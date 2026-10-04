<?php

namespace WordfenceLS;

use WordfenceLS\Settings\Model_DB;
use WordfenceLS\Settings\Model_WPOptions;
use WordfenceLS\Utility_Number;

class Controller_Settings {
	//Configurable
	const OPTION_XMLRPC_ENABLED = 'xmlrpc-enabled';
	const OPTION_ENABLE_2FA = 'enable-2fa';
	const OPTION_ENABLE_PASSKEYS = 'enable-passkeys';
	const DEFAULT_ENABLE_2FA = false;
	const DEFAULT_ENABLE_PASSKEYS = false;
	const LEGACY_DEFAULT_ENABLE_2FA = true;
	const LEGACY_DEFAULT_ENABLE_PASSKEYS = true;
	const OPTION_2FA_WHITELISTED = 'whitelisted';
	const OPTION_IP_SOURCE = 'ip-source';
	const OPTION_IP_TRUSTED_PROXIES = 'ip-trusted-proxies';
	const OPTION_REQUIRE_2FA_ADMIN = 'require-2fa.administrator';
	const OPTION_REQUIRE_2FA_GRACE_PERIOD_ENABLED = 'require-2fa-grace-period-enabled';
	const OPTION_REQUIRE_2FA_GRACE_PERIOD = 'require-2fa-grace-period';
	const OPTION_REQUIRE_2FA_USER_GRACE_PERIOD = '2fa-user-grace-period';
	const OPTION_PASSKEY_ALLOWED_HOSTNAMES = 'passkey-allowed-hostnames';
	const OPTION_PASSKEY_RELYING_PARTY_OVERRIDE = 'passkey-relying-party-override';
	const OPTION_PASSKEY_SIGN_COUNT_MODE = 'passkey-sign-count-mode';
	const OPTION_REMEMBER_DEVICE_ENABLED = 'remember-device';
	const OPTION_REMEMBER_DEVICE_DURATION = 'remember-device-duration';
	const OPTION_ALWAYS_SHOW_LOGIN_SECURITY_MENU = 'always-show-login-security-menu';
	const OPTION_ALLOW_XML_RPC = 'allow-xml-rpc';
	const OPTION_ENABLE_AUTH_CAPTCHA = 'enable-auth-captcha';
	const OPTION_CAPTCHA_TEST_MODE = 'recaptcha-test-mode';
	const OPTION_RECAPTCHA_SITE_KEY = 'recaptcha-site-key';
	const OPTION_RECAPTCHA_SECRET = 'recaptcha-secret';
	const OPTION_RECAPTCHA_THRESHOLD = 'recaptcha-threshold';
	const OPTION_DELETE_ON_DEACTIVATION = 'delete-deactivation';
	const OPTION_PREFIX_REQUIRED_2FA_ROLE = 'required-2fa-role';
	const OPTION_PREFIX_REQUIRED_PASSKEY_ROLE = 'required-passkey-role';
	const OPTION_ENABLE_WOOCOMMERCE_INTEGRATION = 'enable-woocommerce-integration';
	const OPTION_ENABLE_WOOCOMMERCE_ACCOUNT_INTEGRATION = 'enable-woocommerce-account-integration';
	const OPTION_ENABLE_SHORTCODE = 'enable-shortcode';
	const OPTION_ENABLE_LOGIN_HISTORY_COLUMNS = 'enable-login-history-columns';
	const OPTION_STACK_UI_COLUMNS = 'stack-ui-columns';
	
	//Internal
	const OPTION_GLOBAL_NOTICES = 'global-notices';
	const OPTION_LAST_SECRET_REFRESH = 'last-secret-refresh';
	const OPTION_USE_NTP = 'use-ntp';
	const OPTION_ALLOW_DISABLING_NTP = 'allow-disabling-ntp';
	const OPTION_NTP_FAILURE_COUNT = 'ntp-failure-count';
	const OPTION_NTP_OFFSET = 'ntp-offset';
	const OPTION_SHARED_HASH_SECRET_KEY = 'shared-hash-secret';
	const OPTION_SHARED_SYMMETRIC_SECRET_KEY = 'shared-symmetric-secret';
	const OPTION_DISMISSED_FRESH_INSTALL_MODAL = 'dismissed-fresh-install-modal';
	const OPTION_CAPTCHA_STATS = 'captcha-stats';
	const OPTION_SCHEMA_VERSION = 'schema-version';
	const OPTION_USER_COUNT_QUERY_STATE = 'user-count-query-state';
	const OPTION_DISABLE_TEMPORARY_TABLES = 'disable-temporary-tables';
	const OPTION_LAST_PASSKEY_RP = 'last-passkey-rp';
	const OPTION_PUBLIC_SUFFIX_LIST = 'public-suffix-list';
	const OPTION_PUBLIC_SUFFIX_LIST_ETAG = 'public-suffix-list-etag';
	const OPTION_PASSKEY_HOSTNAME_WARNING_VERSION = 'passkey-hostname-warning-version';

	const DEFAULT_REQUIRE_2FA_USER_GRACE_PERIOD = 10;
	const MAX_REQUIRE_2FA_USER_GRACE_PERIOD = 99;

	const STATE_2FA_DISABLED = 'disabled';
	const STATE_2FA_OPTIONAL = 'optional';
	const STATE_2FA_REQUIRED = 'required';
	
	const STATE_PASSKEY_DISABLED = 'passkey_disabled';
	const STATE_PASSKEY_OPTIONAL = 'passkey_optional';
	const STATE_PASSKEY_REQUIRED = 'passkey_required';

	const PASSKEY_SIGN_COUNT_ALLOW = 'allow';
	const PASSKEY_SIGN_COUNT_REJECT_LOWER = 'reject-lower';
	const PASSKEY_SIGN_COUNT_REJECT_LOWER_AND_ZERO = 'reject-lower-and-zero';
	
	protected $_settingsStorage;
	
