<?php

namespace WordfenceLS;

use WordfenceLS\Crypto\Model_JWT;
use WordfenceLS\Crypto\Model_Symmetric;
use RuntimeException;

class Controller_Users {
	const RECOVERY_CODE_COUNT = 5;
	const RECOVERY_CODE_SIZE = 8;
	const SECONDS_PER_DAY = 86400;
	const AUTH_METHOD_2FA = '2fa';
	const AUTH_METHOD_PASSKEY = 'passkey';
	const META_KEY_GRACE_PERIOD_RESET = 'wfls-grace-period-reset';
	const META_KEY_GRACE_PERIOD_OVERRIDE = 'wfls-grace-period-override';
	const META_KEY_ALLOW_GRACE_PERIOD = 'wfls-allow-grace-period';
	const META_KEY_VERIFICATION_TOKENS = 'wfls-verification-tokens';
	const META_KEY_CAPTCHA_SCORES = 'wfls-captcha-scores';
	const VERIFICATION_TOKEN_BYTES = 64;
	const VERIFICATION_TOKEN_LIMIT = 5; //Max number of concurrent tokens
	const VERIFICATION_TOKEN_TRANSIENT_PREFIX = 'wfls_verify_';
	const CAPTCHA_SCORE_LIMIT = 2; //Max number of captcha scores cached
	const CAPTCHA_SCORE_TRANSIENT_PREFIX = 'wfls_captcha_';
	const CAPTCHA_SCORE_CACHE_DURATION = 60; //seconds
	const REMEMBERED_DEVICE_COOKIE_PREFIX = 'wfls-remembered-';
	const REMEMBERED_DEVICE_COOKIE_TYPE = 'remembered-device';
	const REMEMBERED_DEVICE_COOKIE_VERSION = 2;
	const LARGE_USER_BASE_THRESHOLD = 1000;
	const TRUNCATED_ROLE_KEY = 1;

	private $has2FAActiveCache = array();
	private $twoFactorEnrollmentIDCache = array();
	private $remembered2FACache = array();
	private $hasPasskeyActiveCache = array();
	private $any2FAActiveCache = null;
	private $anyPasskeyActiveCache = null;
	private $active2FAUserIDsCache = null;
	private $activePasskeyUserIDsCache = null;
	private $userQueryFilterModes = array();
	private $userSummaryDataCache = null;

	/**
	 * Returns the singleton Controller_Users.
	 *
	 * @return Controller_Users
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_Users();
		}
		return $_shared;
	}

	public function init() {
		$this->_init_actions();
	}

	/**
	 * Imports the array of 2FA secrets. Users that do not currently exist or are disallowed from enabling 2FA are not imported.
	 *
	 * @param array $secrets An array of secrets in the format array(<user id> => array('secret' => <secret in hex>, 'recovery' => <recovery keys in hex>, 'ctime' => <timestamp>, 'vtime' => <timestamp>, 'type' => <type>), ...)
	 * @return int The number imported.
	 */
	public function import_2fa($secrets) {
		global $wpdb;
		$table = Controller_DB::shared()->secrets;

		$count = 0;
		foreach ($secrets as $id => $parameters) {
			$user = new \WP_User($id);
			if (!$user->exists() || !$this->can_activate_2fa($user) || $parameters['type'] != 'authenticator' || $this->has_2fa_active($user)) { continue; }
			$secret = Model_Compat::hex2bin($parameters['secret']);
			$recovery = Model_Compat::hex2bin($parameters['recovery']);
			$ctime = (int) $parameters['ctime'];
			$vtime = min((int) $parameters['vtime'], Controller_Time::time());
			$type = $parameters['type'];
			if ($wpdb->query($wpdb->prepare("INSERT INTO `{$table}` (`user_id`, `secret`, `recovery`, `ctime`, `vtime`, `mode`) VALUES (%d, %s, %s, %d, %d, %s)", $user->ID, $secret, $recovery, $ctime, $vtime, $type)) !== false) {
				$this->clear_2fa_active_cache($user->ID);
				$count++;
			}
		}
		return $count;
	}

	public function admin_users() {
		//We should eventually allow for any user to be granted the manage capability, but we won't account for that now
		if (is_multisite()) {
			$logins = get_super_admins();
			$users = array();
			foreach ($logins as $l) {
				$user = new \WP_User(null, $l);
				if ($user->ID > 0) {
					$users[] = $user;
				}
			}
			return $users;
		}

		$query = new \WP_User_Query(http_build_query(array('role' => 'administrator', 'number' => -1)));
		return $query->get_results();
	}

	public function get_users_by_role($role, $limit = -1) {
		if ($role === 'super-admin') {
			$superAdmins = array();
			foreach(get_super_admins() as $username) {
				$superAdmins[] = new \WP_User($username);
			}
			return $superAdmins;
		}
		else {
			$query = new \WP_User_Query(http_build_query(array('role' => $role, 'number' => is_int($limit) ? $limit : -1)));
			return $query->get_results();
		}
	}

	/**
	 * Returns whether or not the user has a valid remembered device.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	public function has_remembered_2fa($user) {
		$userID = (int) $user->ID;
		if (array_key_exists($userID, $this->remembered2FACache)) {
			return $this->remembered2FACache[$userID];
		}

		if (!Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_REMEMBER_DEVICE_ENABLED)) {
			return false;
		}

		foreach ($_COOKIE as $name => $value) {
			$rememberedDevice = $this->decode_remembered_device_cookie($name, $value);
			if ($rememberedDevice === false || $rememberedDevice['user'] !== (string) $user->ID) {
				continue;
			}

			$enrollmentID = $this->two_factor_enrollment_id($user);
			if ($enrollmentID !== false && $rememberedDevice['enrollment'] === $enrollmentID) {
				$this->remembered2FACache[$userID] = true;
				return true;
			}
		}

		$this->remembered2FACache[$userID] = false;
		return false;
	}

	/**
	 * Sets the cookie needed to remember the 2FA status.
	 *
	 * @param \WP_User $user
	 */
	public function remember_2fa($user) {
		if (!Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_REMEMBER_DEVICE_ENABLED)) {
			return;
		}

		if ($this->has_remembered_2fa($user)) {
			return;
		}

		$enrollmentID = $this->two_factor_enrollment_id($user);
		if ($enrollmentID === false) {
			return;
		}

		$cookie = $this->create_remembered_device_cookie($user, $enrollmentID);
		if ($cookie === false) { //Can't generate cookie due to host failure
			return;
		}

