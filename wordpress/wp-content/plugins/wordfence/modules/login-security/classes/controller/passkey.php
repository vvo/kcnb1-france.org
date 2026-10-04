<?php

namespace WordfenceLS;

use WordfenceLS\Crypto\Model_JWT;

class Controller_Passkey {
	const META_KEY_USER_HANDLE = 'wfls-passkey-user-handle';
	const META_KEY_PASSWORD_AUTH_ENABLED = 'wfls-passkey-password-auth-enabled';
	const REGISTRATION_TOKEN_DURATION = 600;
	const LOGIN_TOKEN_DURATION = 60;
	const MAX_LABEL_LENGTH = 255;
	const MAX_LABEL_BYTES = 765;
	const DEFAULT_MAX_PASSKEYS_PER_USER = 20;
	const REGISTRATION_RATE_LIMIT_CAPACITY = 20;
	const CBOR_MAX_INPUT_BYTES = 262144;
	const CBOR_MAX_COLLECTION_ITEMS = 1024;
	const CBOR_MAX_NESTING_DEPTH = 32;
	const CBOR_MAX_TOTAL_ITEMS = 4096;
	const MAX_CREDENTIAL_ID_BYTES = 1023;
	const MAX_USER_HANDLE_BYTES = 64;
	const MAX_PUBLIC_KEY_BYTES = 529;
	const MAX_REGISTRATION_ATTESTATION_BYTES = 16384;
	const MIN_RSA_MODULUS_BITS = 2048;
	const MAX_RSA_MODULUS_BITS = 4096;
	private static $_verifiedAuthentication = null;

	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_Passkey();
		}
		return $_shared;
	}

	public function init() {
		if (!Controller_Settings::shared()->are_passkeys_enabled()) {
			return;
		}
		add_filter('authenticate', array($this, '_authenticate_verified_passkey'), 5, 3);

		$monarxLoaderSuffix = '/monarx-protect/loader.php';
		foreach (get_included_files() as $includedFile) {
			$normalizedPath = strtolower(str_replace('\\', '/', (string) $includedFile));
			if (substr($normalizedPath, -strlen($monarxLoaderSuffix)) !== $monarxLoaderSuffix) {
				continue;
			}

			// Monarx Protect compatibility workaround: its priority-10 `authenticate` callback does not return the
			// filtered value, replacing a verified passkey user with null before core password authentication.
			add_filter('authenticate', function($user, $username, $password) {
				if ($user !== null) {
					return $user;
				}
				return $this->_authenticate_verified_passkey($user, $username, $password);
			}, 11, 3);
			break;
		}
	}
	
	/**
	 * Returns the default RP for new passkeys when an override is not set.
	 *
	 * @return string
	 */
	public function defaultRP() {
		$host = Utility_URL::reduce_to_public_suffix_plus_one(home_url());
		if ($host === '') {
			$host = Utility_URL::reduce_to_public_suffix_plus_one(site_url());
		}
		return $host;
	}

	private function require_schema() {
		Controller_DB::shared()->require_schema_version(3);
	}

	public function can_manage_passkeys($viewer, $user) {
		if (!Controller_Users::shared()->can_manage_passkey($user)) {
			return false;
		}

		if ($viewer->ID === $user->ID) {
			return user_can($viewer, Controller_Permissions::CAP_MANAGE_PASSKEY_SELF);
		}

		return user_can($viewer, Controller_Permissions::CAP_MANAGE_PASSKEY_OTHERS);
	}

	/**
	 * Returns whether the viewer may register a new passkey for the given user.
	 *
	 * Registration is restricted to the currently logged-in user's own account so the
	 * authenticator interaction always happens in the context of the account owner.
	 *
	 * @param \WP_User $viewer
	 * @param \WP_User $user
	 * @return bool
	 */
	public function can_register_passkeys($viewer, $user) {
		return $viewer instanceof \WP_User
			&& $viewer->ID === $user->ID
			&& $this->can_manage_passkeys($viewer, $user);
	}

	/**
	 * Returns the configured maximum number of passkeys that one user may store.
	 *
	 * @return int Positive per-user passkey limit.
	 */
	public function max_passkeys_per_user() {
		if (defined('WORDFENCE_LS_MAX_PASSKEYS_PER_USER')) {
			$configured = constant('WORDFENCE_LS_MAX_PASSKEYS_PER_USER');
			if ((is_int($configured) && $configured > 0) || (is_string($configured) && preg_match('/^[1-9][0-9]*$/D', $configured))) {
				$limit = (int) $configured;
				if ($limit > 0) {
					return $limit;
				}
			}
		}
		return self::DEFAULT_MAX_PASSKEYS_PER_USER;
	}

	/**
	 * Returns whether a loaded passkey collection has room for another credential.
	 *
	 * @param array $passkeys Existing passkey records.
	 * @return bool True when another passkey may be registered.
	 */
	public function has_passkey_capacity($passkeys) {
		return is_array($passkeys) && count($passkeys) < $this->max_passkeys_per_user();
	}

	/**
	 * Returns whether username/password authentication is enabled for the user while passkeys are registered.
	 *
	 * If the flag has never been saved, username/password authentication remains enabled by default.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	public function is_username_password_auth_enabled($user) {
		$stored = get_user_meta($user->ID, self::META_KEY_PASSWORD_AUTH_ENABLED, true);
		if ($stored === '') {
			return true;
		}
		return Utility_Number::truthyToBool($stored);
	}

	/**
	 * Returns whether a user's roles are configured to require passkeys.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	public function has_required_passkey_role($user) {
		foreach (Controller_Permissions::shared()->get_all_roles($user) as $role) {
			if (Controller_Settings::shared()->get_required_passkey_role_activation_time($role) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns whether the username/password authentication toggle may be changed for the user.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	public function can_change_username_password_auth($user) {
		return !$this->has_required_passkey_role($user);
	}

	/**
	 * Returns the effective username/password authentication setting for the user.
	 *
	 * Username/password authentication remains available while passkeys are globally disabled. While enabled, a
	 * required passkey role effectively disables password authentication regardless of role availability or the stored
	 * preference. For a non-required role, password authentication remains available when passkeys are unavailable and
	 * may otherwise be disabled only after the user has registered a passkey.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	public function is_effective_username_password_auth_enabled($user) {
		if (!Controller_Settings::shared()->are_passkeys_enabled()) {
			return true;
		}
		if (!$this->can_change_username_password_auth($user)) {
			return false;
		}
		if (!Controller_Users::shared()->can_manage_passkey($user)) {
			return true;
		}
		if ($this->is_username_password_auth_enabled($user)) {
			return true;
		}
		return !Controller_Users::shared()->has_registered_passkey($user);
	}

	/**
	 * Saves whether username/password authentication is enabled for the user while passkeys are registered.
	 *
	 * @param \WP_User $user
	 * @param bool $enabled
	 * @return bool
	 */
	public function set_username_password_auth_enabled($user, $enabled) {
		$before = $this->is_username_password_auth_enabled($user);
		$after = (bool) $enabled;
		if (!$after && $this->can_change_username_password_auth($user) && !Controller_Users::shared()->has_registered_passkey($user)) {
			$after = true;
		}
		if ($before === $after) {
			return true;
		}
		$result = update_user_meta($user->ID, self::META_KEY_PASSWORD_AUTH_ENABLED, $after ? 1 : 0) !== false;
		if ($result) {
			/**
			 * Fires when the per-user username/password authentication setting for passkeys changes.
			 *
			 * @since 2.0.0
			 *
			 * @param \WP_User $user The user.
			 * @param bool $before The previous value.
			 * @param bool $after The new value.
			 */
			do_action('wordfence_ls_passkey_password_auth_toggled', $user, $before, $after);
		}
		return $result;
	}

	/**
	 * Returns whether username/password authentication should be blocked for the user because passkeys are active
	 * and password auth has been disabled for the account.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	public function should_block_username_password_auth($user) {
		return Controller_Users::shared()->has_passkey_active($user) && !$this->is_effective_username_password_auth_enabled($user);
	}

	public function get_passkeys($user) {
		$this->require_schema();

		global $wpdb;
		$table = Controller_DB::shared()->passkeys;
		return $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$table}` WHERE `user_id` = %d ORDER BY `ctime` ASC, `id` ASC", $user->ID), ARRAY_A);
	}

	public function any_passkeys_active() {
		$this->require_schema();

		return Controller_Users::shared()->any_passkey_active();
	}

	private function refresh_public_suffix_list_before_first_passkey() {
		if (!$this->any_passkeys_active()) {
			Utility_URL::fetch_and_cache_public_suffix_list();
		}
	}

	/**
	 * Applies the per-user rate limit for starting passkey registration.
	 *
	 * @param \WP_User $user User starting registration.
	 * @return true|\WP_Error True when allowed, or a rate-limit error.
	 */
	public function consume_begin_registration_rate_limit($user) {
		return $this->consume_registration_rate_limit($user, 'begin');
	}

	/**
	 * Applies the per-user rate limit for finishing passkey registration.
	 *
	 * @param \WP_User $user User finishing registration.
	 * @return true|\WP_Error True when allowed, or a rate-limit error.
	 */
	public function consume_finish_registration_rate_limit($user) {
		return $this->consume_registration_rate_limit($user, 'finish');
	}

	/**
	 * Consumes a token from a per-user registration-stage bucket.
	 *
	 * @param \WP_User $user User performing registration.
	 * @param string $stage Registration stage identifier.
	 * @return true|\WP_Error True when allowed, or a rate-limit error.
	 */
	private function consume_registration_rate_limit($user, $stage) {
		$userId = $user instanceof \WP_User ? (int) $user->ID : 0;
		$tokenBucket = new Model_TokenBucket(
			'passkey-registration-' . $stage . ':' . $userId,
			self::REGISTRATION_RATE_LIMIT_CAPACITY,
			1 / Model_TokenBucket::MINUTE
		);
		if ($tokenBucket->consume(1)) {
			return true;
		}

		return new \WP_Error(
			'wfls_passkey_registration_rate_limited',
			__('Too many passkey registration requests were submitted for this account. Please wait a moment and try again.', 'wordfence')
		);
	}

	public function begin_registration($user, $label = '') {
		$this->require_schema();
		$rateLimit = $this->consume_begin_registration_rate_limit($user);
		if (is_wp_error($rateLimit)) {
			return $rateLimit;
		}
		$passkeys = $this->get_passkeys($user);
		if (!$this->has_passkey_capacity($passkeys)) {
			return $this->passkey_limit_error();
		}
		$this->refresh_public_suffix_list_before_first_passkey();

		$rpId = $this->get_rp_id();
		if (empty($rpId)) {
			return new \WP_Error('wfls_passkey_rp_id_missing', __('Unable to determine the site hostname for passkey registration.', 'wordfence'));
		}
		$label = $this->normalize_label($label);
		if (is_wp_error($label)) {
			return $label;
		}

		try {
			$challenge = Model_JWT::base64url_encode(Model_Crypto::random_bytes(32, false));
			$jti = Model_JWT::base64url_encode(Model_Crypto::random_bytes(32, false));
			$userHandle = $this->get_user_handle($user);
			$encodedUserHandle = Model_JWT::base64url_encode($userHandle);
		}
		catch (\RuntimeException $e) {
			return new \WP_Error('wfls_passkey_registration_prepare_failed', __('Unable to prepare the passkey registration request. Please try again.', 'wordfence'));
		}
		$registrationBinding = $this->registration_token_binding($user->ID);
		if ($registrationBinding === null || !set_transient($this->get_registration_token_transient_key($jti), $registrationBinding, self::REGISTRATION_TOKEN_DURATION)) {
			return new \WP_Error('wfls_passkey_registration_prepare_failed', __('Unable to prepare the passkey registration request. Please try again.', 'wordfence'));
		}
		$token = new Model_JWT(array(
			'type' => 'passkey-registration',
			'jti' => $jti,
			'user_id' => $user->ID,
			'challenge' => $challenge,
			'rp_id' => $rpId,
			'user_handle' => $encodedUserHandle,
		), Controller_Time::time() + self::REGISTRATION_TOKEN_DURATION);

		$excludeCredentials = array();
		foreach ($passkeys as $passkey) {
			$excludeCredentials[] = array(
				'type' => 'public-key',
				'id' => Model_JWT::base64url_encode($passkey['credential_id']),
				'transports' => $this->decode_transports($passkey['transports']),
			);
		}

		$displayName = $user->display_name;
		if (empty($displayName)) {
			$displayName = $user->user_login;
		}
		$rpName = get_bloginfo('name', 'raw');
		if (!is_string($rpName) || $rpName === '') {
			$rpName = $rpId;
		}

		return array(
			'token' => (string) $token,
			'options' => array(
				'challenge' => $challenge,
				'rp' => array(
					'name' => $rpName,
					'id' => $rpId,
				),
				'user' => array(
					'id' => $encodedUserHandle,
					'name' => $user->user_login,
					'displayName' => $displayName,
				),
				'pubKeyCredParams' => $this->supported_public_key_credential_parameters(),
				'timeout' => 60000,
				'attestation' => 'none',
				'authenticatorSelection' => array(
					'residentKey' => 'required',
					'requireResidentKey' => true,
					'userVerification' => 'required',
				),
				'excludeCredentials' => $excludeCredentials,
				'extensions' => array(
					'credProps' => true,
				),
			),
			'label' => $label,
		);
	}

	public function finish_registration($user, $token, $credential, $label = '', $uiStyleContext = null) {
		$this->require_schema();
		$rateLimit = $this->consume_finish_registration_rate_limit($user);
		if (is_wp_error($rateLimit)) {
			return $rateLimit;
		}

		$jwt = Model_JWT::decode_jwt($token);
		if (!$jwt || !isset($jwt->payload['type']) || !isset($jwt->payload['jti']) || !isset($jwt->payload['user_id']) || !isset($jwt->payload['challenge']) || !isset($jwt->payload['rp_id']) || !isset($jwt->payload['user_handle']) || !is_string($jwt->payload['type']) || !is_string($jwt->payload['jti']) || $jwt->payload['jti'] === '' || !is_string($jwt->payload['challenge']) || !is_string($jwt->payload['rp_id']) || !is_string($jwt->payload['user_handle'])) {
			return new \WP_Error('wfls_passkey_registration_token_invalid', __('The passkey registration request is invalid or expired. Please try again.', 'wordfence'));
		}
		if ($jwt->payload['type'] !== 'passkey-registration' || (int) $jwt->payload['user_id'] !== (int) $user->ID) {
			return new \WP_Error('wfls_passkey_registration_token_mismatch', __('The passkey registration request does not match the selected user.', 'wordfence'));
		}
		$registrationUserHandle = $this->decode_user_handle($jwt->payload['user_handle']);
		if ($registrationUserHandle === null) {
			return new \WP_Error('wfls_passkey_registration_token_invalid', __('The passkey registration request is invalid or expired. Please try again.', 'wordfence'));
		}

		if (!is_array($credential) || !$this->has_string_value($credential, 'id') || !$this->has_string_value($credential, 'rawId') || !$this->has_string_value($credential, 'type') || !isset($credential['response']) || !is_array($credential['response'])) {
			return new \WP_Error('wfls_passkey_registration_payload_invalid', __('The passkey registration data was incomplete.', 'wordfence'));
		}
		if ($credential['type'] !== 'public-key') {
			return new \WP_Error('wfls_passkey_registration_type_invalid', __('The browser returned an unexpected credential type for the passkey.', 'wordfence'));
		}
		if (!$this->has_string_value($credential['response'], 'clientDataJSON') || !$this->has_string_value($credential['response'], 'attestationObject')) {
			return new \WP_Error('wfls_passkey_registration_response_invalid', __('The passkey registration response was incomplete.', 'wordfence'));
		}

		$clientDataJSON = Model_JWT::base64url_decode($credential['response']['clientDataJSON']);
		$attestationObject = Model_JWT::base64url_decode($credential['response']['attestationObject']);
		$rawId = Model_JWT::base64url_decode($credential['rawId']);
		if ($clientDataJSON === false || $attestationObject === false || $rawId === false) {
			return new \WP_Error('wfls_passkey_registration_decode_failed', __('Unable to decode the passkey registration payload.', 'wordfence'));
		}
		$rawIdLength = Model_Crypto::strlen($rawId);
		if ($rawIdLength < 1 || $rawIdLength > self::MAX_CREDENTIAL_ID_BYTES) {
			return new \WP_Error('wfls_passkey_credential_length_invalid', __('The browser returned an invalid passkey credential identifier length.', 'wordfence'));
		}
		if (Model_Crypto::strlen($attestationObject) > self::MAX_REGISTRATION_ATTESTATION_BYTES) {
			return new \WP_Error('wfls_passkey_attestation_too_large', __('The browser returned passkey attestation data that was too large.', 'wordfence'));
		}

		$clientData = json_decode($clientDataJSON, true);
		if (!is_array($clientData) || !$this->has_string_value($clientData, 'type') || !$this->has_string_value($clientData, 'challenge') || !$this->has_string_value($clientData, 'origin')) {
			return new \WP_Error('wfls_passkey_client_data_invalid', __('The browser returned invalid passkey registration metadata.', 'wordfence'));
		}
		if ($clientData['type'] !== 'webauthn.create') {
			return new \WP_Error('wfls_passkey_client_type_invalid', __('The browser returned an unexpected passkey registration type.', 'wordfence'));
		}
		$httpOriginError = $this->http_origin_error($clientData, 'registration');
		if (is_wp_error($httpOriginError)) {
			return $httpOriginError;
		}
		$clientDataContext = $this->validate_client_data_context($clientData, $jwt->payload['rp_id'], 'wfls_passkey_cross_origin_invalid', __('The passkey registration was returned from an unsupported cross-origin context.', 'wordfence'));
		if (is_wp_error($clientDataContext)) {
			return $clientDataContext;
		}
		if (!hash_equals($jwt->payload['challenge'], $clientData['challenge'])) {
			return new \WP_Error('wfls_passkey_challenge_mismatch', __('The passkey registration challenge could not be verified. Please try again.', 'wordfence'));
		}
		if (!$this->is_allowed_origin_for_rp($clientData['origin'], $jwt->payload['rp_id'])) {
			return new \WP_Error('wfls_passkey_origin_invalid', __('The passkey registration was returned from an unexpected origin. The hostname you are attempting to register the passkey on may need to be added to the Allowed Passkey Hostnames list by an administrator.', 'wordfence'));
		}

		try {
			$authData = $this->parse_registration_attestation($attestationObject, $jwt->payload['rp_id']);
			if (is_wp_error($authData)) {
				return $authData;
			}
		}
		catch (\UnexpectedValueException $e) {
			return new \WP_Error('wfls_passkey_decode_failed', __('The browser returned passkey data in an unsupported format.', 'wordfence'));
		}

		if (!hash_equals($authData['credential_id'], $rawId)) {
			return new \WP_Error('wfls_passkey_credential_mismatch', __('The credential returned by the browser did not match the registered passkey identifier.', 'wordfence'));
		}
		if (
			isset($credential['clientExtensionResults']) &&
			is_array($credential['clientExtensionResults']) &&
			isset($credential['clientExtensionResults']['credProps']) &&
			is_array($credential['clientExtensionResults']['credProps']) &&
			array_key_exists('rk', $credential['clientExtensionResults']['credProps']) &&
			!Utility_Number::truthyToBool($credential['clientExtensionResults']['credProps']['rk'])
		) {
			return new \WP_Error('wfls_passkey_not_discoverable', __('The browser did not create a discoverable passkey for usernameless sign-in. Please try again with a passkey provider that supports discoverable credentials.', 'wordfence'));
		}

		global $wpdb;
		$table = Controller_DB::shared()->passkeys;
		$label = $this->normalize_label($label);
		if (is_wp_error($label)) {
			return $label;
		}
		$transports = array();
		if (isset($credential['response']['transports']) && is_array($credential['response']['transports'])) {
			$transports = $credential['response']['transports'];
		}
		$registrationBinding = $this->registration_token_binding($user->ID);
		if ($registrationBinding === null) {
			return new \WP_Error('wfls_passkey_registration_token_invalid', __('The passkey registration request is invalid or expired. Please try again.', 'wordfence'));
		}
		$registrationTokenLock = new Utility_DatabaseLock(Controller_DB::shared(), 'passkey-registration-token:' . md5($jwt->payload['jti']), 1);
		try {
			$registrationTokenLock->acquire();
			$registrationTokenKey = $this->get_registration_token_transient_key($jwt->payload['jti']);
			$storedRegistrationBinding = get_transient($registrationTokenKey);
			if (!is_string($storedRegistrationBinding) || !hash_equals($storedRegistrationBinding, $registrationBinding) || !delete_transient($registrationTokenKey)) {
				return new \WP_Error('wfls_passkey_registration_token_invalid', __('The passkey registration request is invalid or expired. Please try again.', 'wordfence'));
			}
		}
		catch (\RuntimeException $e) {
			return new \WP_Error('wfls_passkey_registration_busy', __('Another passkey registration is already being completed for this account. Please try again.', 'wordfence'));
		}
		finally {
			$registrationTokenLock->release();
		}
		$now = Controller_Time::time();
		$result = $this->insert_passkey_record_with_limit($table, $user, $authData['credential_id'], $authData['public_key'], $authData['sign_count'], $transports, $label, $registrationUserHandle, $now);
		if (is_wp_error($result)) {
			return $result;
		}
		if ($result === false) {
			return new \WP_Error('wfls_passkey_insert_failed', __('Unable to save the new passkey. It may already be registered.', 'wordfence'));
		}
		if ((int) $result === 0) {
			return new \WP_Error('wfls_passkey_insert_duplicate', __('This passkey is already registered for this site.', 'wordfence'));
		}
		Controller_Users::shared()->clear_passkey_active_cache($user->ID);
		Controller_Settings::shared()->set(Controller_Settings::OPTION_LAST_PASSKEY_RP, (string) $jwt->payload['rp_id']);
		Controller_Settings::shared()->set_initial_passkey_allowed_hostnames((string) $jwt->payload['rp_id'], $clientData['origin']);

		$passkey = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `credential_id_hash` = UNHEX(%s) AND `credential_id` = UNHEX(%s) LIMIT 1",
				bin2hex(hash('sha256', $authData['credential_id'], true)),
				bin2hex($authData['credential_id'])
			),
			ARRAY_A
		);
		/**
		 * Fires when a passkey is registered for a user.
		 *
		 * @since 2.0.0
		 *
		 * @param \WP_User $user The user.
		 * @param array|null $passkey The passkey row as stored in the database.
		 */
		do_action('wordfence_ls_passkey_registered', $user, $passkey);
		$siteName = get_bloginfo('name', 'raw');
		$manageURL = (is_multisite() && is_super_admin($user->ID))
			? network_admin_url('admin.php?page=WFLS#top#passkey')
			: admin_url('admin.php?page=WFLS#top#passkey');
		$notification = Model_View::create('email/passkey-added', array(
			'siteName' => $siteName,
			'passkeyLabel' => $label,
			'registeredAt' => $now,
			'ip' => Model_Request::current()->ip(),
			'manageURL' => $manageURL,
		));
		wp_mail(
			$user->user_email,
			sprintf(/* translators: Site name. */ __('[%s] Passkey Added', 'wordfence'), $siteName),
			$notification->render(),
			array('Content-Type: text/html')
		);
		$itemViewData = array('passkey' => $passkey);
		if ($uiStyleContext !== null) {
			$itemViewData['uiStyleContext'] = Controller_WordfenceLS::normalize_ui_style_context($uiStyleContext);
		}
		return array(
			'passkey' => $passkey,
			'item_html' => Model_View::create('passkey/item', $itemViewData)->render(),
		);
	}

	public function remove_passkey($user, $passkeyId) {
		$this->require_schema();

		global $wpdb;
		$table = Controller_DB::shared()->passkeys;
		$existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE `id` = %d AND `user_id` = %d LIMIT 1", $passkeyId, $user->ID), ARRAY_A);
		if (!$existing) {
			return new \WP_Error('wfls_passkey_missing', __('The requested passkey does not exist for this account.', 'wordfence'));
		}
		$restorePasswordAuth = false;
		if (!$this->is_username_password_auth_enabled($user) && $this->can_change_username_password_auth($user)) {
			$passkeyCount = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$table}` WHERE `user_id` = %d", $user->ID));
			$restorePasswordAuth = $passkeyCount <= 1;
		}

		$deleted = $wpdb->delete($table, array(
			'id' => $passkeyId,
			'user_id' => $user->ID,
		), array('%d', '%d'));
		if ($deleted === false) {
			return new \WP_Error('wfls_passkey_remove_failed', __('Unable to remove the passkey. Please try again.', 'wordfence'));
		}
		Controller_Users::shared()->clear_passkey_active_cache($user->ID);
		if ($restorePasswordAuth) {
			$this->set_username_password_auth_enabled($user, true);
		}
		if (!$this->any_passkeys_active()) {
			Controller_Settings::shared()->set(Controller_Settings::OPTION_LAST_PASSKEY_RP, '');
		}
		/**
		 * Fires when a passkey is removed from a user.
		 *
		 * @since 2.0.0
		 *
		 * @param \WP_User $user The user.
		 * @param array $passkey The passkey row as it existed before deletion.
		 */
		do_action('wordfence_ls_passkey_removed', $user, $existing);

		return true;
	}

	public function begin_login($allowSameRelyingPartyFrame = false) {
		$this->require_schema();

		$rpId = $this->get_rp_id();
		if (empty($rpId)) {
			return new \WP_Error('wfls_passkey_rp_id_missing', __('Unable to determine the site hostname for passkey login.', 'wordfence'));
		}

		try {
			$challenge = Model_JWT::base64url_encode(Model_Crypto::random_bytes(32, false));
			$jti = Model_JWT::base64url_encode(Model_Crypto::random_bytes(32, false));
		}
		catch (\RuntimeException $e) {
			return new \WP_Error('wfls_passkey_login_prepare_failed', __('Unable to prepare the passkey login request. Please try again.', 'wordfence'));
		}
		if (!$this->remember_login_token($jti)) {
			return new \WP_Error('wfls_passkey_login_prepare_failed', __('Unable to prepare the passkey login request. Please try again.', 'wordfence'));
		}
		$token = new Model_JWT(array(
			'type' => 'passkey-login',
			'jti' => $jti,
			'challenge' => $challenge,
			'rp_id' => $rpId,
			'allow_same_rp_frame' => (bool) $allowSameRelyingPartyFrame,
		), Controller_Time::time() + self::LOGIN_TOKEN_DURATION);

		return array(
			'token' => (string) $token,
			'options' => array(
				'challenge' => $challenge,
				'rpId' => $rpId,
				'timeout' => 60000,
				'userVerification' => 'required',
			),
		);
	}

	/**
	 * Applies a lightweight unauthenticated rate limit to passkey login initiation requests.
	 *
	 * Beginning a passkey login creates short-lived server-side token state, so all login-initiation entry points should
	 * consume this bucket before calling begin_login().
	 *
	 * @param string|null $ip Optional client IP to use for the rate-limit bucket.
	 * @return true|\WP_Error
	 */
	public function consume_begin_login_rate_limit($ip = null) {
		$packedIp = is_string($ip) ? Model_IP::inet_pton($ip) : false;
		if ($packedIp === false) {
			$ip = Model_Request::current()->ip();
			$packedIp = is_string($ip) ? Model_IP::inet_pton($ip) : false;
		}
		$identifier = $packedIp !== false ? bin2hex($packedIp) : md5((string) $ip);
		$tokenBucket = new Model_TokenBucket(
			'passkey-login-start:' . $identifier,
			10,
			1 / (6 * Model_TokenBucket::SECOND)
		);
		if ($tokenBucket->consume(1)) {
			return true;
		}

		return new \WP_Error(
			'wfls_passkey_login_rate_limited',
			__('Too many passkey login requests were started from this address. Please wait a moment and try again.', 'wordfence')
		);
	}

	/**
	 * Applies a lightweight unauthenticated rate limit to passkey login finish requests.
	 *
	 * Finishing a passkey login parses attacker-controlled WebAuthn data, so all login-finish entry points should
	 * consume this bucket before calling finish_login().
	 *
	 * @param string|null $ip Optional client IP to use for the rate-limit bucket.
	 * @return true|\WP_Error
	 */
	public function consume_finish_login_rate_limit($ip = null) {
		$packedIp = is_string($ip) ? Model_IP::inet_pton($ip) : false;
		if ($packedIp === false) {
			$ip = Model_Request::current()->ip();
			$packedIp = is_string($ip) ? Model_IP::inet_pton($ip) : false;
		}
		$identifier = $packedIp !== false ? bin2hex($packedIp) : md5((string) $ip);
		$tokenBucket = new Model_TokenBucket(
			'passkey-login-finish:' . $identifier,
			30,
			1 / Model_TokenBucket::SECOND
		);
		if ($tokenBucket->consume(1)) {
			return true;
		}

		return new \WP_Error(
			'wfls_passkey_login_finish_rate_limited',
			__('Too many passkey login responses were submitted from this address. Please wait a moment and try again.', 'wordfence')
		);
	}

	public function finish_login($token, $credential, $remember = false) {
		$this->require_schema();

		$jwt = Model_JWT::decode_jwt($token);
		if (!$jwt || !isset($jwt->payload['type']) || !isset($jwt->payload['jti']) || !isset($jwt->payload['challenge']) || !isset($jwt->payload['rp_id']) || !is_string($jwt->payload['type']) || !is_string($jwt->payload['jti']) || !is_string($jwt->payload['challenge']) || !is_string($jwt->payload['rp_id'])) {
			return new \WP_Error('wfls_passkey_login_token_invalid', __('The passkey login request is invalid or expired. Please try again.', 'wordfence'));
		}
		if ($jwt->payload['type'] !== 'passkey-login') {
			return new \WP_Error('wfls_passkey_login_token_mismatch', __('The passkey login request is invalid. Please try again.', 'wordfence'));
		}
		if (!$this->consume_login_token($jwt->payload['jti'])) {
			return new \WP_Error('wfls_passkey_login_token_used', __('The passkey login request is invalid or expired. Please try again.', 'wordfence'));
		}

		if (!is_array($credential) || !$this->has_string_value($credential, 'rawId') || !$this->has_string_value($credential, 'type') || !isset($credential['response']) || !is_array($credential['response'])) {
			return new \WP_Error('wfls_passkey_login_payload_invalid', __('The passkey login data was incomplete.', 'wordfence'));
		}
		if ($credential['type'] !== 'public-key') {
			return new \WP_Error('wfls_passkey_login_type_invalid', __('The browser returned an unexpected credential type for the passkey.', 'wordfence'));
		}
		if (!$this->has_string_value($credential['response'], 'clientDataJSON') || !$this->has_string_value($credential['response'], 'authenticatorData') || !$this->has_string_value($credential['response'], 'signature')) {
			return new \WP_Error('wfls_passkey_login_response_invalid', __('The passkey login response was incomplete.', 'wordfence'));
		}
		if (!$this->has_string_value($credential['response'], 'userHandle') || $credential['response']['userHandle'] === '') {
			return new \WP_Error('wfls_passkey_login_user_handle_invalid', __('The passkey login did not identify an account. Please try again.', 'wordfence'));
		}

		$rawId = Model_JWT::base64url_decode($credential['rawId']);
		$clientDataJSON = Model_JWT::base64url_decode($credential['response']['clientDataJSON']);
		$authenticatorData = Model_JWT::base64url_decode($credential['response']['authenticatorData']);
		$signature = Model_JWT::base64url_decode($credential['response']['signature']);
		$userHandle = $this->decode_user_handle($credential['response']['userHandle']);
		if ($rawId === false || $clientDataJSON === false || $authenticatorData === false || $signature === false) {
			return new \WP_Error('wfls_passkey_login_decode_failed', __('Unable to decode the passkey login payload.', 'wordfence'));
		}
		if ($userHandle === null) {
			return new \WP_Error('wfls_passkey_login_user_handle_invalid', __('The passkey login did not identify an account. Please try again.', 'wordfence'));
		}

		$clientData = json_decode($clientDataJSON, true);
		if (!is_array($clientData) || !$this->has_string_value($clientData, 'type') || !$this->has_string_value($clientData, 'challenge') || !$this->has_string_value($clientData, 'origin')) {
			return new \WP_Error('wfls_passkey_login_client_data_invalid', __('The browser returned invalid passkey login metadata.', 'wordfence'));
		}
		if ($clientData['type'] !== 'webauthn.get') {
			return new \WP_Error('wfls_passkey_login_client_type_invalid', __('The browser returned an unexpected passkey login type.', 'wordfence'));
		}
		$httpOriginError = $this->http_origin_error($clientData, 'login');
		if (is_wp_error($httpOriginError)) {
			return $httpOriginError;
		}
		$clientDataContext = $this->validate_client_data_context($clientData, $jwt->payload['rp_id'], 'wfls_passkey_login_cross_origin_invalid', __('The passkey login was returned from an unsupported cross-origin context.', 'wordfence'), !empty($jwt->payload['allow_same_rp_frame']));
		if (is_wp_error($clientDataContext)) {
			return $clientDataContext;
		}
		if (!hash_equals($jwt->payload['challenge'], $clientData['challenge'])) {
			return new \WP_Error('wfls_passkey_login_challenge_mismatch', __('The passkey login challenge could not be verified. Please try again.', 'wordfence'));
		}
		if (!$this->is_allowed_origin_for_rp($clientData['origin'], $jwt->payload['rp_id'])) {
			return new \WP_Error('wfls_passkey_login_origin_invalid', __('The passkey login was returned from an unexpected origin.', 'wordfence'));
		}

		$passkey = $this->get_passkey_by_credential_id($rawId);
		if (!$passkey) {
			return new \WP_Error('wfls_passkey_login_missing', __('The selected passkey is not registered for this site.', 'wordfence'));
		}

		$assertion = $this->parse_assertion_authenticator_data($authenticatorData, $jwt->payload['rp_id']);
		if (is_wp_error($assertion)) {
			return $assertion;
		}

		$signatureData = $authenticatorData . hash('sha256', $clientDataJSON, true);
		$verified = $this->verify_signature($passkey['public_key'], $signatureData, $signature);
		if (is_wp_error($verified)) {
			return $verified;
		}
		if (!$verified) {
			return new \WP_Error('wfls_passkey_login_signature_invalid', __('The passkey signature could not be verified.', 'wordfence'));
		}

		$userID = isset($passkey['user_id']) ? (int) $passkey['user_id'] : 0;
		$user = new \WP_User($userID);
		if (!$user->exists()) {
			$this->delete_orphaned_passkey($passkey);
			return new \WP_Error('wfls_passkey_login_user_missing', __('The account associated with this passkey no longer exists.', 'wordfence'));
		}

		$rowHandle = isset($passkey['user_handle']) && is_string($passkey['user_handle']) ? $passkey['user_handle'] : '';
		$rowHandleLength = Model_Crypto::strlen($rowHandle);
		$storedHandle = $this->get_stored_user_handle($user);
		if ($rowHandleLength < 1 || $rowHandleLength > self::MAX_USER_HANDLE_BYTES || $storedHandle === null || !hash_equals($rowHandle, $storedHandle)) {
			$this->delete_orphaned_passkey($passkey);
			return new \WP_Error('wfls_passkey_login_credential_orphaned', __('The selected passkey is no longer associated with this account. Please sign in another way and register it again.', 'wordfence'));
		}
		if (!hash_equals($rowHandle, $userHandle) || !hash_equals($storedHandle, $userHandle)) {
			return new \WP_Error('wfls_passkey_login_user_handle_mismatch', __('The selected passkey did not match the expected account.', 'wordfence'));
		}
		if (!Controller_Users::shared()->can_manage_passkey($user)) {
			return new \WP_Error('wfls_passkey_login_user_unavailable', __('This account is not allowed to use passkey login.', 'wordfence'));
		}

		$signCount = $this->validate_passkey_sign_count((int) $passkey['sign_count'], (int) $assertion['sign_count']);
		if (is_wp_error($signCount)) {
			return $signCount;
		}

		return array(
			'user' => $user,
			'passkey' => $passkey,
			'sign_count' => $assertion['sign_count'],
			'remember' => (bool) $remember,
		);
	}

	/**
	 * Authenticates a passkey login result through the normal WordPress authentication pipeline.
	 *
	 * @param array $result Result returned by finish_login().
	 * @param bool $establishSession Whether to set auth cookies on the current response.
	 * @return \WP_User|\WP_Error
	 */
	public function authenticate_finished_login($result, $establishSession = true) {
		if (!is_array($result) || !isset($result['user']) || !($result['user'] instanceof \WP_User) || !isset($result['passkey']) || !is_array($result['passkey'])) {
			return new \WP_Error('wfls_passkey_login_result_invalid', __('The passkey login could not be completed. Please try again.', 'wordfence'));
		}

		try {
			$credentials = $this->begin_verified_authentication(
				$result['user'],
				$result['passkey'],
				isset($result['sign_count']) ? (int) $result['sign_count'] : 0,
				!empty($result['remember'])
			);
		}
		catch (\RuntimeException $e) {
			$this->clear_verified_authentication();
			return new \WP_Error('wfls_passkey_login_prepare_failed', __('Unable to prepare the passkey login request. Please try again.', 'wordfence'));
		}
		$username = $credentials['username'];
		$password = $credentials['password'];

		$authenticatedUser = wp_authenticate($username, $password);
		if (is_wp_error($authenticatedUser)) {
			$this->clear_verified_authentication();
			return $authenticatedUser;
		}
		if (!$authenticatedUser instanceof \WP_User || (int) $authenticatedUser->ID !== (int) $result['user']->ID) {
			$this->clear_verified_authentication();
			return new \WP_Error('wfls_passkey_authenticated_user_mismatch', __('The passkey login could not be completed. Please try again.', 'wordfence'));
		}

		return $this->finalize_verified_authentication($authenticatedUser, $establishSession);
	}

	/**
	 * Completes passkey assertion verification and authenticates the associated user.
	 *
	 * @param string $token
	 * @param array $credential
	 * @param bool $remember
	 * @param bool $establishSession Whether to set auth cookies on the current response.
	 * @param string|null $ip Optional client IP to use for rate-limiting and failure context.
	 * @return \WP_User|\WP_Error
	 */
	public function finish_and_authenticate_login($token, $credential, $remember = false, $establishSession = true, $ip = null) {
		$contextIp = $this->passkey_login_context_ip($ip);
		$rateLimit = $this->consume_finish_login_rate_limit($contextIp);
		if (is_wp_error($rateLimit)) {
			$this->fire_passkey_login_failed_action($rateLimit, '', null, null, $contextIp, false);
			return $rateLimit;
		}

		$result = $this->finish_login($token, $credential, $remember);
		if (is_wp_error($result)) {
			$this->fire_passkey_login_failed_action($result, $token, $credential, null, $contextIp);
			return $result;
		}

		$authenticated = $this->authenticate_finished_login($result, $establishSession);
		if (is_wp_error($authenticated)) {
			$this->fire_passkey_login_failed_action($authenticated, $token, $credential, $result, $contextIp);
		}
		return $authenticated;
	}

	/**
	 * Early authenticate filter that resolves a request-scoped verified passkey login into the associated user.
	 *
	 * This allows passkey logins to participate in the normal WordPress authentication pipeline, including third-party
	 * filters that hook `authenticate`.
	 *
	 * @param \WP_User|\WP_Error|null $user
	 * @param string $username
	 * @param string $password
	 * @return \WP_User|\WP_Error|null
	 */
	public function _authenticate_verified_passkey($user, $username, $password) {
		if (is_wp_error($user) || !$this->is_verified_authentication_request($username, $password)) {
			return $user;
		}

		$verified = self::$_verifiedAuthentication;
		if (
			!is_array($verified) ||
			!isset($verified['user']) ||
			!isset($verified['username']) ||
			!isset($verified['password']) ||
			!($verified['user'] instanceof \WP_User) ||
			!is_string($verified['username']) ||
			!is_string($verified['password']) ||
			!$verified['user']->exists()
		) {
			return new \WP_Error('wfls_passkey_verified_auth_invalid', __('The passkey login request is invalid or expired. Please try again.', 'wordfence'));
		}

		return $verified['user'];
	}

	/**
	 * Prepares a verified passkey login to be resolved through the WordPress authenticate filter.
	 *
	 * @param \WP_User $user
	 * @param array $passkey
	 * @param int $signCount
	 * @param bool $remember
	 * @return array{username:string,password:string}
	 */
	public function begin_verified_authentication($user, $passkey, $signCount, $remember = false) {
		$username = (string) $user->user_login;
		$password = Model_JWT::base64url_encode(Model_Crypto::random_bytes(32, false));
		self::$_verifiedAuthentication = array(
			'user' => $user,
			'username' => $username,
			'password' => $password,
			'passkey' => $passkey,
			'sign_count' => (int) $signCount,
			'remember' => (bool) $remember,
		);

		return array(
			'username' => $username,
			'password' => $password,
		);
	}

	/**
	 * Returns whether a verified passkey authentication context is currently active for this request.
	 *
	 * @return bool
	 */
	public function has_verified_authentication() {
		return is_array(self::$_verifiedAuthentication)
			&& isset(self::$_verifiedAuthentication['user'])
			&& isset(self::$_verifiedAuthentication['username'])
			&& isset(self::$_verifiedAuthentication['password'])
			&& self::$_verifiedAuthentication['user'] instanceof \WP_User
			&& is_string(self::$_verifiedAuthentication['username'])
			&& is_string(self::$_verifiedAuthentication['password'])
			&& self::$_verifiedAuthentication['user']->exists();
	}

	/**
	 * Returns whether the provided credentials correspond to the current request's verified passkey login context.
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	public function is_verified_authentication_request($username, $password) {
		if (!is_string($username) || !is_string($password) || !$this->has_verified_authentication()) {
			return false;
		}

		return $username === self::$_verifiedAuthentication['username']
			&& hash_equals(self::$_verifiedAuthentication['password'], $password);
	}

	/**
	 * Finalizes a verified passkey authentication after `wp_authenticate()` has succeeded.
	 *
	 * @param \WP_User $user
	 * @param bool $establishSession Whether to set auth cookies on the current response.
	 * @return \WP_User|\WP_Error
	 */
	public function finalize_verified_authentication($user, $establishSession = true) {
		$verified = self::$_verifiedAuthentication;
		$this->clear_verified_authentication();

		if (
			!is_array($verified) ||
			!isset($verified['user']) ||
			!isset($verified['passkey']) ||
			!isset($verified['sign_count']) ||
			!($verified['user'] instanceof \WP_User) ||
			!is_array($verified['passkey']) ||
			!($user instanceof \WP_User) ||
			(int) $verified['user']->ID !== (int) $user->ID
		) {
			return new \WP_Error('wfls_passkey_verified_auth_missing', __('The passkey login request is invalid or expired. Please try again.', 'wordfence'));
		}

		$updated = $this->update_passkey_usage($verified['passkey'], (int) $verified['sign_count']);
		if (is_wp_error($updated)) {
			return $updated;
		}

		$this->fire_passkey_login_succeeded_action($user, $verified['passkey']);

		if ($establishSession) {
			$this->establish_login_session($user, !empty($verified['remember']));
		}
		return $user;
	}

	/**
	 * Clears any request-scoped verified passkey authentication context.
	 *
	 * @return void
	 */
	public function clear_verified_authentication() {
		self::$_verifiedAuthentication = null;
	}

	/**
	 * Fires the passkey-login success integration hook with sanitized context.
	 *
	 * @param \WP_User $user Authenticated user.
	 * @param array $passkey Passkey record used for authentication.
	 * @return void
	 */
	private function fire_passkey_login_succeeded_action($user, $passkey) {
		$context = array(
			'user_id' => (int) $user->ID,
			'username' => $user->user_login,
		);
		if (is_array($passkey)) {
			if (isset($passkey['id'])) {
				$context['passkey_id'] = (int) $passkey['id'];
			}
			if (isset($passkey['credential_id']) && is_string($passkey['credential_id'])) {
				$context['credential_id_sha256'] = hash('sha256', $passkey['credential_id']);
			}
		}

		/**
		 * Fires when a server-side passkey login attempt succeeds.
		 *
		 * The context is sanitized for logging/integration use and must not include raw WebAuthn assertions, signatures,
		 * login tokens, client data JSON, authenticator data, or credential IDs.
		 *
		 * @param \WP_User $user Authenticated user.
		 * @param array $context Sanitized success context.
		 */
		do_action('wordfence_ls_passkey_login_succeeded', $user, $context);
	}

	/**
	 * Fires the passkey-login failure integration hook with sanitized context.
	 *
	 * @param \WP_Error $error Failure returned by passkey login.
	 * @param string $token Submitted login token.
	 * @param mixed $credential Submitted credential payload.
	 * @param array|null $finishedLogin Result returned by finish_login(), when available.
	 * @param string|null $ip Client IP for the failed login attempt.
	 * @param bool $includeSubmittedContext Whether submitted token/credential data may be parsed for context.
	 * @return void
	 */
	private function fire_passkey_login_failed_action($error, $token, $credential, $finishedLogin = null, $ip = null, $includeSubmittedContext = true) {
		$context = $this->passkey_login_failure_context($error, $token, $credential, $finishedLogin, $ip, $includeSubmittedContext);

		/**
		 * Fires when a server-side passkey login attempt fails.
		 *
		 * The context is sanitized for logging/integration use and must not include raw WebAuthn assertions, signatures,
		 * login tokens, client data JSON, authenticator data, or credential IDs.
		 *
		 * @param \WP_Error $error Failure returned by passkey login.
		 * @param array $context Sanitized failure context.
		 */
		do_action('wordfence_ls_passkey_login_failed', $error, $context);
	}

	/**
	 * Builds sanitized context for a failed passkey login attempt.
	 *
	 * @param \WP_Error $error Failure returned by passkey login.
	 * @param string $token Submitted login token.
	 * @param mixed $credential Submitted credential payload.
	 * @param array|null $finishedLogin Result returned by finish_login(), when available.
	 * @param string|null $ip Client IP for the failed login attempt.
	 * @param bool $includeSubmittedContext Whether submitted token/credential data may be parsed for context.
	 * @return array
	 */
	private function passkey_login_failure_context($error, $token, $credential, $finishedLogin = null, $ip = null, $includeSubmittedContext = true) {
		$code = $error->get_error_code();
		$context = array(
			'code' => $code,
			'ip' => $this->passkey_login_context_ip($ip),
			'submitted_context_included' => (bool) $includeSubmittedContext,
		);

		$rawId = false;
		if ($includeSubmittedContext) {
			$jwt = is_string($token) ? Model_JWT::decode_jwt($token) : false;
			if ($jwt && isset($jwt->payload['rp_id']) && is_string($jwt->payload['rp_id'])) {
				$context['rp_id'] = $jwt->payload['rp_id'];
			}

			if (is_array($credential) && $this->has_string_value($credential, 'rawId')) {
				$rawId = Model_JWT::base64url_decode($credential['rawId']);
				if ($rawId !== false) {
					$context['credential_id_sha256'] = hash('sha256', $rawId);
				}
			}

			if (is_array($credential) && isset($credential['response']) && is_array($credential['response']) && $this->has_string_value($credential['response'], 'clientDataJSON')) {
				$clientDataJSON = Model_JWT::base64url_decode($credential['response']['clientDataJSON']);
				if ($clientDataJSON !== false) {
					$clientData = json_decode($clientDataJSON, true);
					if (is_array($clientData) && $this->has_string_value($clientData, 'origin')) {
						$context['origin'] = $clientData['origin'];
					}
				}
			}
		}

		$passkey = null;
		if (is_array($finishedLogin) && isset($finishedLogin['passkey']) && is_array($finishedLogin['passkey'])) {
			$passkey = $finishedLogin['passkey'];
		}
		else if ($rawId !== false) {
			$passkey = $this->get_passkey_by_credential_id($rawId);
		}

		if (is_array($passkey)) {
			if (isset($passkey['id'])) {
				$context['passkey_id'] = (int) $passkey['id'];
			}
			if (isset($passkey['user_id'])) {
				$context['user_id'] = (int) $passkey['user_id'];
			}
		}

		if (is_array($finishedLogin) && isset($finishedLogin['user']) && $finishedLogin['user'] instanceof \WP_User) {
			$context['user_id'] = (int) $finishedLogin['user']->ID;
			$context['username'] = $finishedLogin['user']->user_login;
		}

		/**
		 * Filters sanitized passkey-login failure context before the integration hook fires.
		 *
		 * @param array $context Sanitized failure context.
		 * @param \WP_Error $error Failure returned by passkey login.
		 */
		return apply_filters('wordfence_ls_passkey_login_failure_context', $context, $error);
	}

	/**
	 * Resolves a client IP for passkey login failure context.
	 *
	 * @param string|null $ip Candidate client IP.
	 * @return string
	 */
	private function passkey_login_context_ip($ip = null) {
		return is_string($ip) && Model_IP::inet_pton($ip) !== false ? $ip : Model_Request::current()->ip();
	}

	private function has_string_value($array, $key) {
		return is_array($array) && isset($array[$key]) && is_string($array[$key]);
	}

	private function validate_client_data_context($clientData, $rpId, $errorCode, $errorMessage, $allowSameRelyingPartyFrame = false) {
		$crossOrigin = array_key_exists('crossOrigin', $clientData) ? $clientData['crossOrigin'] : false;
		if ($crossOrigin !== false) {
			if ($crossOrigin !== true || !$allowSameRelyingPartyFrame || !$this->has_string_value($clientData, 'origin')) {
				return new \WP_Error($errorCode, $errorMessage);
			}
			if (!$this->is_allowed_origin_for_rp($clientData['origin'], $rpId)) {
				return new \WP_Error($errorCode, $errorMessage);
			}
			if (array_key_exists('topOrigin', $clientData)) {
				if (!$this->has_string_value($clientData, 'topOrigin') || !$this->is_allowed_origin_for_rp($clientData['topOrigin'], $rpId)) {
					return new \WP_Error($errorCode, $errorMessage);
				}
			}
			return true;
		}

		if (array_key_exists('topOrigin', $clientData)) {
			if (!$allowSameRelyingPartyFrame || !$this->has_string_value($clientData, 'origin') || !$this->has_string_value($clientData, 'topOrigin')) {
				return new \WP_Error($errorCode, $errorMessage);
			}
			if (!$this->is_allowed_origin_for_rp($clientData['origin'], $rpId) || !$this->is_allowed_origin_for_rp($clientData['topOrigin'], $rpId)) {
				return new \WP_Error($errorCode, $errorMessage);
			}
			return true;
		}
		return true;
	}

	/**
	 * Returns a specific error when browser client data shows a passkey operation came from a non-local HTTP origin.
	 *
	 * @param array $clientData Browser client data.
	 * @param string $operation Passkey operation, either "registration" or "login".
	 * @return \WP_Error|null
	 */
	private function http_origin_error($clientData, $operation) {
		foreach (array('origin', 'topOrigin') as $key) {
			if ($this->has_string_value($clientData, $key) && $this->is_disallowed_http_origin($clientData[$key])) {
				if ($operation === 'registration') {
					return new \WP_Error('wfls_passkey_origin_http', __('Passkey registration requires HTTPS. Open this site using https:// and try adding the passkey again.', 'wordfence'));
				}
				return new \WP_Error('wfls_passkey_login_origin_http', __('Passkey login requires HTTPS. Open this site using https:// and try again.', 'wordfence'));
			}
		}
		return null;
	}

	/**
	 * Returns whether the origin uses HTTP in a context where passkeys require HTTPS.
	 *
	 * @param string $origin Browser-reported origin.
	 * @return bool
	 */
	private function is_disallowed_http_origin($origin) {
		$parts = wp_parse_url($origin);
		return is_array($parts) && isset($parts['scheme'], $parts['host']) && strtolower($parts['scheme']) === 'http' && strtolower($parts['host']) !== 'localhost';
	}

	/**
	 * Sanitizes and validates a user-facing passkey label before storage.
	 *
	 * @param mixed $label Submitted label value.
	 * @return string|\WP_Error Normalized label, or an error when the label cannot be stored safely.
	 */
	private function normalize_label($label) {
		$label = is_string($label) ? $label : '';
		if (!$this->is_valid_utf8_string($label)) {
			return new \WP_Error('wfls_passkey_label_invalid', __('The passkey name contains invalid characters.', 'wordfence'));
		}
		if (Model_Crypto::strlen($label) > self::MAX_LABEL_BYTES) {
			return $this->label_too_long_error();
		}
		$label = sanitize_text_field($label);
		if ($label === '') {
			$label = __('Passkey', 'wordfence');
		}
		if (preg_match('/[\x{10000}-\x{10FFFF}]/u', $label)) {
			return new \WP_Error('wfls_passkey_label_unsupported', __('The passkey name contains characters that cannot be stored safely. Remove emoji or other unsupported characters and try again.', 'wordfence'));
		}
		if ($this->utf8_length($label) > self::MAX_LABEL_LENGTH) {
			return $this->label_too_long_error();
		}
		return $label;
	}

	/**
	 * Returns the standard error for a passkey label that exceeds storage limits.
	 *
	 * @return \WP_Error Label length error.
	 */
	private function label_too_long_error() {
		return new \WP_Error('wfls_passkey_label_too_long', sprintf(
			/* translators: Maximum passkey name length. */
			__('The passkey name must be %d characters or fewer.', 'wordfence'),
			self::MAX_LABEL_LENGTH
		));
	}

	/**
	 * Returns whether a string is valid UTF-8.
	 *
	 * @param string $value String to validate.
	 * @return bool True when the value is valid UTF-8.
	 */
	private function is_valid_utf8_string($value) {
		return preg_match('//u', $value) === 1;
	}

	/**
	 * Counts Unicode code points in a valid UTF-8 string.
	 *
	 * @param string $value Valid UTF-8 string.
	 * @return int Number of Unicode code points.
	 */
	private function utf8_length($value) {
		if (function_exists('mb_strlen')) {
			return mb_strlen($value, 'UTF-8');
		}
		if (preg_match_all('/./us', $value, $matches) !== false) {
			return count($matches[0]);
		}
		return strlen($value);
	}

	/**
	 * Returns an existing user handle or creates one for passkey registration.
	 *
	 * @param \WP_User $user User receiving the handle.
	 * @return string Raw user-handle bytes.
	 */
	private function get_user_handle($user) {
		$stored = $this->get_stored_user_handle($user);
		if ($stored !== null) {
			return $stored;
		}

		$handle = Model_Crypto::random_bytes(32, false);
		$encoded = Model_JWT::base64url_encode($handle);
		if (function_exists('add_user_meta')) {
			if (add_user_meta($user->ID, self::META_KEY_USER_HANDLE, $encoded, true)) {
				return $handle;
			}
			$stored = $this->get_stored_user_handle($user);
			if ($stored !== null) {
				return $stored;
			}
		}
		update_user_meta($user->ID, self::META_KEY_USER_HANDLE, $encoded);
		return $handle;
	}

	/**
	 * Returns the user's persisted passkey handle without creating or replacing it.
	 *
	 * @param \WP_User $user User whose handle should be read.
	 * @return string|null Raw user-handle bytes, or null when missing or invalid.
	 */
	private function get_stored_user_handle($user) {
		return $user instanceof \WP_User ? $this->decode_user_handle(get_user_meta($user->ID, self::META_KEY_USER_HANDLE, true)) : null;
	}

	/**
	 * Decodes and validates a canonical WebAuthn user handle.
	 *
	 * @param mixed $encoded Base64url-encoded user handle.
	 * @return string|null Raw user-handle bytes, or null when invalid.
	 */
	private function decode_user_handle($encoded) {
		if (!is_string($encoded) || $encoded === '' || preg_match('/^[A-Za-z0-9_-]+$/D', $encoded) !== 1) {
			return null;
		}
		$decoded = Model_JWT::base64url_decode($encoded);
		$length = is_string($decoded) ? Model_Crypto::strlen($decoded) : 0;
		if ($length < 1 || $length > self::MAX_USER_HANDLE_BYTES || Model_JWT::base64url_encode($decoded) !== $encoded) {
			return null;
		}
		return $decoded;
	}

	/**
	 * Deletes one credential row that no longer has a valid account binding.
	 *
	 * @param array $passkey Orphaned credential row.
	 * @return void
	 */
	private function delete_orphaned_passkey($passkey) {
		$passkeyID = isset($passkey['id']) ? (int) $passkey['id'] : 0;
		$userID = isset($passkey['user_id']) ? (int) $passkey['user_id'] : 0;
		if ($passkeyID <= 0) {
			return;
		}

		global $wpdb;
		$deleted = $wpdb->delete(Controller_DB::shared()->passkeys, array(
			'id' => $passkeyID,
			'user_id' => $userID,
		), array('%d', '%d'));
		if ($deleted !== false && $userID > 0) {
			Controller_Users::shared()->clear_passkey_active_cache($userID);
		}
	}

	private function encode_transports($transports) {
		$valid = array();
		if (!is_array($transports)) {
			return '';
		}
		foreach ($transports as $transport) {
			if (!is_string($transport)) {
				continue;
			}
			$transport = strtolower(sanitize_key($transport));
			if (in_array($transport, array('usb', 'nfc', 'ble', 'hybrid', 'internal'), true)) {
				$valid[$transport] = $transport;
			}
		}
		return implode(',', $valid);
	}

	/**
	 * Serializes the per-user count check and passkey insert.
	 *
	 * @param string $table Fully-qualified passkeys table name.
	 * @param \WP_User $user User that owns the passkey.
	 * @param string $credentialId Raw binary credential ID.
	 * @param string $publicKey Raw COSE public key.
	 * @param int $signCount Authenticator sign count.
	 * @param array $transports Browser-reported transport names.
	 * @param string $label User-facing passkey label.
	 * @param string $userHandle Raw binary passkey user handle.
	 * @param int $now Current timestamp for ctime and mtime.
	 * @return int|bool|\WP_Error Insert result, or an error when the limit cannot be checked safely.
	 */
	private function insert_passkey_record_with_limit($table, $user, $credentialId, $publicKey, $signCount, $transports, $label, $userHandle, $now) {
		global $wpdb;
		$userId = (int) $user->ID;
		$lock = new Utility_DatabaseLock(Controller_DB::shared(), 'passkey-registration:' . (int) $userId, 5);
		try {
			$lock->acquire();
			$passkeyCount = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$table}` WHERE `user_id` = %d", $userId));
			if ($passkeyCount >= $this->max_passkeys_per_user()) {
				return $this->passkey_limit_error();
			}
			if ($passkeyCount === 0 && $this->can_change_username_password_auth($user) && !$this->set_username_password_auth_enabled($user, true)) {
				return new \WP_Error('wfls_passkey_password_auth_restore_failed', __('Unable to restore username/password authentication before registering the first passkey. Please try again.', 'wordfence'));
			}
			return $this->insert_passkey_record($table, $userId, $credentialId, $publicKey, $signCount, $transports, $label, $userHandle, $now);
		}
		catch (\RuntimeException $e) {
			return new \WP_Error('wfls_passkey_registration_busy', __('Another passkey registration is already being completed for this account. Please try again.', 'wordfence'));
		}
		finally {
			$lock->release();
		}
	}

	/**
	 * Inserts a passkey row while treating a replayed credential ID as a no-op.
	 *
	 * @param string $table Fully-qualified passkeys table name.
	 * @param int $userId User ID that owns the passkey.
	 * @param string $credentialId Raw binary credential ID.
	 * @param string $publicKey Raw COSE public key.
	 * @param int $signCount Authenticator sign count.
	 * @param array $transports Browser-reported transport names.
	 * @param string $label User-facing passkey label.
	 * @param string $userHandle Raw binary passkey user handle.
	 * @param int $now Current timestamp for ctime and mtime.
	 * @return int|bool Number of affected rows, or false on database error.
	 */
	private function insert_passkey_record($table, $userId, $credentialId, $publicKey, $signCount, $transports, $label, $userHandle, $now) {
		global $wpdb;
		$credentialIdHash = hash('sha256', $credentialId, true);
		return $wpdb->query($wpdb->prepare(
			"INSERT INTO `{$table}` (`user_id`, `credential_id`, `credential_id_hash`, `public_key`, `sign_count`, `transports`, `label`, `user_handle`, `ctime`, `mtime`) VALUES (%d, UNHEX(%s), UNHEX(%s), %s, %d, %s, %s, %s, %d, %d) ON DUPLICATE KEY UPDATE `credential_id_hash` = `credential_id_hash`",
			$userId,
			bin2hex($credentialId),
			bin2hex($credentialIdHash),
			$publicKey,
			$signCount,
			$this->encode_transports($transports),
			$label,
			$userHandle,
			$now,
			$now
		));
	}

	/**
	 * Returns the standard error for reaching the per-user passkey limit.
	 *
	 * @return \WP_Error Passkey-limit error.
	 */
	private function passkey_limit_error() {
		return new \WP_Error(
			'wfls_passkey_limit_reached',
			sprintf(/* translators: maximum passkeys per user */ __('You may register up to %d passkeys for this account. Remove an existing passkey before adding another.', 'wordfence'), $this->max_passkeys_per_user())
		);
	}

	private function decode_transports($transports) {
		if (!is_string($transports) || $transports === '') {
			return array();
		}
		return array_values(array_filter(array_map('trim', explode(',', $transports))));
	}

	/**
	 * Returns the effective RP ID for passkey registration and login.
	 *
	 * @return string
	 */
	public function get_rp_id() {
		$override = Controller_Settings::shared()->get(Controller_Settings::OPTION_PASSKEY_RELYING_PARTY_OVERRIDE, '');
		if (!empty($override)) {
			return $override;
		}

		$lastPasskeyRP = Controller_Settings::shared()->get(Controller_Settings::OPTION_LAST_PASSKEY_RP, '');
		if (is_string($lastPasskeyRP) && $lastPasskeyRP !== '' && $this->any_passkeys_active()) {
			return $lastPasskeyRP;
		}

		return $this->defaultRP();
	}

	private function is_allowed_origin($origin) {
		$parts = $this->parse_web_origin($origin);
		return is_array($parts) && $this->is_allowed_origin_host($parts['host'], $parts['port'], $parts['scheme']);
	}

	/**
	 * Returns whether an origin is allowed by policy and valid for the requested RP ID.
	 *
	 * @param string $origin Browser-reported origin.
	 * @param string $rpId RP ID from the signed begin token.
	 * @return bool
	 */
	private function is_allowed_origin_for_rp($origin, $rpId) {
		$parts = $this->parse_web_origin($origin);
		if (!is_array($parts) || !$this->is_allowed_origin_host($parts['host'], $parts['port'], $parts['scheme'])) {
			return false;
		}
		return $this->is_origin_host_valid_for_rp($parts['host'], $rpId);
	}

	/**
	 * Parses a serialized browser WebAuthn origin into normalized comparison components.
	 *
	 * @param string $origin Browser-reported origin.
	 * @return array|false Parsed scheme, host, and effective port, or false when invalid or unsupported.
	 */
	private function parse_web_origin($origin) {
		if (!is_string($origin) || $origin === '') {
			return false;
		}

		$parts = wp_parse_url($origin);
		if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
			return false;
		}
		foreach (array('user', 'pass', 'path', 'query', 'fragment') as $component) {
			if (array_key_exists($component, $parts)) {
				return false;
			}
		}

		$scheme = strtolower($parts['scheme']);
		$host = $this->normalize_hostname($parts['host']);
		if ($host === '' || ($scheme !== 'https' && !($scheme === 'http' && $host === 'localhost'))) {
			return false;
		}

		$port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
		if ($port < 1 || $port > 65535) {
			return false;
		}

		return array(
			'scheme' => $scheme,
			'host' => $host,
			'port' => $port,
		);
	}

	/**
	 * Returns whether a host is equal to or a subdomain of the RP ID.
	 *
	 * @param string $host Origin hostname.
	 * @param string $rpId RP ID.
	 * @return bool
	 */
	public function is_origin_host_valid_for_rp($host, $rpId) {
		$host = $this->normalize_hostname($host);
		$rpId = $this->normalize_hostname($rpId);
		if ($host === '' || $rpId === '') {
			return false;
		}

		if ($host === $rpId) {
			return true;
		}

		if ($host === 'localhost' || $rpId === 'localhost' || filter_var($host, FILTER_VALIDATE_IP) || filter_var($rpId, FILTER_VALIDATE_IP)) {
			return false;
		}

		$suffix = '.' . $rpId;
		return strlen($host) > strlen($suffix) && substr($host, -strlen($suffix)) === $suffix;
	}

	/**
	 * Returns whether a hostname and effective port are allowed by the configured passkey origin policy.
	 *
	 * @param string $host Hostname.
	 * @param int $port Effective origin port.
	 * @param string $scheme Origin scheme.
	 * @return bool
	 */
	public function is_allowed_origin_host($host, $port = 443, $scheme = 'https') {
		$host = $this->normalize_hostname($host);
		$port = (int) $port;
		$scheme = strtolower((string) $scheme);
		if ($host === '' || $port < 1 || $port > 65535 || ($scheme !== 'https' && !($scheme === 'http' && $host === 'localhost'))) {
			return false;
		}

		foreach (Controller_Settings::shared()->passkey_allowed_hostnames() as $allowedHost) {
			$parsed = Controller_Settings::shared()->parse_passkey_allowed_hostname($allowedHost);
			if (!is_array($parsed) || $host !== $this->normalize_hostname($parsed['host'])) {
				continue;
			}
			$allowedPort = $parsed['port'];
			if ($allowedPort === null) {
				$allowedPort = 443;
			}
			if ($port === (int) $allowedPort) {
				return true;
			}
		}

		return false;
	}

	private function normalize_hostname($host) {
		if (!is_string($host)) {
			return '';
		}

		$host = strtolower(rtrim($host, '.'));
		$unbracketed = trim($host, '[]');
		if (filter_var($unbracketed, FILTER_VALIDATE_IP)) {
			return $unbracketed;
		}
		return $host;
	}

	/**
	 * Returns the transient key used to track one passkey registration attempt.
	 *
	 * @param string $jti Registration token identifier.
	 * @return string Registration-token transient key.
	 */
	private function get_registration_token_transient_key($jti) {
		return 'wfls-passkey-registration:' . md5($jti);
	}

	/**
	 * Returns a one-way binding between a user and the current WordPress session.
	 *
	 * @param int $userId User ID being registered.
	 * @return string|null Session binding, or null when no authenticated session token is available.
	 */
	private function registration_token_binding($userId) {
		$sessionToken = wp_get_session_token();
		if (!is_string($sessionToken) || $sessionToken === '') {
			return null;
		}
		return hash_hmac('sha256', (string) (int) $userId . "\0" . $sessionToken, Model_Crypto::shared_hash_secret());
	}

	private function get_passkey_by_credential_id($credentialId) {
		$credentialIdLength = Model_Crypto::strlen($credentialId);
		if ($credentialIdLength < 1 || $credentialIdLength > self::MAX_CREDENTIAL_ID_BYTES) {
			return null;
		}
		global $wpdb;
		$table = Controller_DB::shared()->passkeys;
		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM `{$table}` WHERE `credential_id_hash` = UNHEX(%s) AND `credential_id` = UNHEX(%s) LIMIT 1",
			bin2hex(hash('sha256', $credentialId, true)),
			bin2hex($credentialId)
		), ARRAY_A);
	}

	/**
	 * Returns the transient key used to track a single passkey login attempt.
	 *
	 * @param string $jti
	 * @return string
	 */
	private function get_login_token_transient_key($jti) {
		return 'wfls-passkey-login:' . md5($jti);
	}

	/**
	 * Stores a passkey login token identifier so the request may be consumed only once.
	 *
	 * @param string $jti
	 * @return bool
	 */
	private function remember_login_token($jti) {
		if (!is_string($jti) || $jti === '') {
			return false;
		}

		return set_transient($this->get_login_token_transient_key($jti), 1, self::LOGIN_TOKEN_DURATION);
	}

	/**
	 * Consumes a passkey login token identifier, returning false when the token was already used or never issued.
	 *
	 * @param string $jti
	 * @return bool
	 */
	private function consume_login_token($jti) {
		if (!is_string($jti) || $jti === '') {
			return false;
		}

		$lock = new Utility_DatabaseLock(Controller_DB::shared(), 'passkey-login:' . md5($jti), 1);
		try {
			$lock->acquire();
			$key = $this->get_login_token_transient_key($jti);
			if (!get_transient($key)) {
				return false;
			}

			delete_transient($key);
			return true;
		}
		catch (\RuntimeException $e) {
			return false;
		}
		finally {
			$lock->release();
		}
	}

	private function establish_login_session($user, $remember) {
		wp_clear_auth_cookie();
		wp_set_current_user($user->ID, $user->user_login);
		wp_set_auth_cookie($user->ID, $remember, is_ssl());
		do_action('wp_login', $user->user_login, $user);
	}

	private function update_passkey_usage($passkey, $signCount) {
		global $wpdb;
		$table = Controller_DB::shared()->passkeys;
		$now = Controller_Time::time();
		$id = isset($passkey['id']) ? (int) $passkey['id'] : 0;
		$storedSignCount = isset($passkey['sign_count']) ? (int) $passkey['sign_count'] : 0;
		$signCount = (int) $signCount;
		if ($id <= 0) {
			return new \WP_Error('wfls_passkey_usage_update_failed', __('The passkey login could not be completed. Please try again.', 'wordfence'));
		}

		$signCountValidation = $this->validate_passkey_sign_count($storedSignCount, $signCount);
		if (is_wp_error($signCountValidation)) {
			return $signCountValidation;
		}

		if ($signCount > 0) {
			if ($signCount > $storedSignCount) {
				$updated = $wpdb->query($wpdb->prepare(
					"UPDATE `{$table}` SET `sign_count` = %d, `mtime` = %d, `last_used_at` = %d WHERE `id` = %d AND `sign_count` < %d",
					$signCount,
					$now,
					$now,
					$id,
					$signCount
				));
				if ($updated === false) {
					return new \WP_Error('wfls_passkey_usage_update_failed', __('The passkey login could not be completed. Please try again.', 'wordfence'));
				}
				if ($updated > 0) {
					return true;
				}
				if (Controller_Settings::shared()->passkey_sign_count_mode() !== Controller_Settings::PASSKEY_SIGN_COUNT_ALLOW) {
					return new \WP_Error('wfls_passkey_login_sign_count_invalid', __('The passkey sign-in counter was invalid. Please remove and re-add this passkey.', 'wordfence'));
				}
			}

			return $this->touch_passkey_usage($id, $now);
		}

		if (Controller_Settings::shared()->passkey_sign_count_mode() === Controller_Settings::PASSKEY_SIGN_COUNT_REJECT_LOWER_AND_ZERO) {
			$updated = $wpdb->query($wpdb->prepare(
				"UPDATE `{$table}` SET `mtime` = %d, `last_used_at` = %d WHERE `id` = %d AND `sign_count` = 0",
				$now,
				$now,
				$id
			));
			if ($updated === false) {
				return new \WP_Error('wfls_passkey_usage_update_failed', __('The passkey login could not be completed. Please try again.', 'wordfence'));
			}
			if ($updated < 1) {
				return new \WP_Error('wfls_passkey_login_sign_count_invalid', __('The passkey sign-in counter was invalid. Please remove and re-add this passkey.', 'wordfence'));
			}
			return true;
		}

		return $this->touch_passkey_usage($id, $now);
	}

	/**
	 * Updates passkey usage timestamps without changing the stored sign-in counter.
	 *
	 * @param int $id Passkey row ID.
	 * @param int $now Current timestamp.
	 * @return true|\WP_Error
	 */
	private function touch_passkey_usage($id, $now) {
		global $wpdb;
		$table = Controller_DB::shared()->passkeys;
		$updated = $wpdb->update(
			$table,
			array(
				'mtime' => $now,
				'last_used_at' => $now,
			),
			array('id' => (int) $id),
			array('%d', '%d'),
			array('%d')
		);
		if ($updated === false) {
			return new \WP_Error('wfls_passkey_usage_update_failed', __('The passkey login could not be completed. Please try again.', 'wordfence'));
		}
		return true;
	}

	/**
	 * Validates an assertion sign-in counter against the stored passkey counter and configured policy.
	 *
	 * @param int $storedSignCount Stored counter value.
	 * @param int $assertionSignCount Counter returned by the authenticator.
	 * @return true|\WP_Error
	 */
	private function validate_passkey_sign_count($storedSignCount, $assertionSignCount) {
		$mode = Controller_Settings::shared()->passkey_sign_count_mode();
		$storedSignCount = (int) $storedSignCount;
		$assertionSignCount = (int) $assertionSignCount;
		if ($mode === Controller_Settings::PASSKEY_SIGN_COUNT_ALLOW) {
			return true;
		}
		if ($storedSignCount > 0 && $assertionSignCount > 0 && $assertionSignCount <= $storedSignCount) {
			return new \WP_Error('wfls_passkey_login_sign_count_invalid', __('The passkey sign-in counter was invalid. Please remove and re-add this passkey.', 'wordfence'));
		}
		if ($mode === Controller_Settings::PASSKEY_SIGN_COUNT_REJECT_LOWER_AND_ZERO && $storedSignCount > 0 && $assertionSignCount === 0) {
			return new \WP_Error('wfls_passkey_login_sign_count_invalid', __('The passkey sign-in counter was invalid. Please remove and re-add this passkey.', 'wordfence'));
		}
		return true;
	}

	/**
	 * Returns the credential algorithms offered during passkey registration.
	 *
	 * @return array WebAuthn public-key credential parameters.
	 */
	private function supported_public_key_credential_parameters() {
		return array(
			array('type' => 'public-key', 'alg' => -7),
			array('type' => 'public-key', 'alg' => -257),
		);
	}

	/**
	 * Validates a no-attestation registration object and extracts its authenticator data.
	 *
	 * @param string $attestationObject Raw CBOR attestation object.
	 * @param string $rpId Expected relying-party ID.
	 * @return array|\WP_Error Parsed authenticator data, or an error when invalid.
	 */
	private function parse_registration_attestation($attestationObject, $rpId) {
		$entries = $this->decode_cbor_map_entries($attestationObject, 0, $attestationOffset);
		if ($attestationOffset !== Model_Crypto::strlen($attestationObject) || count($entries) !== 3) {
			return $this->registration_attestation_error();
		}

		$fields = array();
		$fieldTypes = array();
		foreach ($entries as $entry) {
			if ($this->cbor_major_type($entry['key_raw']) !== 3 || !is_string($entry['key']) || !in_array($entry['key'], array('fmt', 'authData', 'attStmt'), true)) {
				return $this->registration_attestation_error();
			}
			$fields[$entry['key']] = $entry['value'];
			$fieldTypes[$entry['key']] = $this->cbor_major_type($entry['value_raw']);
		}

		if (
			!array_key_exists('fmt', $fields) || $fieldTypes['fmt'] !== 3 || $fields['fmt'] !== 'none' ||
			!array_key_exists('authData', $fields) || $fieldTypes['authData'] !== 2 || !is_string($fields['authData']) ||
			!array_key_exists('attStmt', $fields) || $fieldTypes['attStmt'] !== 5 || !is_array($fields['attStmt']) || count($fields['attStmt']) !== 0
		) {
			return $this->registration_attestation_error();
		}

		return $this->parse_authenticator_data($fields['authData'], $rpId);
	}

	/**
	 * Returns the standard error for an invalid registration attestation object.
	 *
	 * @return \WP_Error Invalid-attestation error.
	 */
	private function registration_attestation_error() {
		return new \WP_Error('wfls_passkey_attestation_invalid', __('The browser returned invalid passkey attestation data.', 'wordfence'));
	}

	/**
	 * Parses and validates authenticator data returned during registration.
	 *
	 * @param string $authenticatorData Raw authenticator data.
	 * @param string $rpId Expected relying-party ID.
	 * @return array|\WP_Error Parsed credential data, or an error when invalid.
	 */
	private function parse_authenticator_data($authenticatorData, $rpId) {
		if (Model_Crypto::strlen($authenticatorData) < 37) {
			return new \WP_Error('wfls_passkey_auth_data_short', __('The browser returned incomplete passkey authenticator data.', 'wordfence'));
		}

		$rpIdHash = Model_Crypto::substr($authenticatorData, 0, 32);
		$expectedRpIdHash = hash('sha256', $rpId, true);
		if (!hash_equals($expectedRpIdHash, $rpIdHash)) {
			return new \WP_Error('wfls_passkey_rp_hash_invalid', __('The passkey data did not match this site.', 'wordfence'));
		}

		$flags = ord($authenticatorData[32]);
		if (($flags & 0x01) === 0) {
			return new \WP_Error('wfls_passkey_user_presence_missing', __('The passkey registration did not include user presence.', 'wordfence'));
		}
		if (($flags & 0x04) === 0) {
			return new \WP_Error('wfls_passkey_user_verification_missing', __('The passkey registration did not include user verification.', 'wordfence'));
		}
		if (($flags & 0x40) === 0) {
			return new \WP_Error('wfls_passkey_attested_data_missing', __('The passkey registration did not include credential data.', 'wordfence'));
		}
		if (($flags & 0x22) !== 0 || (($flags & 0x10) !== 0 && ($flags & 0x08) === 0)) {
			return new \WP_Error('wfls_passkey_authenticator_flags_invalid', __('The browser returned inconsistent passkey authenticator flags.', 'wordfence'));
		}

		$signCount = unpack('Ncount', Model_Crypto::substr($authenticatorData, 33, 4));
		$signCount = isset($signCount['count']) ? (int) $signCount['count'] : 0;
		$offset = 37 + 16;

		if (Model_Crypto::strlen($authenticatorData) < $offset + 2) {
			return new \WP_Error('wfls_passkey_credential_length_missing', __('The passkey credential identifier was incomplete.', 'wordfence'));
		}

		$credentialLengthData = unpack('nlength', Model_Crypto::substr($authenticatorData, $offset, 2));
		$credentialLength = isset($credentialLengthData['length']) ? (int) $credentialLengthData['length'] : 0;
		$offset += 2;
		if ($credentialLength < 1 || $credentialLength > self::MAX_CREDENTIAL_ID_BYTES) {
			return new \WP_Error('wfls_passkey_credential_length_invalid', __('The browser returned an invalid passkey credential identifier length.', 'wordfence'));
		}

		if (Model_Crypto::strlen($authenticatorData) < $offset + $credentialLength) {
			return new \WP_Error('wfls_passkey_credential_data_short', __('The passkey credential identifier was truncated.', 'wordfence'));
		}

		$credentialId = Model_Crypto::substr($authenticatorData, $offset, $credentialLength);
		$offset += $credentialLength;

		$decodedPublicKey = $this->decode_cbor_item($authenticatorData, $offset, $publicKeyOffset, $publicKeyRaw);
		if (!is_array($decodedPublicKey) || !is_string($publicKeyRaw) || $publicKeyRaw === '') {
			return new \WP_Error('wfls_passkey_public_key_invalid', __('The browser returned an invalid passkey public key.', 'wordfence'));
		}
		$publicKeyValidation = $this->validate_registration_cose_public_key($publicKeyRaw);
		if (is_wp_error($publicKeyValidation)) {
			return $publicKeyValidation;
		}

		$authenticatorDataLength = Model_Crypto::strlen($authenticatorData);
		if (($flags & 0x80) !== 0) {
			if ($publicKeyOffset >= $authenticatorDataLength || $this->cbor_major_type(Model_Crypto::substr($authenticatorData, $publicKeyOffset, 1)) !== 5) {
				return new \WP_Error('wfls_passkey_authenticator_extensions_invalid', __('The browser returned invalid passkey authenticator extension data.', 'wordfence'));
			}
			$extensions = $this->decode_cbor_item($authenticatorData, $publicKeyOffset, $extensionOffset);
			if (!is_array($extensions) || $extensionOffset !== $authenticatorDataLength) {
				return new \WP_Error('wfls_passkey_authenticator_extensions_invalid', __('The browser returned invalid passkey authenticator extension data.', 'wordfence'));
			}
		}
		else if ($publicKeyOffset !== $authenticatorDataLength) {
			return new \WP_Error('wfls_passkey_authenticator_data_trailing', __('The browser returned unexpected trailing passkey authenticator data.', 'wordfence'));
		}

		return array(
			'credential_id' => $credentialId,
			'public_key' => $publicKeyRaw,
			'sign_count' => $signCount,
		);
	}

	/**
	 * Validates a registration COSE key against the algorithms offered by this relying party.
	 *
	 * @param string $publicKey Raw COSE public key.
	 * @return true|\WP_Error True when supported and usable, or an error when invalid.
	 */
	private function validate_registration_cose_public_key($publicKey) {
		if (Model_Crypto::strlen($publicKey) > self::MAX_PUBLIC_KEY_BYTES) {
			return $this->registration_public_key_error();
		}
		try {
			$entries = $this->decode_cbor_map_entries($publicKey, 0, $offset);
		}
		catch (\UnexpectedValueException $e) {
			return $this->registration_public_key_error();
		}
		if ($offset !== Model_Crypto::strlen($publicKey)) {
			return $this->registration_public_key_error();
		}

		$decoded = array();
		$valueTypes = array();
		foreach ($entries as $entry) {
			$keyType = $this->cbor_major_type($entry['key_raw']);
			if (($keyType !== 0 && $keyType !== 1) || !is_int($entry['key'])) {
				return $this->registration_public_key_error();
			}
			$decoded[$entry['key']] = $entry['value'];
			$valueTypes[$entry['key']] = $this->cbor_major_type($entry['value_raw']);
		}

		if (!isset($decoded[1], $decoded[3]) || !is_int($decoded[1]) || !is_int($decoded[3])) {
			return $this->registration_public_key_error();
		}
		$offeredAlgorithms = array();
		foreach ($this->supported_public_key_credential_parameters() as $parameter) {
			$offeredAlgorithms[] = $parameter['alg'];
		}
		if (!in_array($decoded[3], $offeredAlgorithms, true)) {
			return new \WP_Error('wfls_passkey_public_key_unsupported', __('The browser returned a passkey public key using an unsupported algorithm.', 'wordfence'));
		}

		if ($decoded[1] === 2 && $decoded[3] === -7) {
			if (
				count($entries) !== 5 || !isset($decoded[-1], $decoded[-2], $decoded[-3]) ||
				!is_int($decoded[-1]) || $decoded[-1] !== 1 ||
				!is_string($decoded[-2]) || $valueTypes[-2] !== 2 || Model_Crypto::strlen($decoded[-2]) !== 32 ||
				!is_string($decoded[-3]) || $valueTypes[-3] !== 2 || Model_Crypto::strlen($decoded[-3]) !== 32
			) {
				return $this->registration_public_key_error();
			}
			$canonicalKey = "\xa5\x01\x02\x03\x26\x20\x01\x21\x58\x20" . $decoded[-2] . "\x22\x58\x20" . $decoded[-3];
			$pem = $this->cose_ec2_public_key_to_pem($decoded);
			$expectedOpenSSLType = defined('OPENSSL_KEYTYPE_EC') ? OPENSSL_KEYTYPE_EC : null;
		}
		else if ($decoded[1] === 3 && $decoded[3] === -257) {
			if (
				count($entries) !== 4 || !isset($decoded[-1], $decoded[-2]) ||
				!is_string($decoded[-1]) || $valueTypes[-1] !== 2 ||
				!is_string($decoded[-2]) || $valueTypes[-2] !== 2 ||
				!$this->is_valid_registration_rsa_components($decoded[-1], $decoded[-2])
			) {
				return $this->registration_public_key_error();
			}
			$canonicalKey = "\xa4\x01\x03\x03\x39\x01\x00\x20" . $this->encode_cbor_byte_string($decoded[-1]) . "\x21" . $this->encode_cbor_byte_string($decoded[-2]);
			$pem = $this->cose_rsa_public_key_to_pem($decoded);
			$expectedOpenSSLType = OPENSSL_KEYTYPE_RSA;
		}
		else {
			return $this->registration_public_key_error();
		}

		if (!hash_equals($canonicalKey, $publicKey)) {
			return $this->registration_public_key_error();
		}
		$opensslKey = @openssl_pkey_get_public($pem);
		if ($opensslKey === false) {
			return $this->registration_public_key_error();
		}
		$details = @openssl_pkey_get_details($opensslKey);
		if (!is_array($details) || $expectedOpenSSLType === null || !isset($details['type'], $details['bits']) || $details['type'] !== $expectedOpenSSLType) {
			return $this->registration_public_key_error();
		}
		if ($decoded[3] === -7 && (int) $details['bits'] !== 256) {
			return $this->registration_public_key_error();
		}
		if ($decoded[3] === -257 && ((int) $details['bits'] < self::MIN_RSA_MODULUS_BITS || (int) $details['bits'] > self::MAX_RSA_MODULUS_BITS)) {
			return $this->registration_public_key_error();
		}
		return true;
	}

	/**
	 * Returns whether RSA modulus and exponent byte strings meet registration policy.
	 *
	 * @param string $modulus Unsigned RSA modulus.
	 * @param string $exponent Unsigned RSA public exponent.
	 * @return bool True when the components meet the supported bounds.
	 */
	private function is_valid_registration_rsa_components($modulus, $exponent) {
		$modulusLength = Model_Crypto::strlen($modulus);
		$exponentLength = Model_Crypto::strlen($exponent);
		if (
			$modulusLength < 1 || $modulusLength > 512 || $modulus[0] === "\x00" ||
			(ord($modulus[$modulusLength - 1]) & 1) === 0 ||
			$exponentLength < 1 || $exponentLength > 4 || $exponent[0] === "\x00"
		) {
			return false;
		}

		$firstByte = ord($modulus[0]);
		$modulusBits = ($modulusLength - 1) * 8;
		while ($firstByte > 0) {
			$modulusBits++;
			$firstByte >>= 1;
		}
		if ($modulusBits < self::MIN_RSA_MODULUS_BITS || $modulusBits > self::MAX_RSA_MODULUS_BITS) {
			return false;
		}

		return (ord($exponent[$exponentLength - 1]) & 1) === 1 && !($exponentLength === 1 && ord($exponent[0]) < 3);
	}

	/**
	 * Encodes a bounded byte string using canonical CBOR length encoding.
	 *
	 * @param string $value Byte string to encode.
	 * @return string Canonical CBOR byte string.
	 */
	private function encode_cbor_byte_string($value) {
		$length = Model_Crypto::strlen($value);
		if ($length < 24) {
			return chr(0x40 | $length) . $value;
		}
		if ($length <= 0xff) {
			return "\x58" . chr($length) . $value;
		}
		return "\x59" . pack('n', $length) . $value;
	}

	/**
	 * Returns the standard error for an invalid registration public key.
	 *
	 * @return \WP_Error Invalid-public-key error.
	 */
	private function registration_public_key_error() {
		return new \WP_Error('wfls_passkey_public_key_invalid', __('The browser returned an invalid passkey public key.', 'wordfence'));
	}

	private function parse_assertion_authenticator_data($authenticatorData, $rpId) {
		if (Model_Crypto::strlen($authenticatorData) < 37) {
			return new \WP_Error('wfls_passkey_assertion_auth_data_short', __('The browser returned incomplete passkey authenticator data.', 'wordfence'));
		}

		$rpIdHash = Model_Crypto::substr($authenticatorData, 0, 32);
		$expectedRpIdHash = hash('sha256', $rpId, true);
		if (!hash_equals($expectedRpIdHash, $rpIdHash)) {
			return new \WP_Error('wfls_passkey_assertion_rp_hash_invalid', __('The passkey data did not match this site.', 'wordfence'));
		}

		$flags = ord($authenticatorData[32]);
		if (($flags & 0x01) === 0) {
			return new \WP_Error('wfls_passkey_assertion_user_presence_missing', __('The passkey login did not include user presence.', 'wordfence'));
		}
		if (($flags & 0x04) === 0) {
			return new \WP_Error('wfls_passkey_assertion_user_verification_missing', __('The passkey login did not include user verification.', 'wordfence'));
		}

		$signCount = unpack('Ncount', Model_Crypto::substr($authenticatorData, 33, 4));
		return array(
			'flags' => $flags,
			'sign_count' => isset($signCount['count']) ? (int) $signCount['count'] : 0,
		);
	}

	private function verify_signature($storedPublicKey, $data, $signature) {
		try {
			$pem = $this->cose_public_key_to_pem($storedPublicKey);
		}
		catch (\UnexpectedValueException $e) {
			return new \WP_Error('wfls_passkey_public_key_unsupported', __('The stored passkey uses an unsupported public key format.', 'wordfence'));
		}

		$verified = @openssl_verify($data, $signature, $pem, OPENSSL_ALGO_SHA256);
		return $verified === 1;
	}

	private function cose_public_key_to_pem($cosePublicKey) {
		$decoded = $this->decode_cbor_item($cosePublicKey, 0, $offset, $rawBytes);
		if (!is_array($decoded) || !isset($decoded[1]) || !isset($decoded[3])) {
			throw new \UnexpectedValueException('Invalid COSE key.');
		}

		$kty = (int) $decoded[1];
		$alg = (int) $decoded[3];
		if ($kty === 2 && $alg === -7) {
			return $this->cose_ec2_public_key_to_pem($decoded);
		}
		if ($kty === 3 && $alg === -257) {
			return $this->cose_rsa_public_key_to_pem($decoded);
		}

		throw new \UnexpectedValueException('Unsupported COSE key type.');
	}

	private function cose_ec2_public_key_to_pem($decoded) {
		if (!isset($decoded[-1]) || !isset($decoded[-2]) || !isset($decoded[-3])) {
			throw new \UnexpectedValueException('Incomplete EC2 key.');
		}
		if ((int) $decoded[-1] !== 1) {
			throw new \UnexpectedValueException('Unsupported EC curve.');
		}

		$x = $decoded[-2];
		$y = $decoded[-3];
		if (!is_string($x) || !is_string($y)) {
			throw new \UnexpectedValueException('Invalid EC key coordinates.');
		}

		$point = "\x04" . $x . $y;
		$algorithm = $this->asn1_sequence(
			$this->asn1_object_identifier('1.2.840.10045.2.1') .
			$this->asn1_object_identifier('1.2.840.10045.3.1.7')
		);
		$subjectPublicKey = $this->asn1_bit_string($point);
		return $this->pem_encode('PUBLIC KEY', $this->asn1_sequence($algorithm . $subjectPublicKey));
	}

	private function cose_rsa_public_key_to_pem($decoded) {
		if (!isset($decoded[-1]) || !isset($decoded[-2])) {
			throw new \UnexpectedValueException('Incomplete RSA key.');
		}

		$n = $decoded[-1];
		$e = $decoded[-2];
		if (!is_string($n) || !is_string($e)) {
			throw new \UnexpectedValueException('Invalid RSA key components.');
		}

		$rsaPublicKey = $this->asn1_sequence(
			$this->asn1_integer($n) .
			$this->asn1_integer($e)
		);
		$algorithm = $this->asn1_sequence(
			$this->asn1_object_identifier('1.2.840.113549.1.1.1') .
			$this->asn1_null()
		);
		$subjectPublicKey = $this->asn1_bit_string($rsaPublicKey);
		return $this->pem_encode('PUBLIC KEY', $this->asn1_sequence($algorithm . $subjectPublicKey));
	}

	private function pem_encode($label, $der) {
		return "-----BEGIN {$label}-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END {$label}-----\n";
	}

	private function asn1_sequence($payload) {
		return "\x30" . $this->asn1_length(Model_Crypto::strlen($payload)) . $payload;
	}

	private function asn1_bit_string($payload) {
		return "\x03" . $this->asn1_length(Model_Crypto::strlen($payload) + 1) . "\x00" . $payload;
	}

	private function asn1_integer($payload) {
		$payload = ltrim($payload, "\x00");
		if ($payload === '') {
			$payload = "\x00";
		}
		if ((ord($payload[0]) & 0x80) !== 0) {
			$payload = "\x00" . $payload;
		}
		return "\x02" . $this->asn1_length(Model_Crypto::strlen($payload)) . $payload;
	}

	private function asn1_null() {
		return "\x05\x00";
	}

	private function asn1_object_identifier($oid) {
		$parts = array_map('intval', explode('.', $oid));
		if (count($parts) < 2) {
			throw new \UnexpectedValueException('Invalid OID.');
		}

		$payload = chr((40 * $parts[0]) + $parts[1]);
		for ($i = 2; $i < count($parts); $i++) {
			$payload .= $this->asn1_base128_integer($parts[$i]);
		}

		return "\x06" . $this->asn1_length(Model_Crypto::strlen($payload)) . $payload;
	}

	private function asn1_base128_integer($value) {
		$value = (int) $value;
		$bytes = array($value & 0x7f);
		while ($value > 0x7f) {
			$value >>= 7;
			array_unshift($bytes, 0x80 | ($value & 0x7f));
		}
		return implode('', array_map('chr', $bytes));
	}

	private function asn1_length($length) {
		$length = (int) $length;
		if ($length < 0x80) {
			return chr($length);
		}

		$bytes = '';
		while ($length > 0) {
			$bytes = chr($length & 0xff) . $bytes;
			$length >>= 8;
		}
		return chr(0x80 | Model_Crypto::strlen($bytes)) . $bytes;
	}

	private function decode_cbor_item($data, $offset, &$nextOffset = null, &$rawBytes = null, $depth = 0, &$remainingItems = null) {
		$length = Model_Crypto::strlen($data);
		if ($remainingItems === null) {
			if ($length > self::CBOR_MAX_INPUT_BYTES) {
				throw new \UnexpectedValueException('CBOR data exceeds decoder limit.');
			}
			$remainingItems = self::CBOR_MAX_TOTAL_ITEMS;
		}
		if ($depth > self::CBOR_MAX_NESTING_DEPTH) {
			throw new \UnexpectedValueException('CBOR nesting exceeds decoder limit.');
		}
		if ($remainingItems < 1) {
			throw new \UnexpectedValueException('CBOR item count exceeds decoder limit.');
		}
		$remainingItems--;

		if ($offset >= $length) {
			throw new \UnexpectedValueException('Unexpected end of CBOR data.');
		}

		$start = $offset;
		$initial = ord($data[$offset]);
		$offset++;

		$majorType = ($initial >> 5) & 0x07;
		$additionalInformation = $initial & 0x1f;
		$value = null;

		switch ($majorType) {
			case 0:
				$value = $this->decode_cbor_length($data, $offset, $additionalInformation);
				break;
			case 1:
				$value = -1 - $this->decode_cbor_length($data, $offset, $additionalInformation);
				break;
			case 2:
				$byteLength = $this->decode_cbor_length($data, $offset, $additionalInformation, self::CBOR_MAX_INPUT_BYTES);
				$this->ensure_cbor_bytes($data, $offset, $byteLength);
				$value = Model_Crypto::substr($data, $offset, $byteLength);
				$offset += $byteLength;
				break;
			case 3:
				$stringLength = $this->decode_cbor_length($data, $offset, $additionalInformation, self::CBOR_MAX_INPUT_BYTES);
				$this->ensure_cbor_bytes($data, $offset, $stringLength);
				$value = Model_Crypto::substr($data, $offset, $stringLength);
				$offset += $stringLength;
				break;
			case 4:
				$itemCount = $this->decode_cbor_length($data, $offset, $additionalInformation, self::CBOR_MAX_COLLECTION_ITEMS);
				$this->ensure_cbor_collection_items($data, $offset, $itemCount, 1, $remainingItems);
				$value = array();
				for ($i = 0; $i < $itemCount; $i++) {
					$itemRawBytes = null;
					$value[] = $this->decode_cbor_item($data, $offset, $offset, $itemRawBytes, $depth + 1, $remainingItems);
				}
				break;
			case 5:
				$pairCount = $this->decode_cbor_length($data, $offset, $additionalInformation, self::CBOR_MAX_COLLECTION_ITEMS);
				$this->ensure_cbor_collection_items($data, $offset, $pairCount, 2, $remainingItems);
				$value = array();
				for ($i = 0; $i < $pairCount; $i++) {
					$keyRawBytes = null;
					$valueRawBytes = null;
					$key = $this->decode_cbor_item($data, $offset, $offset, $keyRawBytes, $depth + 1, $remainingItems);
					$valueKey = is_int($key) || is_string($key) ? $key : json_encode($key);
					$value[$valueKey] = $this->decode_cbor_item($data, $offset, $offset, $valueRawBytes, $depth + 1, $remainingItems);
				}
				break;
			case 7:
				switch ($additionalInformation) {
					case 20:
						$value = false;
						break;
					case 21:
						$value = true;
						break;
					case 22:
						$value = null;
						break;
					default:
						throw new \UnexpectedValueException('Unsupported CBOR simple value.');
				}
				break;
			default:
				throw new \UnexpectedValueException('Unsupported CBOR major type.');
		}

		$nextOffset = $offset;
		$rawBytes = Model_Crypto::substr($data, $start, $offset - $start);
		return $value;
	}

	/**
	 * Decodes a CBOR map while preserving each entry's encoded key and value types.
	 *
	 * @param string $data CBOR input.
	 * @param int $offset Starting byte offset.
	 * @param int|null $nextOffset Receives the first byte after the map.
	 * @return array Ordered decoded map entries.
	 */
	private function decode_cbor_map_entries($data, $offset, &$nextOffset = null) {
		$length = Model_Crypto::strlen($data);
		if ($length > self::CBOR_MAX_INPUT_BYTES || $offset >= $length) {
			throw new \UnexpectedValueException('Invalid CBOR map input.');
		}

		$initial = ord($data[$offset]);
		if ((($initial >> 5) & 0x07) !== 5) {
			throw new \UnexpectedValueException('Expected CBOR map.');
		}
		$offset++;
		$remainingItems = self::CBOR_MAX_TOTAL_ITEMS - 1;
		$pairCount = $this->decode_cbor_length($data, $offset, $initial & 0x1f, self::CBOR_MAX_COLLECTION_ITEMS);
		$this->ensure_cbor_collection_items($data, $offset, $pairCount, 2, $remainingItems);

		$entries = array();
		for ($i = 0; $i < $pairCount; $i++) {
			$key = $this->decode_cbor_item($data, $offset, $offset, $keyRaw, 1, $remainingItems);
			if (!is_int($key) && !is_string($key)) {
				throw new \UnexpectedValueException('Unsupported CBOR map key type.');
			}
			$value = $this->decode_cbor_item($data, $offset, $offset, $valueRaw, 1, $remainingItems);
			$entries[] = array(
				'key' => $key,
				'key_raw' => $keyRaw,
				'value' => $value,
				'value_raw' => $valueRaw,
			);
		}

		$nextOffset = $offset;
		return $entries;
	}

	/**
	 * Returns the CBOR major type encoded by an item's first byte.
	 *
	 * @param string $rawItem Encoded CBOR item.
	 * @return int|null Major type, or null for empty input.
	 */
	private function cbor_major_type($rawItem) {
		return is_string($rawItem) && $rawItem !== '' ? ((ord($rawItem[0]) >> 5) & 0x07) : null;
	}

	private function decode_cbor_length($data, &$offset, $additionalInformation, $maxValue = null) {
		if ($additionalInformation < 24) {
			return $this->ensure_cbor_length_within_limit($additionalInformation, $maxValue);
		}

		switch ($additionalInformation) {
			case 24:
				$this->ensure_cbor_bytes($data, $offset, 1);
				$value = ord($data[$offset]);
				$offset += 1;
				return $this->ensure_cbor_length_within_limit($value, $maxValue);
			case 25:
				$this->ensure_cbor_bytes($data, $offset, 2);
				$value = unpack('nvalue', Model_Crypto::substr($data, $offset, 2));
				$offset += 2;
				return $this->ensure_cbor_length_within_limit($value['value'], $maxValue);
			case 26:
				$this->ensure_cbor_bytes($data, $offset, 4);
				$value = unpack('Nvalue', Model_Crypto::substr($data, $offset, 4));
				$offset += 4;
				return $this->ensure_cbor_length_within_limit($value['value'], $maxValue);
			case 27:
				$this->ensure_cbor_bytes($data, $offset, 8);
				$parts = unpack('Nhigh/Nlow', Model_Crypto::substr($data, $offset, 8));
				$offset += 8;
				if (PHP_INT_SIZE >= 8) {
					$maxHigh = 0x7fffffff;
					$maxLow = 0xffffffff;
					if ($parts['high'] > $maxHigh || ($parts['high'] === $maxHigh && $parts['low'] > $maxLow)) {
						throw new \UnexpectedValueException('Unsupported large CBOR integer.');
					}
					$value = ($parts['high'] * 4294967296) + $parts['low'];
					return $this->ensure_cbor_length_within_limit($value, $maxValue);
				}
				if ($parts['high'] === 0 && $parts['low'] <= PHP_INT_MAX) {
					return $this->ensure_cbor_length_within_limit($parts['low'], $maxValue);
				}
				throw new \UnexpectedValueException('Unsupported large CBOR integer on this platform.');
			default:
				throw new \UnexpectedValueException('Unsupported CBOR length encoding.');
		}
	}

	private function ensure_cbor_length_within_limit($value, $maxValue) {
		if (!is_int($value) || $value < 0) {
			throw new \UnexpectedValueException('Unsupported CBOR length.');
		}
		if ($maxValue !== null && $value > $maxValue) {
			throw new \UnexpectedValueException('CBOR length exceeds decoder limit.');
		}
		return $value;
	}

	private function ensure_cbor_bytes($data, $offset, $requiredLength) {
		if (Model_Crypto::strlen($data) < $offset + $requiredLength) {
			throw new \UnexpectedValueException('Unexpected end of CBOR data.');
		}
	}

	private function ensure_cbor_collection_items($data, $offset, $entryCount, $minimumItemsPerEntry, $remainingItems) {
		$remainingBytes = Model_Crypto::strlen($data) - $offset;
		if ($entryCount > intdiv($remainingBytes, $minimumItemsPerEntry)) {
			throw new \UnexpectedValueException('CBOR collection exceeds available data.');
		}
		if ($entryCount > intdiv($remainingItems, $minimumItemsPerEntry)) {
			throw new \UnexpectedValueException('CBOR collection exceeds decoder limit.');
		}
	}
}