	/**
	 * Returns the singleton Controller_Settings.
	 *
	 * @return Controller_Settings
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_Settings();
		}
		return $_shared;
	}
	
	public function __construct($settingsStorage = false) {
		if (!$settingsStorage) {
			$settingsStorage = new Model_DB();
		}
		$this->_settingsStorage = $settingsStorage;
	}
	
	/**
	 * Returns a key/value array of all defaults. The value is the storage-ready value (e.g., a JSON string for array
	 * settings).
	 */
	protected function _defaults() {
		return array(
			self::OPTION_ENABLE_2FA => self::DEFAULT_ENABLE_2FA,
			self::OPTION_ENABLE_PASSKEYS => self::DEFAULT_ENABLE_PASSKEYS,
			self::OPTION_XMLRPC_ENABLED => true,
			self::OPTION_2FA_WHITELISTED => '',
			self::OPTION_IP_SOURCE => Model_Request::IP_SOURCE_AUTOMATIC,
			self::OPTION_IP_TRUSTED_PROXIES => '',
			self::OPTION_REQUIRE_2FA_ADMIN => false,
			self::OPTION_REQUIRE_2FA_GRACE_PERIOD_ENABLED => false,
			self::OPTION_REQUIRE_2FA_USER_GRACE_PERIOD => self::DEFAULT_REQUIRE_2FA_USER_GRACE_PERIOD,
			self::OPTION_PASSKEY_RELYING_PARTY_OVERRIDE => '',
			self::OPTION_PASSKEY_SIGN_COUNT_MODE => self::PASSKEY_SIGN_COUNT_REJECT_LOWER,
			self::OPTION_GLOBAL_NOTICES => '[]',
			self::OPTION_REMEMBER_DEVICE_ENABLED => false,
			self::OPTION_REMEMBER_DEVICE_DURATION => 30 * 86400,
			self::OPTION_ALWAYS_SHOW_LOGIN_SECURITY_MENU => true,
			self::OPTION_ALLOW_XML_RPC => true,
			self::OPTION_ENABLE_AUTH_CAPTCHA => false,
			self::OPTION_CAPTCHA_TEST_MODE => false,
			self::OPTION_RECAPTCHA_SITE_KEY => '',
			self::OPTION_RECAPTCHA_SECRET => '',
			self::OPTION_CAPTCHA_STATS => '{"counts": [0,0,0,0,0,0,0,0,0,0,0], "avg": 0}',
			self::OPTION_RECAPTCHA_THRESHOLD => 0.5,
			self::OPTION_LAST_SECRET_REFRESH => 0,
			self::OPTION_DELETE_ON_DEACTIVATION => false,
			self::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION => false,
			self::OPTION_ENABLE_WOOCOMMERCE_ACCOUNT_INTEGRATION => false,
			self::OPTION_ENABLE_SHORTCODE => false,
			self::OPTION_ENABLE_LOGIN_HISTORY_COLUMNS => true,
			self::OPTION_STACK_UI_COLUMNS => true,
			self::OPTION_SCHEMA_VERSION => false,
			self::OPTION_USER_COUNT_QUERY_STATE => false,
			self::OPTION_DISABLE_TEMPORARY_TABLES => false,
			self::OPTION_USE_NTP => true,
			self::OPTION_ALLOW_DISABLING_NTP => false,
			self::OPTION_NTP_FAILURE_COUNT => 0,
			self::OPTION_NTP_OFFSET => 0,
			self::OPTION_DISMISSED_FRESH_INSTALL_MODAL => false,
			self::OPTION_PASSKEY_HOSTNAME_WARNING_VERSION => 0,
		);
	}
	
	public function set_defaults() {
		$defaults = $this->_defaults();
		$defaults = array_column(array_map(function($k, $v) {
			return array('k' => $k, 'v' => array(
				'value' => $v,
				'autoload' => Model_Settings::AUTOLOAD_YES,
				'allowOverwrite' => false,
			));
			}, array_keys($defaults), array_values($defaults)), 'v', 'k');
		$this->_settingsStorage->set_multiple($defaults);
	}
	
	public function set($key, $value, $already_validated = false) {
		return $this->set_multiple(array($key => $value), $already_validated);
	}
	
	public function set_multiple($changes, $already_validated = false) {
		if (!$already_validated && $this->validate_multiple($changes) !== true) {
			return false;
		}
		$changes = $this->clean_multiple($changes);
		$changes = $this->preprocess_multiple($changes);
		$this->add_passkey_hostname_warning_version_update($changes);
		$this->_settingsStorage->set_multiple($changes);
		return true;
	}
	
	public function get($key, $default = false) {
		return $this->_settingsStorage->get($key, $default);
	}
	
	public function get_bool($key, $default = false) {
		return Utility_Number::truthyToBool($this->get($key, $default));
	}
	
	public function get_int($key, $default = 0) {
		return intval($this->get($key, $default));
	}
	
	public function get_float($key, $default = 0.0) {
		return (float) $this->get($key, $default);
	}
	
	public function get_array($key, $default = array()) {
		$value = $this->get($key, null);
		if (is_string($value)) {
			$value = @json_decode($value, true);
		}
		else {
			$value = null;
		}
		return is_array($value) ? $value : $default;
	}
	
	public function remove($key) {
		$this->_settingsStorage->remove($key);
	}
	
	public function all() {
		$defaults = $this->_defaults();
		// Fresh installs persist disabled values; missing values are from legacy installs and retain the enabled behavior.
		$defaults[self::OPTION_ENABLE_2FA] = self::LEGACY_DEFAULT_ENABLE_2FA;
		$defaults[self::OPTION_ENABLE_PASSKEYS] = self::LEGACY_DEFAULT_ENABLE_PASSKEYS;
		$result = $this->_settingsStorage->get_multiple($defaults);
		if ($this->passkey_allowed_hostnames_missing()) {
			$result[self::OPTION_PASSKEY_ALLOWED_HOSTNAMES] = implode("\n", $this->default_passkey_allowed_hostnames(Utility_URL::get_default_public_suffix_list()));
		}
		else {
			$result[self::OPTION_PASSKEY_ALLOWED_HOSTNAMES] = $this->get(self::OPTION_PASSKEY_ALLOWED_HOSTNAMES, '');
		}
		foreach ($result as $key => &$value) {
			$value = $this->inflate($key, $value);
		}
		return $result;
	}
	