		//Remove legacy, invalid, and superseded cookies, while preserving cookies for other users
		foreach ($_COOKIE as $name => $value) {
			if (!$this->should_remove_remembered_device_cookie($name, $value, $user)) {
				continue;
			}
			setcookie($name, '', \WordfenceLS\Controller_Time::time() - 86400, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
		}

		//Set the new one
		setcookie($cookie['name'], $cookie['value'], $cookie['expiration'], COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
	}

	/**
	 * Creates an authenticated remembered-device cookie for a user and enrollment.
	 *
	 * @param \WP_User $user The user to remember.
	 * @param string $enrollmentID The user's current 2FA enrollment identifier.
	 * @return array|bool The cookie parameters, or false if encryption fails.
	 */
	private function create_remembered_device_cookie($user, $enrollmentID) {
		$encrypted = Model_Symmetric::encrypt(json_encode(array(
			'user' => (string) $user->ID,
			'enrollment' => $enrollmentID,
		)));
		if (!$encrypted) {
			return false;
		}

		$id = Model_JWT::base64url_encode(Model_Crypto::random_bytes(16));
		$expiration = \WordfenceLS\Controller_Time::time() + Controller_Settings::shared()->get_int(Controller_Settings::OPTION_REMEMBER_DEVICE_DURATION);
		$jwt = new Model_JWT(array(
			'type' => self::REMEMBERED_DEVICE_COOKIE_TYPE,
			'version' => self::REMEMBERED_DEVICE_COOKIE_VERSION,
			'id' => $id,
			'data' => $encrypted['data'],
			'iv' => $encrypted['iv'],
		), $expiration);
		return array(
			'name' => self::REMEMBERED_DEVICE_COOKIE_PREFIX . $id,
			'value' => (string) $jwt,
			'expiration' => $expiration,
		);
	}

	/**
	 * Decodes and validates the self-contained portion of a remembered-device cookie.
	 *
	 * @param string $name The cookie name.
	 * @param mixed $value The cookie value.
	 * @return array|bool The decrypted remembered-device data, or false if invalid.
	 */
	private function decode_remembered_device_cookie($name, $value) {
		if (!is_string($name) || !preg_match('/^wfls\-remembered\-([A-Za-z0-9_-]{22})$/D', $name, $matches) || !is_string($value)) {
			return false;
		}

		$jwt = Model_JWT::decode_jwt($value);
		if (!$jwt || !isset($jwt->payload['type'], $jwt->payload['version'], $jwt->payload['id'], $jwt->payload['data'], $jwt->payload['iv']) ||
			$jwt->payload['type'] !== self::REMEMBERED_DEVICE_COOKIE_TYPE ||
			$jwt->payload['version'] !== self::REMEMBERED_DEVICE_COOKIE_VERSION ||
			!is_string($jwt->payload['id']) || !hash_equals($matches[1], $jwt->payload['id']) ||
			!is_string($jwt->payload['data']) || !is_string($jwt->payload['iv'])) {
			return false;
		}

		$maxExpiration = \WordfenceLS\Controller_Time::time() + Controller_Settings::shared()->get_int(Controller_Settings::OPTION_REMEMBER_DEVICE_DURATION);
		if (\WordfenceLS\Controller_Time::time() > min($jwt->expiration, $maxExpiration)) { //Either JWT is expired or the remember period was shortened since generating it
			return false;
		}

		$decrypted = Model_Symmetric::decrypt(array('data' => $jwt->payload['data'], 'iv' => $jwt->payload['iv']));
		$rememberedDevice = is_string($decrypted) ? @json_decode($decrypted, true) : false;
		if (!is_array($rememberedDevice) || !isset($rememberedDevice['user'], $rememberedDevice['enrollment']) ||
			!is_string($rememberedDevice['user']) || !preg_match('/^[1-9][0-9]*$/D', $rememberedDevice['user']) ||
			!is_string($rememberedDevice['enrollment']) || !preg_match('/^[1-9][0-9]*$/D', $rememberedDevice['enrollment'])) {
			return false;
		}

		return $rememberedDevice;
	}

	/**
	 * Returns whether an existing remembered-device cookie should be removed during issuance.
	 *
	 * @param string $name The cookie name.
	 * @param mixed $value The cookie value.
	 * @param \WP_User $user The user receiving a new cookie.
	 * @return bool
	 */
	private function should_remove_remembered_device_cookie($name, $value, $user) {
		if (!is_string($name) || strpos($name, self::REMEMBERED_DEVICE_COOKIE_PREFIX) !== 0) {
			return false;
		}

		$rememberedDevice = $this->decode_remembered_device_cookie($name, $value);
		return $rememberedDevice === false || $rememberedDevice['user'] === (string) $user->ID;
	}

	/**
	 * Returns whether or not 2FA can be activated on the given user.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	public function can_activate_2fa($user) {
		if (!Controller_Settings::shared()->is_2fa_enabled()) {
			return false;
		}
		if (is_multisite() && !is_super_admin($user->ID)) {
			return Controller_Permissions::shared()->does_user_have_multisite_capability($user, Controller_Permissions::CAP_ACTIVATE_2FA_SELF);
		}
		return user_can($user, Controller_Permissions::CAP_ACTIVATE_2FA_SELF);
	}

	/**
	 * Returns whether any stored 2FA enrollment exists, independent of global availability.
	 *
	 * @return bool
	 */
	public function any_2fa_active() {
		if ($this->any2FAActiveCache === null) {
			global $wpdb;
			$table = Controller_DB::shared()->secrets;
			$value = $wpdb->get_var("SELECT 1 FROM `{$table}` LIMIT 1");
			$this->any2FAActiveCache = $value !== null && $value !== false;
		}
		return $this->any2FAActiveCache;
	}

	/**
	 * Returns whether or not the user has 2FA activated.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	public function has_2fa_active($user) {
		if (!$this->can_activate_2fa($user)) {
			return false;
		}
		return $this->has_2fa_enrollment($user);
	}

	/**
	 * Returns whether the user has a stored 2FA enrollment, independent of global availability.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	public function has_2fa_enrollment($user) {
		$userID = (int) $user->ID;
		if (!array_key_exists($userID, $this->has2FAActiveCache)) {
			global $wpdb;
			$table = Controller_DB::shared()->secrets;
			$value = $wpdb->get_var($wpdb->prepare("SELECT `id` FROM `{$table}` WHERE `user_id` = %d LIMIT 1", $userID));
			$enrollmentID = ((is_int($value) || is_string($value)) && preg_match('/^[1-9][0-9]*$/D', (string) $value)) ? (string) $value : false;
			$this->twoFactorEnrollmentIDCache[$userID] = $enrollmentID;
			$this->has2FAActiveCache[$userID] = $enrollmentID !== false;
		}
		return $this->has2FAActiveCache[$userID];
	}

	/**
	 * Returns the identifier for the user's current 2FA enrollment.
	 *
	 * @param \WP_User $user The user to inspect.
	 * @return string|bool The enrollment identifier, or false if 2FA is not active.
	 */
	private function two_factor_enrollment_id($user) {
		if (!$this->can_activate_2fa($user)) {
			return false;
		}
		$userID = (int) $user->ID;
		if (!array_key_exists($userID, $this->twoFactorEnrollmentIDCache)) {
			$this->has_2fa_enrollment($user);
		}
		return array_key_exists($userID, $this->twoFactorEnrollmentIDCache) ? $this->twoFactorEnrollmentIDCache[$userID] : false;
	}

	/**
	 * Clears cached 2FA enrollment and remembered-device state.
	 *
	 * @param int|null $userID The user to clear, or null to clear all users.
	 * @return void
	 */
	public function clear_2fa_active_cache($userID = null) {
		if ($userID === null) {
			$this->has2FAActiveCache = array();
			$this->twoFactorEnrollmentIDCache = array();
			$this->remembered2FACache = array();
		}
		else {
			$userID = (int) $userID;
			unset($this->has2FAActiveCache[$userID], $this->twoFactorEnrollmentIDCache[$userID], $this->remembered2FACache[$userID]);
		}
		$this->any2FAActiveCache = null;
		$this->active2FAUserIDsCache = null;
	}

	/**
	 * Clears cached passkey activation state.
	 *
	 * @param int|null $userID The user to clear, or null to clear all users.
	 * @return void
	 */
	public function clear_passkey_active_cache($userID = null) {
		if ($userID === null) {
			$this->hasPasskeyActiveCache = array();
		}
		else {
			unset($this->hasPasskeyActiveCache[(int) $userID]);
		}
		$this->anyPasskeyActiveCache = null;
		$this->activePasskeyUserIDsCache = null;
	}

	/**
	 * Clears all cached active authentication state.
	 *
	 * @return void
	 */
	public function clear_active_authentication_cache() {
		$this->clear_2fa_active_cache();
		$this->clear_passkey_active_cache();
	}

	/**
	 * Returns whether a request contains a valid active-state user filter.
	 *
	 * @param string $requestKey The request parameter to inspect.
	 * @return bool
	 */
	private function has_user_id_activity_filter($requestKey) {
		return isset($_REQUEST[$requestKey]) && preg_match('/^(?:in)?active$/i', $_REQUEST[$requestKey]) === 1;
	}

	/**
	 * Deactivates a user.
	 *
	 * @param \WP_User $user
	 */
	public function deactivate_2fa($user) {
		global $wpdb;
		$table = Controller_DB::shared()->secrets;
		if ($wpdb->query($wpdb->prepare("DELETE FROM `{$table}` WHERE `user_id` = %d", $user->ID)) !== false) {
			$this->clear_2fa_active_cache($user->ID);
		}

		/**
		 * Fires when 2FA is disabled for a user.
		 *
		 * @since 1.1.13
		 *
		 * @param \WP_User $user The user.
		 */
		do_action('wordfence_ls_2fa_deactivated', $user);
	}

	private function has_admin_with_2fa_active() {
		static $cache = null;
		if ($cache === null) {
			$activeIDs = $this->_user_ids_with_2fa_active();
			foreach ($activeIDs as $id) {
				if (Controller_Permissions::shared()->can_manage_settings(new \WP_User($id))) {
					$cache = true;
					return $cache;
				}
			}
			$cache = false;
		}
		return $cache;
	}

	private function has_admin_with_passkey_active() {
		static $cache = null;
		if ($cache === null) {
			$activeIDs = $this->_user_ids_with_passkey_active();
			foreach ($activeIDs as $id) {
				if (Controller_Permissions::shared()->can_manage_settings(new \WP_User($id))) {
					$cache = true;
					return $cache;
				}
			}
			$cache = false;
		}
		return $cache;
	}

	/**
	 * Returns whether or not 2FA is required for the user regardless of activation status. 2FA is considered required
	 * when the option to require it is enabled and there is at least one administrator with it active.
	 *
	 * @param \WP_User $user
	 * @param bool &$gracePeriod
	 * @param int &$requiredAt
	 * @return bool
	 */
	public function requires_2fa($user, &$gracePeriod = false, &$requiredAt = null) {
		if (!Controller_Settings::shared()->is_2fa_enabled()) {
			$gracePeriod = false;
			$requiredAt = null;
			return false;
		}
		static $cache = array();
		if (array_key_exists($user->ID, $cache)) {
			list($required, $gracePeriod, $requiredAt) = $cache[$user->ID];
			return $required;
		}
		else {
			$gracePeriod = false;
			$requiredAt = null;
			$required = $this->does_user_role_require_2fa($user, $gracePeriod, $requiredAt);
			$cache[$user->ID] = array($required, $gracePeriod, $requiredAt);
			return $required;
		}
	}

	/**
	 * Returns whether or not a passkey is required for the user regardless of activation status. A passkey is
	 * considered required when the option to require it is enabled and there is at least one administrator with it
	 * active.
	 *
	 * @param \WP_User $user
	 * @param bool &$gracePeriod
	 * @param int &$requiredAt
	 * @return bool
	 */
	public function requires_passkey($user, &$gracePeriod = false, &$requiredAt = null) {
		if (!Controller_Settings::shared()->are_passkeys_enabled()) {
			$gracePeriod = false;
			$requiredAt = null;
			return false;
		}
		static $cache = array();
		if (array_key_exists($user->ID, $cache)) {
			list($required, $gracePeriod, $requiredAt) = $cache[$user->ID];
			return $required;
		}
		else {
			$gracePeriod = false;
			$requiredAt = null;
			$required = $this->does_user_role_require_passkey($user, $gracePeriod, $requiredAt);
			$cache[$user->ID] = array($required, $gracePeriod, $requiredAt);
			return $required;
		}
	}

	/**
	 * Returns whether or not additional authentication (2FA or passkey) is missing and required now.
	 *
	 * Missing methods still inside their grace period set the grace-period output values, but do not make the
	 * return value true unless another missing method is already enforceable.
	 *
	 * @param \WP_User $user
	 * @param bool &$gracePeriod
	 * @param int &$requiredAt
	 * @return bool
	 */
	public function requires_additional_auth($user, &$gracePeriod = false, &$requiredAt = null) {
		static $cache = array();
		if (array_key_exists($user->ID, $cache)) {
			list($required, $gracePeriod, $requiredAt) = $cache[$user->ID];
			return $required;
		}
		else {
			$gracePeriod = false;
			$requiredAt = null;
			$tfaRequired = $this->does_user_role_require_2fa($user, $maybe2FAGracePeriod, $maybe2FARequiredAt);
			$passkeyRequired = $this->does_user_role_require_passkey($user, $maybePasskeyGracePeriod, $maybePasskeyRequiredAt);
			$has2FA = $this->has_2fa_active($user);
			$hasPasskey = $this->has_passkey_active($user);
			$missingRequired2FA = $tfaRequired && !$has2FA;
			$missingRequiredPasskey = $passkeyRequired && !$hasPasskey;
			$missingGrace2FA = !$tfaRequired && $maybe2FAGracePeriod && !$has2FA;
			$missingGracePasskey = !$passkeyRequired && $maybePasskeyGracePeriod && !$hasPasskey;
			$missingRequired = $missingRequired2FA || $missingRequiredPasskey;
			$gracePeriod = $missingGrace2FA || $missingGracePasskey;

			$requiredAtCandidates = $missingRequired ? array(
				array($missingRequired2FA, $maybe2FARequiredAt),
				array($missingRequiredPasskey, $maybePasskeyRequiredAt),
			) : array(
				array($missingGrace2FA, $maybe2FARequiredAt),
				array($missingGracePasskey, $maybePasskeyRequiredAt),
			);
			foreach ($requiredAtCandidates as $candidate) {
				if ($candidate[0] && $candidate[1] !== null && ($requiredAt === null || $candidate[1] < $requiredAt)) {
					$requiredAt = $candidate[1];
				}
			}
			$cache[$user->ID] = array(
				$missingRequired,
				$gracePeriod,
				$requiredAt,
			);
			return $missingRequired;
		}
	}

	/**
	 * Returns missing authentication methods that are still inside the user's grace period.
	 *
	 * @param \WP_User $user The user to inspect.
	 * @param int|null &$requiredAt The earliest timestamp at which one of the returned methods will be required.
	 * @return string[] Authentication method identifiers.
	 */
	public function required_auth_methods_in_grace_period($user, &$requiredAt = null) {
		$requiredAt = null;
		$methods = array();
		$tfaRequired = $this->does_user_role_require_2fa($user, $maybe2FAGracePeriod, $maybe2FARequiredAt);
		$passkeyRequired = $this->does_user_role_require_passkey($user, $maybePasskeyGracePeriod, $maybePasskeyRequiredAt);
		$missingGrace2FA = !$tfaRequired && $maybe2FAGracePeriod && !$this->has_2fa_active($user);
		$missingGracePasskey = !$passkeyRequired && $maybePasskeyGracePeriod && !$this->has_passkey_active($user);

		if ($missingGrace2FA) {
			$methods[] = self::AUTH_METHOD_2FA;
			if ($maybe2FARequiredAt !== null) {
				$requiredAt = $maybe2FARequiredAt;
			}
		}
		if ($missingGracePasskey) {
			$methods[] = self::AUTH_METHOD_PASSKEY;
			if ($maybePasskeyRequiredAt !== null && ($requiredAt === null || $maybePasskeyRequiredAt < $requiredAt)) {
				$requiredAt = $maybePasskeyRequiredAt;
			}
		}

		return $methods;
	}

	/**
	 * Returns the number of recovery codes remaining for the user or null if the user does not have 2FA active.
	 *
	 * @param \WP_User $user
	 * @return int
	 */
	public function recovery_code_count($user) {
		global $wpdb;
		$table = Controller_DB::shared()->secrets;
		$record = $wpdb->get_var($wpdb->prepare("SELECT `recovery` FROM `{$table}` WHERE `user_id` = %d", $user->ID));
		if (!$record) {
			return 0;
		}

		return intdiv(Model_Crypto::strlen($record), self::RECOVERY_CODE_SIZE);
	}

	/**
	 * Generates a new set of recovery codes and saves them to $user if provided.
	 *
	 * @param \WP_User|bool $user The user to save the codes to or false to just return codes.
	 * @param int $count
	 * @return array
	 */
	public function regenerate_recovery_codes($user = false, $count = self::RECOVERY_CODE_COUNT) {
		$codes = array();
		for ($i = 0; $i < $count; $i++) {
			$c = \WordfenceLS\Model_Crypto::random_bytes(self::RECOVERY_CODE_SIZE);
			$codes[] = $c;
		}

		if ($user && Controller_Users::shared()->has_2fa_active($user)) {
			global $wpdb;
			$table = Controller_DB::shared()->secrets;
			$wpdb->query($wpdb->prepare("UPDATE `{$table}` SET `recovery` = %s WHERE `user_id` = %d", implode('', $codes), $user->ID));
		}

		return $codes;
	}

	/**
	 * Returns whether or not a passkey can be managed on the given user.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	public function can_manage_passkey($user) {
		if (!Controller_Settings::shared()->are_passkeys_enabled()) {
			return false;
		}
		if (is_multisite() && !is_super_admin($user->ID)) {
			return Controller_Permissions::shared()->does_user_have_multisite_capability($user, Controller_Permissions::CAP_MANAGE_PASSKEY_SELF);
		}
		return user_can($user, Controller_Permissions::CAP_MANAGE_PASSKEY_SELF);
	}

	/**
	 * Returns whether any stored passkey exists, independent of global availability.
	 *
	 * @return bool
	 */
	public function any_passkey_active() {
		if ($this->anyPasskeyActiveCache === null) {
			global $wpdb;
			$table = Controller_DB::shared()->passkeys;
			$value = $wpdb->get_var("SELECT 1 FROM `{$table}` LIMIT 1");
			$this->anyPasskeyActiveCache = $value !== null && $value !== false;
		}
		return $this->anyPasskeyActiveCache;
	}

	/**
	 * Returns whether or not the user has 2FA activated.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	public function has_passkey_active($user) {
		if (!$this->can_manage_passkey($user)) {
			return false;
		}
		return $this->has_registered_passkey($user);
	}

	/**
	 * Returns whether the user has a stored passkey, independent of global availability.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	public function has_registered_passkey($user) {
		$userID = (int) $user->ID;
		if (!array_key_exists($userID, $this->hasPasskeyActiveCache)) {
			global $wpdb;
			$table = Controller_DB::shared()->passkeys;
			$value = $wpdb->get_var($wpdb->prepare("SELECT 1 FROM `{$table}` WHERE `user_id` = %d LIMIT 1", $userID));
			$this->hasPasskeyActiveCache[$userID] = $value !== null && $value !== false;
		}
		return $this->hasPasskeyActiveCache[$userID];
	}

	/**
	 * Records the reCAPTCHA score for later display.
	 *
	 * This is not atomic, which means this can miscount on hits that overlap, but the overhead of being atomic is not
	 * worth it for our use.
	 *
	 * @param \WP_User $user|null
	 * @param float $score
	 */
	public function record_captcha_score($user, $score) {
		if (!Controller_CAPTCHA::shared()->enabled()) { return; }

		if ($user) { update_user_meta($user->ID, 'wfls-last-captcha-score', $score); }
		$stats = Controller_Settings::shared()->get_array(Controller_Settings::OPTION_CAPTCHA_STATS, array());
		if (!array_key_exists('counts', $stats)) { $stats['counts'] = array_fill(0, 10, 0); }
		if (!array_key_exists('avg', $stats)) { $stats['avg'] = 0; }
		$int_score = min(max((int) ($score * 10), 0), 10);
		$count = array_sum($stats['counts']);
		$stats['counts'][$int_score]++;
		$stats['avg'] = ($stats['avg'] * $count + $int_score) / ($count + 1);
		Controller_Settings::shared()->set(Controller_Settings::OPTION_CAPTCHA_STATS, $stats);
	}

	/**
	 * Returns the active and inactive user counts.
	 *
	 * @return array
	 */
	public function user_counts() {
		if (is_multisite() && function_exists('get_user_count')) {
			$total_users = get_user_count();
		}
		else {
			global $wpdb;
			$total_users = (int) $wpdb->get_var("SELECT COUNT(ID) as c FROM {$wpdb->users}");
		}
		$active_users = $this->active_count();
		$passkey_active_users = $this->passkey_active_count();
		return array(
			'active_users' => $active_users,
			'inactive_users' => max($total_users - $active_users, 0),
			'passkey_active_users' => $passkey_active_users,
			'passkey_inactive_users' => max($total_users - $passkey_active_users, 0)
		);
	}

	public function detailed_user_counts($force = false) {
		global $wpdb;

		$blog_prefix = $wpdb->get_blog_prefix();
		$wp_roles = new \WP_Roles();
		$roles = $wp_roles->get_names();

		$counts = array();
		$groups = array(
			'avail_roles' => 0,
			'active_avail_roles' => 0,
			'passkey_active_avail_roles' => 0,
		);

		foreach ($groups as $group => $count) {
			$counts[$group] = array();
			foreach ($roles as $role_key => $role_name) {
				$counts[$group][$role_key] = 0;
			}
			$counts[$group][self::TRUNCATED_ROLE_KEY] = 0;
		}

		$dbController = Controller_DB::shared();
		$dbController->require_schema_version(3); //Can potentially run before the install handler runs migrations, so ensure we're at the minimum version

		if ($dbController->create_temporary_role_counts_table()) {
			$lock = new Utility_NullLock();
			$role_counts_table = $dbController->role_counts_temporary;
		}
		else {
			$lock = new Utility_DatabaseLock($dbController, 'role-count-calculation');
			$role_counts_table = $dbController->role_counts;
		}

		try {
			$lock->acquire();

			if (!$force && Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_USER_COUNT_QUERY_STATE)) {
				throw new RuntimeException('Previous user count query failed to completed successfully. User count queries are currently disabled');
			}
			Controller_Settings::shared()->set(Controller_Settings::OPTION_USER_COUNT_QUERY_STATE, true);

			$secrets_table = $dbController->secrets;
			$passkeys_table = $dbController->passkeys;

			$dbController->query("TRUNCATE {$role_counts_table}");
			$dbController->query($wpdb->prepare(<<<SQL
				INSERT INTO {$role_counts_table}
				SELECT
					um.meta_value AS serialized_roles,
					s.user_id IS NULL AS two_factor_inactive,
					p.user_id IS NULL AS passkey_inactive,
					1 AS user_count
				FROM
					{$wpdb->usermeta} um
				INNER JOIN {$wpdb->users} u ON u.ID = um.user_id
				LEFT JOIN (
					SELECT DISTINCT user_id
					FROM {$secrets_table}
				) s ON s.user_id = u.ID
				LEFT JOIN (
					SELECT DISTINCT user_id
					FROM {$passkeys_table}
				) p ON p.user_id = u.ID
				WHERE
					meta_key = %s
				ON DUPLICATE KEY
						UPDATE user_count = user_count + 1;
SQL
			, "{$blog_prefix}capabilities"));

			$results = $wpdb->get_results(<<<SQL
				SELECT
					serialized_roles AS serialized_roles,
					two_factor_inactive,
					passkey_inactive,
					user_count
				FROM
					{$role_counts_table};
SQL
			, OBJECT);

			Controller_Settings::shared()->set(Controller_Settings::OPTION_USER_COUNT_QUERY_STATE, false);
		}
		catch (RuntimeException $e) {
			$lock->release(); //Finally is not supported in older PHP versions, so it is necessary to release the lock in two places
			return false;
		}
		$lock->release();

		foreach ($results as $row) {
			$truncated_role = false;
			try {
				$row_roles = Utility_Serialization::unserialize($row->serialized_roles, array('allowed_classes' => false), 'is_array');
			}
			catch (RuntimeException $e) {
				$row_roles = array(self::TRUNCATED_ROLE_KEY => true);
				$truncated_role = true;
			}
			foreach ($row_roles as $row_role => $state) {
				if ($state !== true || (!$truncated_role && !is_string($row_role)))
					continue;
				if (array_key_exists($row_role, $roles) || $row_role === self::TRUNCATED_ROLE_KEY) {
					foreach ($groups as $group => &$group_count) {
						if ($group === 'active_avail_roles' && $row->two_factor_inactive) { continue; }
						else if ($group === 'passkey_active_avail_roles' && $row->passkey_inactive) { continue; }
						$counts[$group][$row_role] += $row->user_count;
						$group_count += $row->user_count;
					}
				}
			}
		}

		foreach ($roles as $role_key => $role_name) {
			if ($counts['avail_roles'][$role_key] === 0 && $counts['active_avail_roles'][$role_key] === 0 && $counts['passkey_active_avail_roles'][$role_key] === 0) {
				unset($counts['avail_roles'][$role_key]);
				unset($counts['active_avail_roles'][$role_key]);
				unset($counts['passkey_active_avail_roles'][$role_key]);
			}
		}

		// Separately add super admins for multisite
		if (is_multisite()) {
			$superAdmins = 0;
			$activeSuperAdmins = 0;
			$passkeySuperAdmins = 0;
			foreach(get_super_admins() as $username) {
				$superAdmins++;
				$user = new \WP_User($username);
				if ($this->has_2fa_enrollment($user)) {
					$activeSuperAdmins++;
				}
				if ($this->has_registered_passkey($user)) {
					$passkeySuperAdmins++;
				}
			}
			$counts['avail_roles']['super-admin'] = $superAdmins;
			$counts['active_avail_roles']['super-admin'] = $activeSuperAdmins;
			$counts['passkey_active_avail_roles']['super-admin'] = $passkeySuperAdmins;
		}

		$counts['total_users'] = $groups['avail_roles'];
		$counts['active_total_users'] = $groups['active_avail_roles'];
		$counts['passkey_active_total_users'] = $groups['passkey_active_avail_roles'];

		return $counts;
	}

	/**
	 * Returns the number of users with 2FA active.
	 *
	 * @return int
	 */
	public function active_count() {
		global $wpdb;
		$table = Controller_DB::shared()->secrets;
		return intval($wpdb->get_var("SELECT COUNT(DISTINCT `user_id`) FROM `{$table}`"));
	}

	/**
	 * Returns the number of users with a passkey active.
	 *
	 * @return int
	 */
	public function passkey_active_count() {
		global $wpdb;
		$table = Controller_DB::shared()->passkeys;
		return intval($wpdb->get_var("SELECT COUNT(DISTINCT `user_id`) FROM `{$table}`"));
	}

	/**
	 * WP Filters/Actions
	 */

	protected function _init_actions() {
		add_action('deleted_user', array($this, '_deleted_user'));
		add_filter('manage_users_columns', array($this, '_manage_users_columns'));
		add_filter('manage_users_custom_column', array($this, '_manage_users_custom_column'), 10, 3);
		add_filter('manage_users_sortable_columns', array($this, '_manage_users_sortable_columns'), 10, 1);
		add_action('pre_user_query', array($this, '_pre_user_query'));
		add_filter('users_list_table_query_args', array($this, '_users_list_table_query_args'));
		add_filter('user_row_actions', array($this, '_user_row_actions'), 10, 2);
		add_filter('views_users', array($this, '_views_users'));

		if (is_multisite()) {
			add_filter('manage_users-network_columns', array($this, '_manage_users_columns'));
			add_filter('manage_users-network_custom_column', array($this, '_manage_users_custom_column'), 10, 3);
			add_filter('manage_users-network_sortable_columns', array($this, '_manage_users_sortable_columns'), 10, 1);
			add_filter('ms_user_row_actions', array($this, '_user_row_actions'), 10, 2);
			add_filter('views_users-network', array($this, '_views_users'));
		}
	}