	/**
	 * Validates whether a user-entered setting value is acceptable. Returns true if valid or an error message if not.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return bool|string
	 */
	public function validate($key, $value) {
		switch ($key) {
			//Boolean
			case self::OPTION_XMLRPC_ENABLED:
			case self::OPTION_ENABLE_2FA:
			case self::OPTION_ENABLE_PASSKEYS:
			case self::OPTION_REQUIRE_2FA_ADMIN:
			case self::OPTION_REQUIRE_2FA_GRACE_PERIOD_ENABLED:
			case self::OPTION_REMEMBER_DEVICE_ENABLED:
			case self::OPTION_ALWAYS_SHOW_LOGIN_SECURITY_MENU:
			case self::OPTION_ALLOW_XML_RPC:
			case self::OPTION_ENABLE_AUTH_CAPTCHA:
			case self::OPTION_CAPTCHA_TEST_MODE:
			case self::OPTION_DISMISSED_FRESH_INSTALL_MODAL:
			case self::OPTION_DELETE_ON_DEACTIVATION:
			case self::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION:
			case self::OPTION_ENABLE_WOOCOMMERCE_ACCOUNT_INTEGRATION:
			case self::OPTION_ENABLE_SHORTCODE:
			case self::OPTION_ENABLE_LOGIN_HISTORY_COLUMNS:
			case self::OPTION_STACK_UI_COLUMNS:
			case self::OPTION_USER_COUNT_QUERY_STATE:
			case self::OPTION_DISABLE_TEMPORARY_TABLES:
				return true;
				
			//Int
			case self::OPTION_LAST_SECRET_REFRESH:
				return is_numeric($value); //Left using is_numeric to prevent issues with existing values
			case self::OPTION_SCHEMA_VERSION:
			case self::OPTION_PASSKEY_HOSTNAME_WARNING_VERSION:
				return Utility_Number::isInteger($value, 0);
				
			//Array
			case self::OPTION_GLOBAL_NOTICES:
			case self::OPTION_CAPTCHA_STATS:
				return is_array($value);
				
			//Special
			case self::OPTION_IP_TRUSTED_PROXIES:
			case self::OPTION_2FA_WHITELISTED:
				$value = !is_string($value) ? '' : $value;
				$parsed = array_filter(array_map(function($s) { return trim($s); }, preg_split('/[\r\n]/', $value)));
				foreach ($parsed as $entry) {
					if (!Controller_Whitelist::shared()->is_valid_range($entry)) {
						return sprintf(/* translators: IP or range */ __('The IP/range %s is invalid.', 'wordfence'), esc_html($entry));
					}
				}
				return true;
			case self::OPTION_IP_SOURCE:
				if (!in_array($value, array(Model_Request::IP_SOURCE_AUTOMATIC, Model_Request::IP_SOURCE_REMOTE_ADDR, Model_Request::IP_SOURCE_X_FORWARDED_FOR, Model_Request::IP_SOURCE_X_REAL_IP))) {
					return __('An invalid IP source was provided.', 'wordfence');
				}
				return true;
			case self::OPTION_PASSKEY_SIGN_COUNT_MODE:
				if (!in_array($value, self::passkey_sign_count_modes(), true)) {
					return __('An invalid passkey sign-in counter setting was provided.', 'wordfence');
				}
				return true;
			case self::OPTION_REQUIRE_2FA_GRACE_PERIOD:
				$gracePeriodEnd = strtotime($value);
				if ($gracePeriodEnd <= \WordfenceLS\Controller_Time::time()) {
					return __('The grace period end time must be in the future.', 'wordfence');
				}
				return true;
			case self::OPTION_PASSKEY_RELYING_PARTY_OVERRIDE:
				$value = is_string($value) ? trim($value) : '';
				if ($value === '') {
					return true;
				}
				if (preg_match('/[\s\/\?#]/', $value) || strpos($value, '://') !== false || strpos($value, ':') !== false) {
					return __('The passkey credential domain must be a hostname only, without a protocol, port, or path.', 'wordfence');
				}
				if ($value !== 'localhost' && !preg_match('/^(?=.{1,253}$)(?!-)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i', $value)) {
					return __('The passkey credential domain must be a valid hostname.', 'wordfence');
				}
				return true;
			case self::OPTION_PASSKEY_ALLOWED_HOSTNAMES:
				$hosts = $this->parse_passkey_allowed_hostnames($value);
				if (empty($hosts)) {
					return __('At least one passkey login hostname must be allowed.', 'wordfence');
				}
				foreach ($hosts as $host) {
					if (!$this->is_valid_passkey_hostname($host)) {
						return sprintf(/* translators: hostname */ __('The passkey login hostname %s is invalid. Enter a hostname with an optional port, without a protocol or path.', 'wordfence'), esc_html($host));
					}
				}
				return true;
			case self::OPTION_REMEMBER_DEVICE_DURATION:
				return is_numeric($value) && $value > 0;
			case self::OPTION_RECAPTCHA_THRESHOLD:
				return is_numeric($value) && $value > 0 && $value <= 1;
			case self::OPTION_RECAPTCHA_SITE_KEY:
				if (empty($value)) {
					return true;
				}
				
				$response = wp_remote_get('https://www.google.com/recaptcha/api.js?render=' . urlencode($value));
				
				if (!is_wp_error($response)) {
					$status = wp_remote_retrieve_response_code($response);
					if ($status == 200) {
						return true;
					}
					
					$data = wp_remote_retrieve_body($response);
					if (strpos($data, 'grecaptcha') === false) {
						return __('Unable to validate the reCAPTCHA site key. Please check the key and try again.', 'wordfence');
					}
					return true;
				}
				return sprintf(/* translators: validation error */ __('An error was encountered while validating the reCAPTCHA site key: %s', 'wordfence'), $response->get_error_message());
			case self::OPTION_REQUIRE_2FA_USER_GRACE_PERIOD:
				if (!is_numeric($value) || $value < 0 || $value > self::MAX_REQUIRE_2FA_USER_GRACE_PERIOD) {
					return sprintf(/* translators: 1. Minimum number of days. 2. Maximum number of days. */ __('The grace period day limit must be between %1$d and %2$d.', 'wordfence'), 0, self::MAX_REQUIRE_2FA_USER_GRACE_PERIOD);
				}
				return true;
		}
		return true;
	}
	
	public function validate_multiple($values) {
		$errors = array();
		foreach ($values as $key => $value) {
			$status = $this->validate($key, $value); 
			if ($status !== true) {
				$errors[$key] = $status;
			}
		}
		
		if (!empty($errors)) {
			return $errors;
		}
		
		return true;
	}
	
	/**
	 * Cleans and normalizes a setting value for use in saving.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	public function clean($key, $value) {
		switch ($key) {
			//Boolean
			case self::OPTION_XMLRPC_ENABLED:
			case self::OPTION_ENABLE_2FA:
			case self::OPTION_ENABLE_PASSKEYS:
			case self::OPTION_REQUIRE_2FA_ADMIN:
			case self::OPTION_REQUIRE_2FA_GRACE_PERIOD_ENABLED:
			case self::OPTION_REMEMBER_DEVICE_ENABLED:
			case self::OPTION_ALWAYS_SHOW_LOGIN_SECURITY_MENU:
			case self::OPTION_ALLOW_XML_RPC:
			case self::OPTION_ENABLE_AUTH_CAPTCHA:
			case self::OPTION_CAPTCHA_TEST_MODE:
			case self::OPTION_DISMISSED_FRESH_INSTALL_MODAL:
			case self::OPTION_DELETE_ON_DEACTIVATION:
			case self::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION:
			case self::OPTION_ENABLE_WOOCOMMERCE_ACCOUNT_INTEGRATION:
			case self::OPTION_ENABLE_SHORTCODE:
			case self::OPTION_ENABLE_LOGIN_HISTORY_COLUMNS:
			case self::OPTION_STACK_UI_COLUMNS:
			case self::OPTION_USER_COUNT_QUERY_STATE:
			case self::OPTION_DISABLE_TEMPORARY_TABLES:
				return Utility_Number::truthyToBool($value);
				
			//Int
			case self::OPTION_REMEMBER_DEVICE_DURATION:
			case self::OPTION_LAST_SECRET_REFRESH:
			case self::OPTION_REQUIRE_2FA_USER_GRACE_PERIOD:
			case self::OPTION_SCHEMA_VERSION:
			case self::OPTION_PASSKEY_HOSTNAME_WARNING_VERSION:
				return (int) $value;
				
			//Float
			case self::OPTION_RECAPTCHA_THRESHOLD:
				return (float) $value;
			
			//Array
			case self::OPTION_GLOBAL_NOTICES:
			case self::OPTION_CAPTCHA_STATS:
				return json_encode($value);
			
			//Special
			case self::OPTION_IP_TRUSTED_PROXIES:
			case self::OPTION_2FA_WHITELISTED:
				$value = !is_string($value) ? '' : $value;
				$parsed = array_filter(array_map(function($s) { return trim($s); }, preg_split('/[\r\n]/', $value)));
				$cleaned = array();
				foreach ($parsed as $item) {
					$cleaned[] = $this->_sanitize_ip_range($item);
				}
				return implode("\n", $cleaned);
			case self::OPTION_REQUIRE_2FA_GRACE_PERIOD:
				$dt = $this->_parse_local_time($value);
				return $dt->format('U');
			case self::OPTION_PASSKEY_ALLOWED_HOSTNAMES:
				return implode("\n", $this->parse_passkey_allowed_hostnames($value));
			case self::OPTION_PASSKEY_RELYING_PARTY_OVERRIDE:
			case self::OPTION_RECAPTCHA_SITE_KEY:
			case self::OPTION_RECAPTCHA_SECRET:
				return trim($value);
			case self::OPTION_PASSKEY_SIGN_COUNT_MODE:
				return in_array($value, self::passkey_sign_count_modes(), true) ? $value : self::PASSKEY_SIGN_COUNT_REJECT_LOWER;
		}
		return $value;
	}
	
	/**
	 * Normalizes a setting value from its saved state into the desired type.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	public function inflate($key, $value) {
		switch ($key) {
			//Boolean
			case self::OPTION_XMLRPC_ENABLED:
			case self::OPTION_ENABLE_2FA:
			case self::OPTION_ENABLE_PASSKEYS:
			case self::OPTION_REQUIRE_2FA_ADMIN:
			case self::OPTION_REQUIRE_2FA_GRACE_PERIOD_ENABLED:
			case self::OPTION_REMEMBER_DEVICE_ENABLED:
			case self::OPTION_ALWAYS_SHOW_LOGIN_SECURITY_MENU:
			case self::OPTION_ALLOW_XML_RPC:
			case self::OPTION_ENABLE_AUTH_CAPTCHA:
			case self::OPTION_CAPTCHA_TEST_MODE:
			case self::OPTION_DISMISSED_FRESH_INSTALL_MODAL:
			case self::OPTION_DELETE_ON_DEACTIVATION:
			case self::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION:
			case self::OPTION_ENABLE_WOOCOMMERCE_ACCOUNT_INTEGRATION:
			case self::OPTION_ENABLE_SHORTCODE:
			case self::OPTION_ENABLE_LOGIN_HISTORY_COLUMNS:
			case self::OPTION_STACK_UI_COLUMNS:
			case self::OPTION_USER_COUNT_QUERY_STATE:
			case self::OPTION_DISABLE_TEMPORARY_TABLES:
				return Utility_Number::truthyToBool($value);
			
			//Int
			case self::OPTION_REMEMBER_DEVICE_DURATION:
			case self::OPTION_LAST_SECRET_REFRESH:
			case self::OPTION_REQUIRE_2FA_USER_GRACE_PERIOD:
			case self::OPTION_SCHEMA_VERSION:
				return (int) $value;
			
			//Float
			case self::OPTION_RECAPTCHA_THRESHOLD:
				return (float) $value;
			
			//Array
			case self::OPTION_GLOBAL_NOTICES:
			case self::OPTION_CAPTCHA_STATS:
				return json_decode($value, true);
			
			//Special
			case self::OPTION_IP_TRUSTED_PROXIES:
			case self::OPTION_2FA_WHITELISTED:
				$value = !is_string($value) ? '' : $value;
				return implode("\n", array_filter(array_map(function($s) { return trim($s); }, preg_split('/[\r\n]/', $value))));
		}
		return $value;
	}
	
	public function clean_multiple($changes) {
		$cleaned = array();
		foreach ($changes as $key => $value) {
			$cleaned[$key] = $this->clean($key, $value);
		}
		return $cleaned;
	}

	private function get_required_2fa_role_key($role) {
		return implode('.', array(self::OPTION_PREFIX_REQUIRED_2FA_ROLE, $role));
	}

	public function get_required_2fa_role_activation_time($role) {
		$time = $this->get_int($this->get_required_2fa_role_key($role), -1);
		if ($time < 0)
			return false;
		return $time;
	}

	public function get_user_2fa_grace_period() {
		return $this->get_int(self::OPTION_REQUIRE_2FA_USER_GRACE_PERIOD, self::DEFAULT_REQUIRE_2FA_USER_GRACE_PERIOD);
	}

	/**
	 * Returns whether two-factor authentication is globally enabled.
	 * Missing values are treated as enabled until the upgrade migration initializes them.
	 *
	 * @return bool
	 */
	public function is_2fa_enabled() {
		return $this->get_bool(self::OPTION_ENABLE_2FA, self::LEGACY_DEFAULT_ENABLE_2FA);
	}

	/**
	 * Returns whether passkeys are globally enabled.
	 * Missing values are treated as enabled until the upgrade migration initializes them.
	 *
	 * @return bool
	 */
	public function are_passkeys_enabled() {
		return $this->get_bool(self::OPTION_ENABLE_PASSKEYS, self::LEGACY_DEFAULT_ENABLE_PASSKEYS);
	}
	
	private function get_required_passkey_role_key($role) {
		return implode('.', array(self::OPTION_PREFIX_REQUIRED_PASSKEY_ROLE, $role));
	}
	
	public function get_required_passkey_role_activation_time($role) {
		if (is_multisite() && $role !== 'super-admin') {
			return false;
		}
		$time = $this->get_int($this->get_required_passkey_role_key($role), -1);
		if ($time < 0) { return false; }
		return $time;
	}
	
	public function get_user_passkey_grace_period() {
		return $this->get_user_2fa_grace_period();
	}

	/**
	 * Returns the valid passkey sign-in counter policy identifiers.
	 *
	 * @return string[]
	 */
	public static function passkey_sign_count_modes() {
		return array(
			self::PASSKEY_SIGN_COUNT_ALLOW,
			self::PASSKEY_SIGN_COUNT_REJECT_LOWER,
			self::PASSKEY_SIGN_COUNT_REJECT_LOWER_AND_ZERO,
		);
	}

	/**
	 * Returns the configured passkey sign-in counter policy.
	 *
	 * @return string
	 */
	public function passkey_sign_count_mode() {
		$mode = $this->get(self::OPTION_PASSKEY_SIGN_COUNT_MODE, self::PASSKEY_SIGN_COUNT_REJECT_LOWER);
		return in_array($mode, self::passkey_sign_count_modes(), true) ? $mode : self::PASSKEY_SIGN_COUNT_REJECT_LOWER;
	}

	/**
	 * Returns the default hostnames allowed to complete passkey registration and login.
	 *
	 * @param string[]|null $publicSuffixList Optional public suffix list override.
	 * @return string[]
	 */
	public function default_passkey_allowed_hostnames($publicSuffixList = null) {
		$hosts = array();
		foreach (array(site_url(), home_url()) as $url) {
			$host = $this->passkey_allowed_hostname_from_url($url);
			if ($host !== '') {
				$hosts[] = $host;
			}
		}

		$rpOverride = $this->normalize_passkey_hostname($this->get(self::OPTION_PASSKEY_RELYING_PARTY_OVERRIDE, ''));
		if ($rpOverride !== '' && $this->is_valid_passkey_hostname($rpOverride)) {
			$hosts[] = $rpOverride;
		}

		$rpHost = Utility_URL::reduce_to_public_suffix_plus_one(home_url(), $publicSuffixList);
		if ($rpHost === '') {
			$rpHost = Utility_URL::reduce_to_public_suffix_plus_one(site_url(), $publicSuffixList);
		}
		if ($rpHost !== '') {
			if ($this->should_add_www_passkey_hostname($rpHost)) {
				$hosts[] = 'www.' . preg_replace('/^www\./i', '', $rpHost);
			}
			$hosts[] = $rpHost;
		}

		return $this->normalize_unique_passkey_allowed_hostnames($hosts);
	}

	/**
	 * Returns the configured hostnames allowed to complete passkey registration and login.
	 *
	 * @return string[]
	 */
	public function passkey_allowed_hostnames() {
		if ($this->passkey_allowed_hostnames_missing()) {
			return $this->default_passkey_allowed_hostnames();
		}
		$value = $this->get(self::OPTION_PASSKEY_ALLOWED_HOSTNAMES, '');
		return $this->parse_passkey_allowed_hostnames($value);
	}