	public function _deleted_user($id) {
		$user = new \WP_User($id);
		if ($user instanceof \WP_User && !$user->exists()) {
			global $wpdb;
			$tables = array(
				Controller_DB::shared()->secrets,
				Controller_DB::shared()->passkeys,
			);
			foreach ($tables as $table) {
				$wpdb->query($wpdb->prepare("DELETE FROM `{$table}` WHERE `user_id` = %d", $id));
			}
			$this->clear_2fa_active_cache($id);
			$this->clear_passkey_active_cache($id);
		}
	}

	public function _manage_users_columns($columns = array()) {
		if (user_can(wp_get_current_user(), Controller_Permissions::CAP_ACTIVATE_2FA_OTHERS)) {
			$columns['wfls_2fa_status'] = esc_html__('2FA Status', 'wordfence');
		}
		if (user_can(wp_get_current_user(), Controller_Permissions::CAP_MANAGE_PASSKEY_OTHERS)) {
			$columns['wfls_passkey_status'] = esc_html__('Passkey Status', 'wordfence');
		}

		if (Controller_Settings::shared()->are_login_history_columns_enabled() && Controller_Permissions::shared()->can_manage_settings(wp_get_current_user())) {
			$columns['wfls_last_login'] = esc_html__('Last Login', 'wordfence');
			if (Controller_CAPTCHA::shared()->enabled()) {
				$columns['wfls_last_captcha'] = esc_html__('Last CAPTCHA', 'wordfence');
			}
		}
		return $columns;
	}

	public function _manage_users_custom_column($value = '', $column_name = '', $user_id = 0) {
		switch($column_name) {
			case 'wfls_2fa_status':
				$user = new \WP_User($user_id);
				$value = __('Not Allowed', 'wordfence');
				if (Controller_Users::shared()->can_activate_2fa($user)) {
					$has2fa = Controller_Users::shared()->has_2fa_active($user);
					$requires2fa = $this->requires_2fa($user, $inGracePeriod);
					if ($has2fa) {
						$value = esc_html__('Active', 'wordfence');
					}
					elseif ($inGracePeriod) {
						$value = wp_kses(__('Inactive<small class="wfls-sub-status">(Grace Period)</small>', 'wordfence'), array('small'=>array('class'=>array())));
					}
					elseif (($requires2fa && !$has2fa)) {
						$value = wp_kses($inGracePeriod === null ? __('Locked Out<small class="wfls-sub-status">(Grace Period Disabled)</small>', 'wordfence') : __('Locked Out<small class="wfls-sub-status">(Grace Period Exceeded)</small>', 'wordfence'), array('small'=>array('class'=>array())));
					}
					else {
						$value = esc_html__('Inactive', 'wordfence');
					}
				}
				break;
			case 'wfls_passkey_status':
				$user = new \WP_User($user_id);
				$value = __('Not Allowed', 'wordfence');
				$hasPasskey = Controller_Users::shared()->has_passkey_active($user);
				if (Controller_Users::shared()->can_manage_passkey($user) || $hasPasskey) {
					$value = $hasPasskey ? esc_html__('Active', 'wordfence') : esc_html__('Inactive', 'wordfence');
				}
				break;
			case 'wfls_last_login':
				$value = '-';
				if (($last = get_user_meta($user_id, 'wfls-last-login', true)) && Utility_Number::isUnixTimestamp($last)) {
					$value = Controller_Time::format_local_time(get_option('date_format') . ' ' . get_option('time_format'), $last);
				}
				break;
			case 'wfls_last_captcha':
				$user = new \WP_User($user_id);
				$value = '-';
				if (($last = get_user_meta($user_id, 'wfls-last-captcha-score', true))) {
					$value = number_format($last, 1);
				}
				break;
		}

		return $value;
	}

	public function _manage_users_sortable_columns($sortable_columns) {
		return array_merge($sortable_columns, array(
			'wfls_last_login' => 'wfls-lastlogin',
			'wfls_last_captcha' => 'wfls-lastcaptcha',
		));
	}

	protected function _user_ids_with_2fa_active() {
		if ($this->active2FAUserIDsCache === null) {
			global $wpdb;
			$table = Controller_DB::shared()->secrets;
			$this->active2FAUserIDsCache = (array) $wpdb->get_col("SELECT DISTINCT `user_id` FROM `{$table}`");
		}
		return $this->active2FAUserIDsCache;
	}

	protected function _user_ids_with_passkey_active() {
		if ($this->activePasskeyUserIDsCache === null) {
			global $wpdb;
			$table = Controller_DB::shared()->passkeys;
			$this->activePasskeyUserIDsCache = (array) $wpdb->get_col("SELECT DISTINCT `user_id` FROM `{$table}`");
		}
		return $this->activePasskeyUserIDsCache;
	}

	public function _users_list_table_query_args($args) {
		$this->userQueryFilterModes = array();
		if ($this->has_user_id_activity_filter('wf2fa')) {
			$this->userQueryFilterModes['2fa'] = strtolower($_REQUEST['wf2fa']);
		}
		if ($this->has_user_id_activity_filter('wfls-passkey')) {
			$this->userQueryFilterModes['passkey'] = strtolower($_REQUEST['wfls-passkey']);
		}

		if (isset($args['orderby'])) {
			if (is_string($args['orderby'])) {
				if ($args['orderby'] == 'wfls-lastlogin') {
					$args['meta_key'] = 'wfls-last-login';
					$args['orderby'] = 'meta_value';
				}
				else if ($args['orderby'] == 'wfls-lastcaptcha') {
					$args['meta_key'] = 'wfls-last-captcha-score';
					$args['orderby'] = 'meta_value';
				}
			}
			else {
				$has_one = false;
				if (array_key_exists('wfls-lastlogin', $args['orderby'])) {
					$args['meta_key'] = 'wfls-last-login';
					$args['orderby']['meta_value'] = $args['orderby']['wfls-lastlogin'];
					unset($args['orderby']['wfls-lastlogin']);
					$has_one = true;
				}

				if (array_key_exists('wfls-lastcaptcha', $args['orderby'])) {
					if (!$has_one) { //We have to discard one if both are set to sort by because $meta_key can only be a single value rather than an array
						$args['meta_key'] = 'wfls-last-captcha-score';
						$args['orderby']['meta_value'] = $args['orderby']['wfls-lastcaptcha'];
					}
					unset($args['orderby']['wfls-lastcaptcha']);
					$has_one = true;
				}

				if (in_array('wfls-lastlogin', $args['orderby'])) {
					if (!$has_one) { //We have to discard one if both are set to sort by because $meta_key can only be a single value rather than an array
						$args['meta_key'] = 'wfls-last-login';
						$args['orderby'][] = 'meta_value';
					}
					unset($args['orderby'][array_search('wfls-lastlogin', $args['orderby'])]);
					$has_one = true;
				}

				if (in_array('wfls-lastcaptcha', $args['orderby'])) {
					if (!$has_one) { //We have to discard one if both are set to sort by because $meta_key can only be a single value rather than an array
						$args['meta_key'] = 'wfls-last-captcha-score';
						$args['orderby'][] = 'meta_value';
					}
					unset($args['orderby'][array_search('wfls-lastcaptcha', $args['orderby'])]);
					$has_one = true;
				}
			}
		}
		return $args;
	}