	/**
	 * Returns whether the allowed passkey hostnames option has never been stored.
	 *
	 * @return bool
	 */
	public function passkey_allowed_hostnames_missing() {
		$missing = new \stdClass();
		return $this->get(self::OPTION_PASSKEY_ALLOWED_HOSTNAMES, $missing) === $missing;
	}

	/**
	 * Returns the hostnames that would be stored after the first successful passkey registration.
	 *
	 * @param string $rpId RP ID used to create the passkey.
	 * @param string $origin Browser-reported registration origin URL, or a hostname for previewing pending registration.
	 * @return string[]
	 */
	public function initial_passkey_allowed_hostnames($rpId, $origin) {
		$hosts = $this->default_passkey_allowed_hostnames();
		$rpId = $this->normalize_passkey_hostname($rpId);
		if ($this->is_valid_passkey_hostname($rpId)) {
			$hosts[] = $rpId;
		}

		$originHost = $this->passkey_allowed_hostname_from_url($origin);
		if ($originHost === '') {
			$originHost = $this->normalize_passkey_allowed_hostname($origin);
		}
		if ($this->is_valid_passkey_hostname($originHost)) {
			$hosts[] = $originHost;
		}

		return $this->normalize_unique_passkey_allowed_hostnames($hosts);
	}

	/**
	 * Stores the initial allowed passkey hostnames after the first successful passkey registration.
	 *
	 * @param string $rpId RP ID used to create the passkey.
	 * @param string $origin Browser-reported registration origin.
	 * @return void
	 */
	public function set_initial_passkey_allowed_hostnames($rpId, $origin) {
		if (!$this->passkey_allowed_hostnames_missing()) {
			return;
		}

		$hosts = $this->initial_passkey_allowed_hostnames($rpId, $origin);
		if (empty($hosts)) {
			return;
		}

		$this->_settingsStorage->set(self::OPTION_PASSKEY_ALLOWED_HOSTNAMES, implode("\n", $hosts), Model_Settings::AUTOLOAD_YES, false);
		$this->bump_passkey_hostname_warning_version();
	}

	/**
	 * Adds the default allowed passkey hostnames to a pending settings save when the option has not been stored yet.
	 *
	 * @param array &$settings Pending settings changes.
	 * @return void
	 */
	private function add_default_passkey_allowed_hostnames_to_settings_if_missing(&$settings) {
		if (!$this->passkey_allowed_hostnames_missing() || array_key_exists(self::OPTION_PASSKEY_ALLOWED_HOSTNAMES, $settings)) {
			return;
		}

		$hosts = $this->default_passkey_allowed_hostnames();
		if (empty($hosts)) {
			return;
		}

		$settings[self::OPTION_PASSKEY_ALLOWED_HOSTNAMES] = implode("\n", $hosts);
	}

	/**
	 * Adds a hostname warning version bump when passkey hostname settings are changed.
	 *
	 * @param array &$settings Pending cleaned settings.
	 * @return void
	 */
	private function add_passkey_hostname_warning_version_update(&$settings) {
		$trackedSettings = array(
			self::OPTION_PASSKEY_ALLOWED_HOSTNAMES,
			self::OPTION_PASSKEY_RELYING_PARTY_OVERRIDE,
		);

		foreach ($trackedSettings as $key) {
			if (!array_key_exists($key, $settings)) {
				continue;
			}

			$missing = new \stdClass();
			if ($this->get($key, $missing) !== $settings[$key]) {
				$settings[self::OPTION_PASSKEY_HOSTNAME_WARNING_VERSION] = $this->get_int(self::OPTION_PASSKEY_HOSTNAME_WARNING_VERSION) + 1;
				return;
			}
		}
	}

	/**
	 * Increments the passkey hostname warning version.
	 *
	 * @return void
	 */
	private function bump_passkey_hostname_warning_version() {
		$this->_settingsStorage->set(self::OPTION_PASSKEY_HOSTNAME_WARNING_VERSION, $this->get_int(self::OPTION_PASSKEY_HOSTNAME_WARNING_VERSION) + 1);
	}

	/**
	 * Parses a newline-delimited hostname list into normalized hostnames.
	 *
	 * @param string|string[] $value Hostname list.
	 * @return string[]
	 */
	private function parse_passkey_allowed_hostnames($value) {
		if (is_array($value)) {
			$items = $value;
		}
		else {
			$value = is_string($value) ? $value : '';
			$items = preg_split('/[\r\n]/', $value);
		}
		return $this->normalize_unique_passkey_allowed_hostnames($items);
	}

	/**
	 * Normalizes hostnames and removes duplicates while preserving first-seen order.
	 *
	 * @param string[] $hosts Hostnames.
	 * @return string[]
	 */
	private function normalize_unique_passkey_allowed_hostnames($hosts) {
		$normalized = array();
		foreach ($hosts as $host) {
			$original = is_string($host) ? strtolower(trim($host)) : '';
			$host = $this->normalize_passkey_allowed_hostname($host);
			if ($host === '' && $original !== '') {
				// Preserve invalid entries so validation fails instead of silently dropping part of the submitted list.
				$host = $original;
			}
			if ($host !== '') {
				$normalized[$host] = $host;
			}
		}
		return array_values($normalized);
	}