	/**
	 * Adds the 2FA/passkey active/inactive filters to the users query without preloading all matching user IDs.
	 *
	 * @param \WP_User_Query $query
	 */
	public function _pre_user_query($query) {
		$modes = $this->userQueryFilterModes;
		$this->userQueryFilterModes = array();
		if (empty($modes) || !isset($query->query_where) || !is_string($query->query_where)) {
			return;
		}

		global $wpdb;
		$filters = array(
			'2fa' => array(
				'mode' => isset($modes['2fa']) ? $modes['2fa'] : null,
				'table' => Controller_DB::shared()->secrets,
				'alias' => 'wfls_2fa_filter',
			),
			'passkey' => array(
				'mode' => isset($modes['passkey']) ? $modes['passkey'] : null,
				'table' => Controller_DB::shared()->passkeys,
				'alias' => 'wfls_passkey_filter',
			),
		);
		foreach ($filters as $filter) {
			if ($filter['mode'] != 'active' && $filter['mode'] != 'inactive') {
				continue;
			}

			$exists = "EXISTS (SELECT 1 FROM `{$filter['table']}` AS `{$filter['alias']}` WHERE `{$filter['alias']}`.`user_id` = `{$wpdb->users}`.`ID`)";
			$query->query_where .= $filter['mode'] == 'inactive' ? " AND NOT {$exists}" : " AND {$exists}";
		}
	}

	public function _user_row_actions($actions, $user) {
		//Format is 'view' => '<a href="https://wfpremium.dev1.ryanbritton.com/author/ryan/" aria-label="View posts by ryan">View</a>'
		$viewer = wp_get_current_user();
		$canManageSettings = Controller_Permissions::shared()->can_manage_settings($viewer);
		$canManage2FA = user_can($viewer, Controller_Permissions::CAP_ACTIVATE_2FA_OTHERS) && (Controller_Users::shared()->can_activate_2fa($user) || Controller_Users::shared()->has_2fa_active($user));
		$canManagePasskeys = user_can($viewer, Controller_Permissions::CAP_MANAGE_PASSKEY_OTHERS) && Controller_Users::shared()->can_manage_passkey($user);
		if ($canManageSettings || $canManage2FA || $canManagePasskeys) {
			$url = (is_multisite() ? network_admin_url('admin.php?page=WFLS&user=' . $user->ID) : admin_url('admin.php?page=WFLS&user=' . $user->ID));
			$actions['wf2fa'] = '<a href="' . esc_url($url) . '" aria-label="' . esc_attr(sprintf(/* translators: Username */ __('Edit login security for %s', 'wordfence'), $user->user_login)) . '">' . esc_html__('Login Security', 'wordfence') . '</a>';
		}
		return $actions;
	}

	public function _views_users($views) {
		//Format is 'subscriber' => '<a href=\\'users.php?role=subscriber\\'>Subscriber <span class="count">(40,002)</span></a>',
		include(ABSPATH . WPINC . '/version.php'); /** @var string $wp_version */
		if (version_compare($wp_version, '4.4.0', '>=')) {
			$counts = $this->user_counts();
			$views['all'] = str_replace(' class="current" aria-current="page"', '', $views['all']);
			if (user_can(wp_get_current_user(), Controller_Permissions::CAP_ACTIVATE_2FA_OTHERS)) {
				$views['wfls-active'] = '<a href="' . esc_url(add_query_arg('wf2fa', 'active', 'users.php')) . '"' . (isset($_GET['wf2fa']) && $_GET['wf2fa'] == 'active' ? ' class="current" aria-current="page"' : '') . '>' . esc_html__('2FA Active', 'wordfence') . ' <span class="count">(' . number_format($counts['active_users']) . ')</span></a>';
				$views['wfls-inactive'] = '<a href="' . esc_url(add_query_arg('wf2fa', 'inactive', 'users.php')) . '"' . (isset($_GET['wf2fa']) && $_GET['wf2fa'] == 'inactive' ? ' class="current" aria-current="page"' : '') . '>' . esc_html__('2FA Inactive', 'wordfence') . ' <span class="count">(' . number_format($counts['inactive_users']) . ')</span></a>';
			}
			if (user_can(wp_get_current_user(), Controller_Permissions::CAP_MANAGE_PASSKEY_OTHERS)) {
				$views['wfls-passkey-active'] = '<a href="' . esc_url(add_query_arg('wfls-passkey', 'active', 'users.php')) . '"' . (isset($_GET['wfls-passkey']) && $_GET['wfls-passkey'] == 'active' ? ' class="current" aria-current="page"' : '') . '>' . esc_html__('Passkey Active', 'wordfence') . ' <span class="count">(' . number_format($counts['passkey_active_users']) . ')</span></a>';
				$views['wfls-passkey-inactive'] = '<a href="' . esc_url(add_query_arg('wfls-passkey', 'inactive', 'users.php')) . '"' . (isset($_GET['wfls-passkey']) && $_GET['wfls-passkey'] == 'inactive' ? ' class="current" aria-current="page"' : '') . '>' . esc_html__('Passkey Inactive', 'wordfence') . ' <span class="count">(' . number_format($counts['passkey_inactive_users']) . ')</span></a>';
			}
		}
		return $views;
	}

	private static function get_registration_date($user) {
		return strtotime($user->user_registered);
	}

	private function get_grace_period_reset_time($user) {
		$time = get_user_option(self::META_KEY_GRACE_PERIOD_RESET, $user->ID);
		if (empty($time))
			return null;
		return (int) $time;
	}

	public function get_grace_period_override($user) {
		$override = get_user_option(self::META_KEY_GRACE_PERIOD_OVERRIDE, $user->ID);
		if ($override === false)
			return null;
		return (int) $override;
	}

	private function does_user_role_require_2fa($user, &$inGracePeriod = null, &$requiredAt = null) {
		if (!Controller_Settings::shared()->is_2fa_enabled()) {
			$inGracePeriod = false;
			$requiredAt = null;
			return false;
		}
		$is2faAdmin = Controller_Permissions::shared()->can_manage_settings($user);
		$userDate = self::get_grace_period_reset_time($user);
		if ($userDate === null)
			$userDate = self::get_registration_date($user);
		if ($is2faAdmin && !$this->get_grace_period_allowed_flag($user->ID)) {
			$gracePeriod = 0;
			$inGracePeriod = null;
		}
		else {
			$gracePeriod = self::get_grace_period_override($user);
			if ($gracePeriod === null)
				$gracePeriod = Controller_Settings::shared()->get_user_2fa_grace_period();
			$gracePeriod *= self::SECONDS_PER_DAY;
			$inGracePeriod = false;
		}
		$now = time();
		foreach (Controller_Permissions::shared()->get_all_roles($user) as $role) {
			$roleDate = Controller_Settings::shared()->get_required_2fa_role_activation_time($role);
			if ($roleDate === false)
				continue;
			$effectiveDate = max($userDate, $roleDate) + $gracePeriod;
			if ($requiredAt === null || $effectiveDate < $requiredAt)
				$requiredAt = $effectiveDate;
			if ($effectiveDate <= $now && (!$is2faAdmin || $this->has_admin_with_2fa_active())) {
				if ($inGracePeriod)
					$inGracePeriod = false;
				return true;
			}
			else if ($inGracePeriod !== null) {
				$inGracePeriod = true;
			}
		}
		return false;
	}

	private function does_user_role_require_passkey($user, &$inGracePeriod = null, &$requiredAt = null) {
		if (!Controller_Settings::shared()->are_passkeys_enabled()) {
			$inGracePeriod = false;
			$requiredAt = null;
			return false;
		}
		$isPasskeyAdmin = Controller_Permissions::shared()->can_manage_settings($user);
		$userDate = self::get_grace_period_reset_time($user);
		if ($userDate === null) {
			$userDate = self::get_registration_date($user);
		}
		if ($isPasskeyAdmin && !$this->get_grace_period_allowed_flag($user->ID)) {
			$gracePeriod = 0;
			$inGracePeriod = null;
		}
		else {
			$gracePeriod = self::get_grace_period_override($user);
			if ($gracePeriod === null) {
				$gracePeriod = Controller_Settings::shared()->get_user_passkey_grace_period();
			}
			$gracePeriod *= self::SECONDS_PER_DAY;
			$inGracePeriod = false;
		}
		$now = time();
		foreach (Controller_Permissions::shared()->get_all_roles($user) as $role) {
			$roleDate = Controller_Settings::shared()->get_required_passkey_role_activation_time($role);
			if ($roleDate === false) {
				continue;
			}
			$effectiveDate = max($userDate, $roleDate) + $gracePeriod;
			if ($requiredAt === null || $effectiveDate < $requiredAt) {
				$requiredAt = $effectiveDate;
			}
			if ($effectiveDate <= $now && (!$isPasskeyAdmin || $this->has_admin_with_passkey_active())) {
				if ($inGracePeriod) {
					$inGracePeriod = false;
				}
				return true;
			}
			else if ($inGracePeriod !== null) {
				$inGracePeriod = true;
			}
		}
		return false;
	}

	public function reset_grace_period($user, $override = null) {
		if (!($this->can_activate_2fa($user) || $this->can_manage_passkey($user))) {
			return false;
		}

		$requires2FA = $this->requires_2fa($user, $in2FAGracePeriod);
		$requiresPasskey = $this->requires_passkey($user, $inPasskeyGracePeriod);
		$missingRequired2FA = ($requires2FA || $in2FAGracePeriod) && !$this->has_2fa_active($user);
		$missingRequiredPasskey = ($requiresPasskey || $inPasskeyGracePeriod) && !$this->has_passkey_active($user);
		if (!$missingRequired2FA && !$missingRequiredPasskey) {
			return false;
		}

		update_user_option($user->ID, self::META_KEY_GRACE_PERIOD_RESET, time(), true);
		if ($override !== null) {
			update_user_option($user->ID, self::META_KEY_GRACE_PERIOD_OVERRIDE, (int) $override, true);
		}
		return true;
	}

	public function revoke_grace_period($user) {
		foreach(array(
			self::META_KEY_GRACE_PERIOD_RESET,
			self::META_KEY_GRACE_PERIOD_OVERRIDE,
			self::META_KEY_ALLOW_GRACE_PERIOD
			) as $option) {
			delete_user_option($user->ID, $option, true);
		}
	}

	public function allow_grace_period($userId) {
		update_user_option($userId, self::META_KEY_ALLOW_GRACE_PERIOD, true, true);
	}

	public function get_grace_period_allowed_flag($userId) {
		return (bool) get_user_option(self::META_KEY_ALLOW_GRACE_PERIOD, $userId);
	}

	public function has_revokable_grace_period($user) {
		return $this->get_grace_period_allowed_flag($user->ID) || $this->get_grace_period_reset_time($user) !== null;
	}

	private function get_inactive_2fa_super_admins($gracePeriod = false) {
		$inactive = array();
		foreach(get_super_admins() as $username) {
			$user = new \WP_User($username);
			if (!$this->has_2fa_active($user)) {
				$this->requires_2fa($user, $inGracePeriod, $requiredAt);
				if ($gracePeriod === null || $gracePeriod == $inGracePeriod) {
					$current = new \StdClass();
					$current->user_id = $user->ID;
					$current->user_login = $username;
					$current->required_at = $requiredAt;
					$inactive[] = $current;
				}
			}
		}
		return $inactive;
	}

	private function generate_inactive_2fa_user_query($roleKey, $gracePeriod = null, $page = null, $perPage = null) {
		global $wpdb;
		$secondsPerDay = (int) self::SECONDS_PER_DAY;
		$gracePeriodSeconds = (int) (Controller_Settings::shared()->get_user_2fa_grace_period() * self::SECONDS_PER_DAY);
		$roleTime = (int) (Controller_Settings::shared()->get_required_2fa_role_activation_time($roleKey));
		$siteId = get_current_blog_id();
		$blogPrefix = $wpdb->get_blog_prefix($siteId);
		$usermeta = $wpdb->usermeta;
		$users = $wpdb->users;
		$secrets = Controller_DB::shared()->secrets;
		$admin = Controller_Permissions::shared()->can_role_manage_settings($roleKey);
		$parameters = array(
			self::META_KEY_GRACE_PERIOD_RESET,
			self::META_KEY_GRACE_PERIOD_OVERRIDE
		);
		$gracePeriodClause = "IF(overrides.days IS NULL, $gracePeriodSeconds, overrides.days * $secondsPerDay)";
		$registeredTimestampClause = "UNIX_TIMESTAMP(CONVERT_TZ($users.user_registered, '+00:00', @@time_zone))";
		$now = time();
		if ($admin) {
			$allowancesJoin = <<<SQL
				LEFT JOIN (
					SELECT
						user_id,
						meta_value AS allowed
					FROM
						$usermeta
					WHERE
						meta_key = %s
				) allowances ON allowances.user_id = $usermeta.user_id
SQL;
			$parameters[] = self::META_KEY_ALLOW_GRACE_PERIOD;
			$allowedClause = 'IFNULL(allowances.allowed, 0)';
			$gracePeriodClause = "IF($allowedClause = 0, 0, $gracePeriodClause)";
		}
		else {
			$allowancesJoin = null;
			$allowedClause = null;
		}
		$timeClause = "GREATEST($roleTime, $registeredTimestampClause, IFNULL(resets.time, 0)) + $gracePeriodClause";
		$query = <<<SQL
			SELECT
				$usermeta.user_id,
				$users.user_login,
				$timeClause AS required_at
			FROM
				$usermeta
				JOIN $users ON $users.ID = $usermeta.user_id
				LEFT JOIN (
					SELECT
						user_id,
						meta_value AS time
					FROM
						$usermeta
					WHERE
						meta_key = %s
				) resets ON resets.user_id = $usermeta.user_id
				LEFT JOIN (
					SELECT
						user_id,
						meta_value AS days
					FROM
						$usermeta
					WHERE
						meta_key = %s
				) overrides ON overrides.user_id = $usermeta.user_id
				$allowancesJoin
			WHERE
				meta_key = '{$blogPrefix}capabilities'
				AND meta_value LIKE %s
				AND NOT $usermeta.user_id IN(SELECT user_id FROM {$secrets})
SQL;
		$conditions = array();
		$operator = 'AND';
		if ($gracePeriod !== null) {
			if ($gracePeriod) {
				$conditions[] = "$timeClause > $now";
			}
			else {
				$conditions[] = "$timeClause <= $now";
				$operator = 'OR';
			}
		}
		if ($admin) {
			$conditions[] = $allowedClause . ' = ' . ($gracePeriod ? 1 : 0);
		}
		if (!empty($conditions))
			$query .= ' AND (' . implode(" $operator ", $conditions). ')';
		if ($page !== null && $perPage !== null) {
			$offset = (int) (($page - 1) * $perPage);
			$limit = (int) ($perPage + 1);
			if ($offset >= 0 && $perPage > 0)
				$query .= " LIMIT $offset, $limit";
		}
		$serializedRoleKey = serialize($roleKey);
		$roleMatch = '%' . (method_exists($wpdb, 'esc_like') ? $wpdb->esc_like($serializedRoleKey) : addcslashes($serializedRoleKey, '_%\\')). '%';
		$parameters[] = $roleMatch;
		return $wpdb->prepare(
			$query.';',
			$parameters
		);
	}

	public function get_inactive_2fa_users($roleKey, $gracePeriod = null, $page = null, $perPage = null, &$lastPage = null) {
		global $wpdb;
		if (is_multisite() && $roleKey === 'super-admin') {
			$superAdmins = $this->get_inactive_2fa_super_admins($gracePeriod);
			if ($page !== null && $perPage !== null) {
				$start = ($page - 1) * $perPage;
				$end = $start + $perPage;
				$lastPage = $end >= count($superAdmins);
				$superAdmins = array_slice($superAdmins, $start, $perPage);
			}
			return $superAdmins;
		}
		else {
			$query = $this->generate_inactive_2fa_user_query($roleKey, $gracePeriod, $page, $perPage);
			$results = $wpdb->get_results($query);
			if (count($results) > $perPage) {
				$lastPage = false;
				array_pop($results);
			}
			else {
				$lastPage = true;
			}
			return $results;
		}
	}