	/**
	 * Parses a configured passkey hostname with an optional port.
	 *
	 * IPv6 literals may be entered bare when no port is present, but are normalized to brackets for storage.
	 *
	 * @param string $value Configured hostname entry.
	 * @return array|false Parsed host, optional port, and normalized entry, or false when invalid.
	 */
	public function parse_passkey_allowed_hostname($value) {
		if (!is_string($value)) {
			return false;
		}

		$value = strtolower(trim($value));
		if ($value === '' || preg_match('/[\s\/\?#@]/', $value) || strpos($value, '://') !== false) {
			return false;
		}

		$host = '';
		$port = null;
		if (substr($value, 0, 1) === '[') {
			if (!preg_match('/^\[([^\]]+)\](?::([0-9]+))?$/', $value, $matches)) {
				return false;
			}
			$host = $matches[1];
			if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
				return false;
			}
			if (isset($matches[2]) && $matches[2] !== '') {
				$port = (int) $matches[2];
			}
		}
		else if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$host = $value;
		}
		else {
			if (substr_count($value, ':') > 1) {
				return false;
			}
			if (strpos($value, ':') !== false) {
				list($host, $portValue) = explode(':', $value, 2);
				if ($portValue === '' || preg_match('/^[0-9]+$/D', $portValue) !== 1) {
					return false;
				}
				$port = (int) $portValue;
			}
			else {
				$host = $value;
			}
		}

		$host = $this->normalize_passkey_hostname($host);
		if (!$this->is_valid_passkey_hostname_only($host) || ($port !== null && ($port < 1 || $port > 65535))) {
			return false;
		}
		if ($port === 443) {
			$port = null;
		}

		$displayHost = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '[' . $host . ']' : $host;
		return array(
			'host' => $host,
			'port' => $port,
			'entry' => $displayHost . ($port === null ? '' : ':' . $port),
		);
	}

	/**
	 * Normalizes a configured passkey hostname and optional port for storage.
	 *
	 * @param string $value Configured hostname entry.
	 * @return string Normalized entry, or an empty string when invalid.
	 */
	private function normalize_passkey_allowed_hostname($value) {
		$parsed = $this->parse_passkey_allowed_hostname($value);
		return is_array($parsed) ? $parsed['entry'] : '';
	}

	/**
	 * Normalizes a hostname without a port for comparison and storage.
	 *
	 * @param string $host Hostname.
	 * @return string
	 */
	private function normalize_passkey_hostname($host) {
		if (!is_string($host)) {
			return '';
		}
		$host = strtolower(rtrim(trim($host), '.'));
		$unbracketed = trim($host, '[]');
		if (filter_var($unbracketed, FILTER_VALIDATE_IP)) {
			return $unbracketed;
		}
		return $host;
	}

	/**
	 * Returns whether a base hostname should also include a www-prefixed default.
	 *
	 * @param string $host Hostname.
	 * @return bool
	 */
	private function should_add_www_passkey_hostname($host) {
		$host = $this->normalize_passkey_hostname($host);
		return $host !== ''
			&& $host !== 'localhost'
			&& strpos($host, '.') !== false
			&& !filter_var(trim($host, '[]'), FILTER_VALIDATE_IP);
	}

	/**
	 * Extracts a normalized passkey hostname and optional effective port other than 443 from a URL.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function passkey_allowed_hostname_from_url($url) {
		$parts = is_string($url) ? wp_parse_url($url) : false;
		if (!is_array($parts) || empty($parts['host'])) {
			return '';
		}

		$host = $this->normalize_passkey_hostname($parts['host']);
		if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$host = '[' . $host . ']';
		}
		$scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
		$effectivePort = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : ($scheme === 'http' ? 80 : null));
		if ($effectivePort !== null && $effectivePort !== 443) {
			$host .= ':' . $effectivePort;
		}

		return $this->normalize_passkey_allowed_hostname($host);
	}

	/**
	 * Returns whether a passkey hostname entry is valid.
	 *
	 * @param string $host Hostname.
	 * @return bool
	 */
	private function is_valid_passkey_hostname($host) {
		return $this->parse_passkey_allowed_hostname($host) !== false;
	}

	/**
	 * Returns whether a normalized hostname without a port is valid for passkey origin policy.
	 *
	 * @param string $host Normalized hostname.
	 * @return bool
	 */
	private function is_valid_passkey_hostname_only($host) {
		if ($host === 'localhost') {
			return true;
		}
		if (filter_var($host, FILTER_VALIDATE_IP)) {
			return true;
		}
		return preg_match('/^(?=.{1,253}$)(?!-)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i', $host) === 1;
	}

	/**
	 * Preprocesses the value, returning true if it was saved here (e.g., saved 2fa enabled by assigning a role 
	 * capability) or false if it is to be saved by the backing storage.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @param array &$settings the array of settings to process, this function may append additional values from preprocessing
	 * @return bool
	 */
	public function preprocess($key, $value, &$settings) {
		if (preg_match('/^enabled-roles\.(.+)$/', $key, $matches)) { //Enabled roles are stored as capabilities rather than in the settings storage
			$role = $matches[1];
			if ($role === 'super-admin') {
				$roleValid = true;
			}
			else if (in_array($value, array(self::STATE_2FA_OPTIONAL, self::STATE_2FA_REQUIRED))) {
				$roleValid = Controller_Permissions::shared()->allow_2fa_self($role);
			}
			else {
				$roleValid = Controller_Permissions::shared()->disallow_2fa_self($role);
			}
			
			if (!in_array($value, array(self::STATE_2FA_OPTIONAL, self::STATE_2FA_REQUIRED))) {
				$value = self::STATE_2FA_DISABLED;
			}
			
			if ($roleValid) {
				$settings[$this->get_required_2fa_role_key($role)] = ($value === self::STATE_2FA_REQUIRED ? time() : -1);
			}
			
			/**
			 * Fires when configured or effective 2FA availability/required on a role changes.
			 *
			 * @since 1.1.13
			 *
			 * @param string $role The name of the role.
			 * @param string $state The state of 2FA on the role.
			 */
			do_action('wordfence_ls_changed_2fa_required', $role, $value);
			
			return true;
		}
		else if (preg_match('/^passkey-enabled-roles\.(.+)$/', $key, $matches)) { //Passkey-enabled roles are stored as capabilities rather than in the settings storage
			$role = $matches[1];
			if (is_multisite() && $role !== 'super-admin') {
				Controller_Permissions::shared()->disallow_passkey_self($role);
				$settings[$this->get_required_passkey_role_key($role)] = -1;
				return true;
			}
			if ($role === 'super-admin') {
				$roleValid = true;
			}
			else if (in_array($value, array(self::STATE_PASSKEY_OPTIONAL, self::STATE_PASSKEY_REQUIRED))) {
				$roleValid = Controller_Permissions::shared()->allow_passkey_self($role);
			}
			else {
				$roleValid = Controller_Permissions::shared()->disallow_passkey_self($role);
			}
			
			if (!in_array($value, array(self::STATE_PASSKEY_OPTIONAL, self::STATE_PASSKEY_REQUIRED))) {
				$value = self::STATE_PASSKEY_DISABLED;
			}
			
			if ($roleValid) {
				if (in_array($value, array(self::STATE_PASSKEY_OPTIONAL, self::STATE_PASSKEY_REQUIRED))) {
					$this->add_default_passkey_allowed_hostnames_to_settings_if_missing($settings);
				}
				$settings[$this->get_required_passkey_role_key($role)] = ($value === self::STATE_PASSKEY_REQUIRED ? time() : -1);
			}
			
			/**
			 * Fires when configured or effective passkey availability/required on a role changes.
			 *
			 * @since 2.0.0
			 *
			 * @param string $role The name of the role.
			 * @param string $state The state of passkeys on the role.
			 */
			do_action('wordfence_ls_changed_passkey_required', $role, $value);
			
			return true;
		}
		
		//Settings that will dispatch actions
		switch ($key) {
			case self::OPTION_PASSKEY_RELYING_PARTY_OVERRIDE:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					$settings[self::OPTION_LAST_PASSKEY_RP] = '';
					/**
					 * Fires when the RP override changes.
					 *
					 * @since 2.0.0
					 *
					 * @param string $before The previous value.
					 * @param string $after The new value.
					 */
					do_action('wordfence_ls_changed_rp_override', $before, $after);
				}
				break;
			case self::OPTION_ALWAYS_SHOW_LOGIN_SECURITY_MENU:
				$before = $this->get_bool($key, true);
				$after = Utility_Number::truthyToBool($value);

				if ($before != $after) {
					/**
					 * Fires when the always-show Login Security menu option is enabled/disabled.
					 *
					 * @since 2.0.0
					 *
					 * @param bool $before The previous value.
					 * @param bool $after The new value.
					 */
					do_action('wordfence_ls_always_show_login_security_menu_toggled', $before, $after);
				}
				break;
			case self::OPTION_XMLRPC_ENABLED:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when the XML-RPC 2FA requirement changes.
					 *
					 * @since 1.1.13
					 *
					 * @param bool $before The previous value.
					 * @param bool $after The new value.
					 */
					do_action('wordfence_ls_xml_rpc_2fa_toggled', $before, $after);
				}
				break;
			case self::OPTION_2FA_WHITELISTED:
				$before = $this->whitelisted_ips();
				$after = explode("\n", $value); //Already cleaned here so just re-split
					
				if ($before != $after) {
					/**
					 * Fires when the whitelist changes.
					 *
					 * @since 1.1.13
					 *
					 * @param string[] $before The previous value.
					 * @param string[] $after The new value.
					 */
					do_action('wordfence_ls_updated_allowed_ips', $before, $after);
				}
				break;
			case self::OPTION_IP_SOURCE:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when the IP source changes.
					 *
					 * @since 1.1.13
					 *
					 * @param string $before The previous value.
					 * @param string $after The new value.
					 */
					do_action('wordfence_ls_changed_ip_source', $before, $after);
				}
				break;
			case self::OPTION_IP_TRUSTED_PROXIES:
				$before = $this->trusted_proxies();
				$after = explode("\n", $value); //Already cleaned here so just re-split
				
				if (count($before) == count($after) && empty(array_diff($before, $after))) {
					/**
					 * Fires when the trusted proxy list changes.
					 *
					 * @since 1.1.13
					 *
					 * @param string[] $before The previous value.
					 * @param string[] $after The new value.
					 */
					do_action('wordfence_ls_updated_trusted_proxies', $before, $after);
				}
				break;
			case self::OPTION_REQUIRE_2FA_USER_GRACE_PERIOD:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when the grace period changes.
					 *
					 * @since 1.1.13
					 *
					 * @param int $before The previous value.
					 * @param int $after The new value.
					 */
					do_action('wordfence_ls_changed_grace_period', $before, $after);
				}
				break;
			case self::OPTION_ALLOW_XML_RPC:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when the XML-RPC is enabled/disabled.
					 *
					 * @since 1.1.13
					 *
					 * @param bool $before The previous value.
					 * @param bool $after The new value.
					 */
					do_action('wordfence_ls_xml_rpc_enabled_toggled', $before, $after);
				}
				break;
			case self::OPTION_ENABLE_AUTH_CAPTCHA:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when the login captcha is enabled/disabled.
					 *
					 * @since 1.1.13
					 *
					 * @param bool $before The previous value.
					 * @param bool $after The new value.
					 */
					do_action('wordfence_ls_captcha_enabled_toggled', $before, $after);
				}
				break;
			case self::OPTION_RECAPTCHA_THRESHOLD:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when the reCAPTCHA threshold changes.
					 *
					 * @since 1.1.13
					 *
					 * @param float $before The previous value.
					 * @param float $after The new value.
					 */
					do_action('wordfence_ls_captcha_threshold_changed', $before, $after);
				}
				break;
			case self::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when WooCommerce integration is enabled/disabled.
					 *
					 * @since 1.1.13
					 *
					 * @param bool $before The previous value.
					 * @param bool $after The new value.
					 */
					do_action('wordfence_ls_woocommerce_enabled_toggled', $before, $after);
				}
				break;
			case self::OPTION_CAPTCHA_TEST_MODE:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when captcha test mode is enabled/disabled.
					 *
					 * @since 1.1.13
					 *
					 * @param bool $before The previous value.
					 * @param bool $after The new value.
					 */
					do_action('wordfence_ls_captcha_test_mode_toggled', $before, $after);
				}
				break;
		}
		
		return false;
	}
	
	public function preprocess_multiple($changes) {
		$remaining = array();
		$syncLoginSecurityMenuVisibility = false;
		$alwaysShowLoginSecurityMenu = array_key_exists(self::OPTION_ALWAYS_SHOW_LOGIN_SECURITY_MENU, $changes) ? Utility_Number::truthyToBool($changes[self::OPTION_ALWAYS_SHOW_LOGIN_SECURITY_MENU]) : null;
		$featureDefinitions = array(
			self::OPTION_ENABLE_2FA => array(
				'role_prefix' => 'enabled-roles.',
				'required_prefix' => self::OPTION_PREFIX_REQUIRED_2FA_ROLE . '.',
				'capability' => Controller_Permissions::CAP_ACTIVATE_2FA_SELF,
				'disabled_state' => self::STATE_2FA_DISABLED,
				'optional_state' => self::STATE_2FA_OPTIONAL,
				'required_state' => self::STATE_2FA_REQUIRED,
				'action' => 'wordfence_ls_changed_2fa_required',
				'multisite_roles' => true,
			),
			self::OPTION_ENABLE_PASSKEYS => array(
				'role_prefix' => 'passkey-enabled-roles.',
				'required_prefix' => self::OPTION_PREFIX_REQUIRED_PASSKEY_ROLE . '.',
				'capability' => Controller_Permissions::CAP_MANAGE_PASSKEY_SELF,
				'disabled_state' => self::STATE_PASSKEY_DISABLED,
				'optional_state' => self::STATE_PASSKEY_OPTIONAL,
				'required_state' => self::STATE_PASSKEY_REQUIRED,
				'action' => 'wordfence_ls_changed_passkey_required',
				'multisite_roles' => false,
			),
		);
		$changedFeatures = array();
		foreach ($featureDefinitions as $option => $definition) {
			if (array_key_exists($option, $changes)) {
				$enabledBefore = $option === self::OPTION_ENABLE_2FA ? $this->is_2fa_enabled() : $this->are_passkeys_enabled();
				$enabledAfter = Utility_Number::truthyToBool($changes[$option]);
				if ($enabledBefore !== $enabledAfter) {
					$definition['enabled_after'] = $enabledAfter;
					$changedFeatures[] = $definition;
				}
			}
		}
		foreach ($changes as $key => $value) {
			if ($key === self::OPTION_ALWAYS_SHOW_LOGIN_SECURITY_MENU || preg_match('/^(?:enabled-roles|passkey-enabled-roles)\./', $key)) {
				$syncLoginSecurityMenuVisibility = true;
			}
			if (!$this->preprocess($key, $value, $remaining)) {
				$remaining[$key] = $value;
			}
		}
		if ($syncLoginSecurityMenuVisibility) {
			Controller_Permissions::shared()->sync_login_security_menu_visibility(null, $alwaysShowLoginSecurityMenu);
		}
		if (!empty($changedFeatures)) {
			$wpRoles = function_exists('wp_roles') ? wp_roles() : (class_exists('WP_Roles') ? new \WP_Roles() : null);
			$roleObjects = is_object($wpRoles) && isset($wpRoles->role_objects) && is_array($wpRoles->role_objects) ? $wpRoles->role_objects : array();
			foreach ($changedFeatures as $definition) {
				$roles = array();
				if (!is_multisite() || $definition['multisite_roles']) {
					$roles = $roleObjects;
				}
				if (is_multisite()) {
					$roles['super-admin'] = null;
				}
				foreach ($changes as $key => $value) {
					if (strpos($key, $definition['role_prefix']) === 0) {
						$role = substr($key, strlen($definition['role_prefix']));
						if ($role !== '' && (!is_multisite() || $definition['multisite_roles'] || $role === 'super-admin')) {
							$roles[$role] = isset($roleObjects[$role]) ? $roleObjects[$role] : null;
						}
					}
				}

				$requiredDefaults = array();
				foreach (array_keys($roles) as $role) {
					if (!array_key_exists($definition['role_prefix'] . $role, $changes)) {
						$requiredDefaults[$definition['required_prefix'] . $role] = -1;
					}
				}
				$requiredValues = $this->_settingsStorage->get_multiple($requiredDefaults);

				foreach ($roles as $role => $roleObject) {
					$pendingRoleKey = $definition['role_prefix'] . $role;
					$roleWasChanged = array_key_exists($pendingRoleKey, $changes);
					if ($roleWasChanged) {
						$state = $changes[$pendingRoleKey];
						if (!in_array($state, array($definition['optional_state'], $definition['required_state']), true)) {
							$state = $definition['disabled_state'];
						}
					}
					else {
						$requiredKey = $definition['required_prefix'] . $role;
						if (isset($requiredValues[$requiredKey]) && (int) $requiredValues[$requiredKey] >= 0) {
							$state = $definition['required_state'];
						}
						else if ($role === 'super-admin' || (is_object($roleObject) && $roleObject->has_cap($definition['capability']))) {
							$state = $definition['optional_state'];
						}
						else {
							$state = $definition['disabled_state'];
						}
					}

					if ($state !== $definition['disabled_state'] && (!$definition['enabled_after'] || !$roleWasChanged)) {
						do_action($definition['action'], $role, $definition['enabled_after'] ? $state : $definition['disabled_state']);
					}
				}
			}
		}
		return $remaining;
	}
	
	/**
	 * Convenience
	 */
	
	/**
	 * Returns a cleaned array containing the whitelist entries.
	 * 
	 * @return array
	 */
	public function whitelisted_ips() {
		return array_filter(array_map(function($s) { return trim($s); }, preg_split('/[\r\n]/', $this->get(self::OPTION_2FA_WHITELISTED, ''))));
	}
	
	/**
	 * Returns a cleaned array containing the trusted proxy entries.
	 *
	 * @return array
	 */
	public function trusted_proxies() {
		return array_filter(array_map(function($s) { return trim($s); }, preg_split('/[\r\n]/', $this->get(self::OPTION_IP_TRUSTED_PROXIES, ''))));
	}

	public function get_ntp_failure_count() {
		return $this->get_int(self::OPTION_NTP_FAILURE_COUNT, 0);
	}

	public function reset_ntp_failure_count() {
		$this->set(self::OPTION_NTP_FAILURE_COUNT, 0);
	}

	public function increment_ntp_failure_count() {
		$count = $this->get_ntp_failure_count();
		if ($count < 0)
			return false;
		$count++;
		$this->set(self::OPTION_NTP_FAILURE_COUNT, $count);
		return $count;
	}

	public function is_ntp_disabled_via_constant() {
		return defined('WORDFENCE_LS_DISABLE_NTP') && WORDFENCE_LS_DISABLE_NTP;
	}

	public function is_ntp_enabled($requireOffset = true) {
		if ($this->is_ntp_cron_disabled())
			return false;
		if ($this->get_bool(self::OPTION_USE_NTP, true)) {
			if ($requireOffset) {
				$offset = $this->get(self::OPTION_NTP_OFFSET, null);
				return $offset !== null && abs((int)$offset) <= Controller_TOTP::TIME_WINDOW_LENGTH;
			}
			else {
				return true;
			}
		}
		return false;
	}

	public function is_ntp_cron_disabled(&$failureCount = null) {
		if ($this->is_ntp_disabled_via_constant())
			return true;
		$failureCount = $this->get_ntp_failure_count();
		if ($failureCount >= Controller_Time::FAILURE_LIMIT) {
			return true;
		}
		else if ($failureCount < 0) {
			$failureCount = 0;
			return true;
		}
		return false;
	}

	public function disable_ntp_cron() {
		$this->set(self::OPTION_NTP_FAILURE_COUNT, -1);
	}

	public function are_login_history_columns_enabled() {
		return Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ENABLE_LOGIN_HISTORY_COLUMNS, true);
	}

	public function should_stack_ui_columns() {
		return self::shared()->get_bool(Controller_Settings::OPTION_STACK_UI_COLUMNS, true);
	}

	public function should_always_show_login_security_menu() {
		return $this->get_bool(self::OPTION_ALWAYS_SHOW_LOGIN_SECURITY_MENU, true);
	}

	/**
	 * Utility
	 */
	
	/**
	 * Parses the given time string and returns its DateTime with the server's configured time zone.
	 * 
	 * @param string $timestring
	 * @return \DateTime
	 */
	protected function _parse_local_time($timestring) {
		$utc = new \DateTimeZone('UTC');
		$tz = get_option('timezone_string');
		if (!empty($tz)) {
			$tz = new \DateTimeZone($tz);
			return new \DateTime($timestring, $tz);
		}
		else {
			$gmt = get_option('gmt_offset');
			if (!empty($gmt)) {
				if (PHP_VERSION_ID < 50510) {
					$timestamp = strtotime($timestring);
					$dtStr = gmdate("c", (int) ($timestamp + $gmt * 3600)); //Have to do it this way because of < PHP 5.5.10
					return new \DateTime($dtStr, $utc);
				}
				else {
					$direction = ($gmt > 0 ? '+' : '-');
					$gmt = abs($gmt);
					$h = (int) $gmt;
					$m = ($gmt - $h) * 60;
					$tz = new \DateTimeZone($direction . str_pad($h, 2, '0', STR_PAD_LEFT) . str_pad($m, 2, '0', STR_PAD_LEFT));
					return new \DateTime($timestring, $tz);
				}
			}
		}
		return new \DateTime($timestring);
	}
	
	/**
	 * Cleans a user-entered IP range of unnecessary characters and normalizes some glyphs.
	 *
	 * @param string $range
	 * @return string
	 */
	protected function _sanitize_ip_range($range) {
		$range = preg_replace('/\s/', '', $range); //Strip whitespace
		$range = preg_replace('/[\\x{2013}-\\x{2015}]/u', '-', $range); //Non-hyphen dashes to hyphen
		$range = strtolower($range);
		
		if (preg_match('/^\d+-\d+$/', $range)) { //v5 32 bit int style format
			list($start, $end) = explode('-', $range);
			$start = long2ip($start);
			$end = long2ip($end);
			$range = "{$start}-{$end}";
		}
		
		return $range;
	}

	/**
	 * Migrates the legacy administrator-only 2FA requirement setting to role-based requirement settings.
	 *
	 * @return void
	 */
	public function migrate_admin_2fa_requirements_to_roles() {
		if (!$this->get_bool(self::OPTION_REQUIRE_2FA_ADMIN))
			return;
		$time = time();
		if (is_multisite()) {
			$this->set($this->get_required_2fa_role_key('super-admin'), $time, true);
		}
		else {
			$roles = new \WP_Roles();
			foreach ($roles->roles as $key => $data) {
				$role = $roles->get_role($key);
				if (Controller_Permissions::shared()->can_role_manage_settings($role) && Controller_Permissions::shared()->allow_2fa_self($role->name)) {
					$this->set($this->get_required_2fa_role_key($role->name), $time, true);
				}
			}
		}
		$this->remove(self::OPTION_REQUIRE_2FA_ADMIN);
		$this->remove(self::OPTION_REQUIRE_2FA_GRACE_PERIOD);
		$this->remove(self::OPTION_REQUIRE_2FA_GRACE_PERIOD_ENABLED);
	}

	public function reset_ntp_disabled_flag() {
		$this->remove(self::OPTION_USE_NTP);
		$this->remove(self::OPTION_NTP_OFFSET);
		$this->remove(self::OPTION_NTP_FAILURE_COUNT);
	}
}