	private function get_verification_token_transient_key($hash) {
		return self::VERIFICATION_TOKEN_TRANSIENT_PREFIX . $hash;
	}

	private function load_verification_token($hash) {
		$key = $this->get_verification_token_transient_key($hash);
		$userId = get_transient($key);
		if ($userId === false)
			return null;
		return intval($userId);
	}

	private function load_verification_tokens($user) {
		$storedHashes = get_user_meta($user->ID, self::META_KEY_VERIFICATION_TOKENS, true);
		$validHashes = array();
		if (is_array($storedHashes)) {
			foreach ($storedHashes as $hash) {
				$userId = $this->load_verification_token($hash);
				if ($userId === $user->ID)
					$validHashes[] = $hash;
			}
		}
		return $validHashes;
	}

	private function hash_verification_token($token) {
		return wp_hash($token);
	}

	public function generate_verification_token($user) {
		$token = Model_Crypto::random_bytes(self::VERIFICATION_TOKEN_BYTES);
		$hash = $this->hash_verification_token($token);
		$tokens = $this->load_verification_tokens($user);
		array_unshift($tokens, $hash);
		while (count($tokens) > self::VERIFICATION_TOKEN_LIMIT) {
			$excessHash = array_pop($tokens);
			delete_transient($this->get_verification_token_transient_key($excessHash));
		}
		$key = $this->get_verification_token_transient_key($hash);
		set_transient($key, $user->ID, WORDFENCE_LS_EMAIL_VALIDITY_DURATION_MINUTES * 60);
		update_user_meta($user->ID, self::META_KEY_VERIFICATION_TOKENS, $tokens);
		return base64_encode($token);
	}

	public function validate_verification_token($token, $user = null) {
		$hash = $this->hash_verification_token(base64_decode($token));
		$userId = $this->load_verification_token($hash);
		return $userId !== null && ($user === null || $userId === $user->ID);
	}

	/**
	 * Returns the key used to store a captcha score transient.
	 *
	 * @param string $hash
	 * @return string
	 */
	private function get_captcha_score_transient_key($hash) {
		return self::CAPTCHA_SCORE_TRANSIENT_PREFIX . $hash;
	}

	/**
	 * Attempts to look up a stored captcha score for the given hash and user. If found, returns the score. If not,
	 * returns null.
	 *
	 * @param string $hash
	 * @param \WP_User $user
	 * @return float|false
	 */
	private function load_captcha_score($hash, $user) {
		$key = $this->get_captcha_score_transient_key($hash);
		$data = get_transient($key);
		if ($data === false) {
			return false;
		}

		if (!$user->exists() || $data['user'] !== $user->ID) {
			return false;
		}

		return floatval($data['score']);
	}

	/**
	 * Deletes the stored captcha score if present for the given hash.
	 *
	 * @param string $hash
	 */
	private function clear_captcha_score($token, $user) {
		$hash = $this->hash_captcha_token($token);
		$key = $this->get_captcha_score_transient_key($hash);
		delete_transient($key);

		$storedHashes = get_user_meta($user->ID, self::META_KEY_CAPTCHA_SCORES, true);
		$validHashes = array();
		if (is_array($storedHashes)) {
			foreach ($storedHashes as $hash) {
				$storedScore = $this->load_captcha_score($hash, $user);
				if ($storedScore !== false) {
					$validHashes[] = $hash;
				}
			}
		}
		$validHashes = array_slice($validHashes, 0, self::CAPTCHA_SCORE_LIMIT);
		update_user_meta($user->ID, self::META_KEY_CAPTCHA_SCORES, $validHashes);
	}

	/**
	 * Hashes the captcha token for storage.
	 *
	 * @param string $token
	 * @return string
	 */
	private function hash_captcha_token($token) {
		return wp_hash($token);
	}

	/**
	 * Returns the cached score for the given captcha score and user if available. This action removes it from the cache
	 * since the intent is for it only to be used for the initial login request to validate credentials + the follow-up
	 * request either finalizing the login (no 2FA set) or with the 2FA token.
	 *
	 * $expired will be set to `true` if the reason for returning `false` is because the $token is recently expired. It
	 * will be false when the $token is either uncached or has been expired long enough to be removed from the internal
	 * list.
	 *
	 * @param string $token
	 * @param \WP_User $user
	 * @param bool $expired
	 * @return float|false
	 */
	public function cached_captcha_score($token, $user, &$expired = false) {
		$hash = $this->hash_captcha_token($token);
		$score = $this->load_captcha_score($hash, $user);
		if ($score === false) {
			$storedHashes = get_user_meta($user->ID, self::META_KEY_CAPTCHA_SCORES, true);
			if (is_array($storedHashes)) {
				$expired = in_array($hash, $storedHashes);
			}
		}

		$this->clear_captcha_score($token, $user);
		return $score;
	}

	/**
	 * Caches the $token/$score pair for $user, automatically pruning its cached list to the maximum allowable count
	 *
	 * @param string $token
	 * @param float|false $score
	 * @param \WP_User $user
	 */
	public function cache_captcha_score($token, $score, $user) {
		if ($score === false) {
			return;
		}

		$storedHashes = get_user_meta($user->ID, self::META_KEY_CAPTCHA_SCORES, true);
		$validHashes = array();
		if (is_array($storedHashes)) {
			foreach ($storedHashes as $hash) {
				$storedScore = $this->load_captcha_score($hash, $user);
				if ($storedScore !== false) {
					$validHashes[] = $hash;
				}
			}
		}

		$hash = $this->hash_verification_token($token);
		array_unshift($validHashes, $hash);
		while (count($validHashes) > self::CAPTCHA_SCORE_LIMIT) {
			$excessHash = array_pop($validHashes);
			delete_transient($this->get_captcha_score_transient_key($excessHash));
		}

		$key = $this->get_captcha_score_transient_key($hash);
		set_transient($key, array('user' => $user->ID, 'score' => $score), self::CAPTCHA_SCORE_CACHE_DURATION);
		update_user_meta($user->ID, self::META_KEY_CAPTCHA_SCORES, $validHashes);
	}

	public function get_user_count() {
		global $wpdb;
		if (function_exists('get_user_count')) {
			return get_user_count();
		}
		return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
	}

	/**
	 * Returns the user-count data shared by the settings summary and its JavaScript disclosure.
	 *
	 * @return array Summary counts and the detailed role counts when available.
	 */
	public function get_user_summary_data() {
		if ($this->userSummaryDataCache !== null) {
			return $this->userSummaryDataCache;
		}

		$knownTotalUsers = (int) $this->get_user_count();
		$counts = $this->get_detailed_user_counts_if_enabled($knownTotalUsers);
		$twoFactorEnabled = Controller_Settings::shared()->is_2fa_enabled();
		$passkeysEnabled = Controller_Settings::shared()->are_passkeys_enabled();
		$this->userSummaryDataCache = array(
			'counts' => $counts,
			'known_total_users' => $knownTotalUsers,
			'2fa_enabled' => $twoFactorEnabled,
			'passkeys_enabled' => $passkeysEnabled,
			'active_2fa_users' => $twoFactorEnabled ? (is_array($counts) ? (int) $counts['active_total_users'] : $this->active_count()) : 0,
			'active_passkey_users' => $passkeysEnabled ? (is_array($counts) ? (int) $counts['passkey_active_total_users'] : $this->passkey_active_count()) : 0,
		);
		return $this->userSummaryDataCache;
	}

	public function has_large_user_base() {
		return $this->get_user_count() >= self::LARGE_USER_BASE_THRESHOLD;
	}

	public function should_force_user_counts() {
		return isset($_GET['wfls-show-user-counts']);
	}

	/**
	 * Returns detailed user counts when the site-size safeguard permits the aggregation query.
	 *
	 * @param int|null $totalUserCount Previously retrieved total user count, when available.
	 * @return array|null|false Detailed counts, null when deferred, or false after a failed query.
	 */
	public function get_detailed_user_counts_if_enabled($totalUserCount = null) {
		$force = $this->should_force_user_counts();
		if ($totalUserCount === null) {
			$totalUserCount = $this->get_user_count();
		}
		if ($totalUserCount >= self::LARGE_USER_BASE_THRESHOLD && !$force) {
			return null;
		}
		return $this->detailed_user_counts($force);
	}

}
