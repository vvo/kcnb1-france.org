<?php

namespace WordfenceLS;

use WordfenceLS\Crypto\Model_JWT;
use WordfenceLS\Crypto\Model_Symmetric;
use WordfenceLS\Utility_Number;

class Controller_AJAX {

	const MAX_USERS_TO_NOTIFY = 100;

	protected $_actions = null; //Populated on init
	
	/**
	 * Returns the singleton Controller_AJAX.
	 *
	 * @return Controller_AJAX
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_AJAX();
		}
		return $_shared;
	}

	/**
	 * Returns error codes that should be masked in the AJAX login flow.
	 *
	 * @return array
	 */
	private static function masked_authentication_error_codes() {
		return array(
			'invalid_username',
			'invalid_email',
			'incorrect_password',
			'authentication_failed',
			'wfls_passkey_role_password_auth_disabled',
			'wfls_passkey_user_password_auth_disabled',
		);
	}

	/**
	 * Returns whether an authentication error should use the masked login message.
	 *
	 * @param string $code Authentication error code.
	 * @return bool
	 */
	private static function is_masked_authentication_error_code($code) {
		return in_array($code, self::masked_authentication_error_codes(), true);
	}

	/**
	 * Returns the masked AJAX login error message.
	 *
	 * @return string
	 */
	private static function masked_login_error_message() {
		if (Controller_Settings::shared()->are_passkeys_enabled() && Controller_Users::shared()->any_passkey_active()) {
			return wp_kses(sprintf(
				/* translators: 1. Forgot password URL. 2. Passkey recovery help URL. */
				__('<strong>ERROR</strong>: The username or password you entered is incorrect, or the account you were trying to authenticate as requires logging in using a passkey. <a href="%1$s" title="Password Lost and Found">Lost your password</a> or <a href="%2$s" title="Passkey recovery help">need help with a lost passkey</a>?', 'wordfence'),
				wp_lostpassword_url(),
				Controller_Support::esc_supportURL(Controller_Support::ITEM_MODULE_LOGIN_SECURITY_PASSKEY_REQUIRED)
			), array('strong'=>array(), 'a'=>array('href'=>array(), 'title'=>array())));
		}

		return wp_kses(sprintf(
			/* translators: Forgot password URL */
			__('<strong>ERROR</strong>: The username or password you entered is incorrect. <a href="%s" title="Password Lost and Found">Lost your password</a>?', 'wordfence'),
			wp_lostpassword_url()
		), array('strong'=>array(), 'a'=>array('href'=>array(), 'title'=>array())));
	}

	public function init() {
		$this->_actions = array(      
			'authenticate' => array(
				'handler' => array($this, '_ajax_authenticate_callback'),
				'nopriv' => true,
				'nonce' => false,
				'permissions' => array(), //Format is 'permission' => 'error message'
				'required_parameters' => array(),
			),
			'begin_passkey_login' => array(
				'handler' => array($this, '_ajax_begin_passkey_login_callback'),
				'nopriv' => true,
				'nonce' => false,
				'permissions' => array(),
				'required_parameters' => array(),
			),
			'finish_passkey_login' => array(
				'handler' => array($this, '_ajax_finish_passkey_login_callback'),
				'nopriv' => true,
				'nonce' => false,
				'permissions' => array(),
				'required_parameters' => array('token', 'credential'),
			),
			'register_support' => array(
				'handler' => array($this, '_ajax_register_support_callback'),
				'nopriv' => true,
				'nonce' => false,
				'permissions' => array(),
				'required_parameters' => array('wfls-message-nonce', 'wfls-message'),
			),
			'activate' => array(
				'handler' => array($this, '_ajax_activate_callback'),
				'permissions' => array(),
				'required_parameters' => array('nonce', 'secret', 'recovery', 'code', 'user'),
			),
			'deactivate' => array(
				'handler' => array($this, '_ajax_deactivate_callback'),
				'permissions' => array(),
				'required_parameters' => array('nonce', 'user'),
			),
			'regenerate' => array(
				'handler' => array($this, '_ajax_regenerate_callback'),
				'permissions' => array(),
				'required_parameters' => array('nonce', 'user'),
			),
			'begin_passkey_registration' => array(
				'handler' => array($this, '_ajax_begin_passkey_registration_callback'),
				'permissions' => array(),
				'required_parameters' => array('nonce', 'user'),
			),
			'finish_passkey_registration' => array(
				'handler' => array($this, '_ajax_finish_passkey_registration_callback'),
				'permissions' => array(),
				'required_parameters' => array('nonce', 'user', 'token', 'credential'),
			),
			'remove_passkey' => array(
				'handler' => array($this, '_ajax_remove_passkey_callback'),
				'permissions' => array(),
				'required_parameters' => array('nonce', 'user', 'passkey_id'),
			),
			'set_passkey_password_auth' => array(
				'handler' => array($this, '_ajax_set_passkey_password_auth_callback'),
				'permissions' => array(),
				'required_parameters' => array('nonce', 'user', 'enabled'),
			),
			'save_options' => array(
				'handler' => array($this, '_ajax_save_options_callback'),
				'permissions' => array(Controller_Permissions::CAP_MANAGE_SETTINGS => function() { return __('You do not have permission to change options.', 'wordfence'); }), //These are deliberately written as closures to be executed later so that WP doesn't load the translations too early, which can cause it not to pick up user-specific language settings
				'required_parameters' => array('nonce', 'changes'),
			),
			'update_public_suffix_list' => array(
				'handler' => array($this, '_ajax_update_public_suffix_list_callback'),
				'permissions' => array(Controller_Permissions::CAP_MANAGE_SETTINGS => function() { return __('You do not have permission to update the public suffix list.', 'wordfence'); }),
				'required_parameters' => array(),
			),
			'save_public_suffix_list_update' => array(
				'handler' => array($this, '_ajax_save_public_suffix_list_update_callback'),
				'permissions' => array(Controller_Permissions::CAP_MANAGE_SETTINGS => function() { return __('You do not have permission to update the public suffix list.', 'wordfence'); }),
				'required_parameters' => array('token'),
			),
			'send_grace_period_notification' => array(
				'handler' => array($this, '_ajax_send_grace_period_notification_callback'),
				'permissions' => array(Controller_Permissions::CAP_MANAGE_SETTINGS => function() { return __('You do not have permission to send notifications.', 'wordfence'); }),
				'required_parameters' => array('nonce', 'role', 'url'),
			),
			'update_ip_preview' => array(
				'handler' => array($this, '_ajax_update_ip_preview_callback'),
				'permissions' => array(Controller_Permissions::CAP_MANAGE_SETTINGS => function() { return __('You do not have permission to change options.', 'wordfence'); }),
				'required_parameters' => array('nonce', 'ip_source', 'ip_source_trusted_proxies'),
			),
			'dismiss_notice' => array(
				'handler' => array($this, '_ajax_dismiss_notice_callback'),
				'permissions' => array(),
				'required_parameters' => array('nonce', 'id'),
			),
			'reset_recaptcha_stats' => array(
				'handler' => array($this, '_ajax_reset_recaptcha_stats_callback'),
				'permissions' => array(Controller_Permissions::CAP_MANAGE_SETTINGS => function() { return __('You do not have permission to reset reCAPTCHA statistics.', 'wordfence'); }),
				'required_parameters' => array('nonce'),
			),
			'reset_2fa_grace_period' => array (
				'handler' => array($this, '_ajax_reset_2fa_grace_period_callback'),
				'permissions' => array(Controller_Permissions::CAP_MANAGE_SETTINGS => function() { return __('You do not have permission to reset the authentication grace period.', 'wordfence'); }),
				'required_parameters' => array('nonce', 'user_id')
			),
			'revoke_2fa_grace_period' => array (
				'handler' => array($this, '_ajax_revoke_2fa_grace_period_callback'),
				'permissions' => array(Controller_Permissions::CAP_MANAGE_SETTINGS => function() { return __('You do not have permission to revoke the authentication grace period.', 'wordfence'); }),
				'required_parameters' => array('nonce', 'user_id')
			),
			'reset_ntp_failure_count' => array(
				'handler' => array($this, '_ajax_reset_ntp_failure_count_callback'),
				'permissions' => array(Controller_Permissions::CAP_MANAGE_SETTINGS => function() { return __('You do not have permission to reset the NTP failure count.', 'wordfence'); }),
				'required_parameters' => array(),
			),
			'disable_ntp' => array(
				'handler' => array($this, '_ajax_disable_ntp_callback'),
				'permissions' => array(Controller_Permissions::CAP_MANAGE_SETTINGS => function() { return __('You do not have permission to disable NTP.', 'wordfence'); }),
				'required_parameters' => array(),
			),
			'dismiss_persistent_notice' => array(
				'handler' => array($this, '_ajax_dismiss_persistent_notice_callback'),
				'permissions' => array(Controller_Permissions::CAP_MANAGE_SETTINGS => function() { return __('You do not have permission to dismiss this notice.', 'wordfence'); }),
				'required_parameters' => array('nonce', 'notice_id')
			),
			'dismiss_passkey_hostname_lockout_notice' => array(
				'handler' => array($this, '_ajax_dismiss_passkey_hostname_lockout_notice_callback'),
				'permissions' => array(Controller_Permissions::CAP_MANAGE_SETTINGS => function() { return __('You do not have permission to dismiss this notice.', 'wordfence'); }),
				'required_parameters' => array('nonce', 'signature')
			)
		);
		
		$this->_init_actions();
	}
	
	public function _init_actions() {
		foreach ($this->_actions as $action => $parameters) {
			if (isset($parameters['nopriv']) && $parameters['nopriv']) {
				add_action('wp_ajax_nopriv_wordfence_ls_' . $action, array($this, '_ajax_handler'));
			}
			add_action('wp_ajax_wordfence_ls_' . $action, array($this, '_ajax_handler'));
		}
	}
	
	/**
	 * This is a convenience function for sending a JSON response and ensuring that execution stops after sending
	 * since wp_die() can be interrupted.
	 *
	 * @param $response
	 * @param int|null $status_code
	 */
	public static function send_json($response, $status_code = null) {
		wp_send_json($response, $status_code);
		die();
	}
	
	public function _ajax_handler() {
		$action = (isset($_POST['action']) && is_string($_POST['action']) && $_POST['action']) ? $_POST['action'] : $_GET['action'];
		if (preg_match('~wordfence_ls_([a-zA-Z_0-9]+)$~', $action, $matches)) {
			$action = $matches[1];
			if (!isset($this->_actions[$action])) {
				self::send_json(array('error' => esc_html__('An unknown action was provided.', 'wordfence')));
			}
			
			$parameters = $this->_actions[$action];
			if (!empty($parameters['required_parameters'])) {
				foreach ($parameters['required_parameters'] as $k) {
					if (!isset($_POST[$k])) {
						self::send_json(array('error' => esc_html__('An expected parameter was not provided.', 'wordfence')));
					}
				}
			}
			
			if (!isset($parameters['nonce']) || $parameters['nonce']) {
				$nonce = (isset($_POST['nonce']) && is_string($_POST['nonce']) && $_POST['nonce']) ? $_POST['nonce'] : $_GET['nonce'];
				if (!is_string($nonce) || !wp_verify_nonce($nonce, 'wp-ajax')) {
					self::send_json(array('error' => esc_html__('Your browser sent an invalid security token. Please try reloading this page.', 'wordfence'), 'tokenInvalid' => 1));
				}
			}
			
			if (!empty($parameters['permissions'])) {
				$user = wp_get_current_user();
				foreach ($parameters['permissions'] as $permission => $error) {
					if (!user_can($user, $permission)) {
						self::send_json(array('error' => $error()));
					}
				}
			}
			
			call_user_func($parameters['handler']);
		}
	}
	
	public function _ajax_authenticate_callback() {
		$credentialKeys = array(
			'log' => 'pwd',
			'username' => 'password'
		);
		$username = null;
		$password = null;
		foreach ($credentialKeys as $usernameKey => $passwordKey) {
			if (array_key_exists($usernameKey, $_POST) && array_key_exists($passwordKey, $_POST) && is_string($_POST[$usernameKey]) && is_string($_POST[$passwordKey])) {
				$username = $_POST[$usernameKey];
				$password = $_POST[$passwordKey];
				break;
			}
		}
		if (empty($username) || empty($password)) {
			self::send_json(array('error' => wp_kses(sprintf(/* translators: Forgot password URL */ __('<strong>ERROR</strong>: A username and password must be provided. <a href="%s" title="Password Lost and Found">Lost your password</a>?', 'wordfence'), wp_lostpassword_url()), array('strong'=>array(), 'a'=>array('href'=>array(), 'title'=>array())))));
		}
		
		$legacy2FAActive = Controller_WordfenceLS::shared()->legacy_2fa_active();
		if ($legacy2FAActive) { //Legacy 2FA is active, pass it on to the authenticate filter
			self::send_json(array('login' => 1));
		}
		
		do_action_ref_array('wp_authenticate', array(&$username, &$password));

		$preflight = Controller_WordfenceLS::shared()->authenticate_preflight($username, $password);
		$user = $preflight['user'];
		if (is_object($user) && ($user instanceof \WP_User)) {
			if (!Controller_Users::shared()->has_2fa_active($user) || Controller_Whitelist::shared()->is_whitelisted(Model_Request::current()->ip()) || Controller_Users::shared()->has_remembered_2fa($user) || $preflight['combined_2fa_valid']) { //Not enabled for this user, is whitelisted, has a valid remembered cookie, or has already provided a 2FA code via the password field pass the credentials on to the normal login flow
				self::send_json(array('login' => 1));
			}
			self::send_json(array('login' => 1, 'two_factor_required' => true));
		}
		else if (is_wp_error($user)) {
			/**
			 * Filters whether account-sensitive errors are masked during AJAX authentication.
			 *
			 * @param bool $maskLoginErrors Whether to mask login errors.
			 */
			$maskLoginErrors = (bool) apply_filters('wordfence_ls_mask_login_errors', true);
			$errors = array();
			$messages = array();
			$reset = false;
			foreach ($user->get_error_codes() as $code) {
				if ($maskLoginErrors && self::is_masked_authentication_error_code($code)) {
					$errors[] = self::masked_login_error_message();
				}
				else {
					if ($code == 'wfls_twofactor_invalid') {
						$reset = true;
					}
					
					$severity = $user->get_error_data($code);
					foreach ($user->get_error_messages($code) as $error_message) {
						if ($severity == 'message') {
							$messages[] = $error_message;
						}
						else {
							$errors[] = $error_message;
						}
					}
				}
			}
			
			if (!empty($errors)) {
				$errors = implode('<br>', $errors);
				$errors = apply_filters('login_errors', $errors);
				self::send_json(array('error' => $errors, 'reset' => $reset));
			}
			
			if (!empty($messages)) {
				$messages = implode('<br>', $messages);
				$messages = apply_filters('login_errors', $messages);
				self::send_json(array('message' => $messages, 'reset' => $reset));
			}
		}
		
		self::send_json(array('error' => self::masked_login_error_message()));
	}

	public function _ajax_begin_passkey_login_callback() {
		if (!Controller_Settings::shared()->are_passkeys_enabled()) {
			self::send_json(array('error' => __('Passkeys are currently disabled.', 'wordfence')));
		}
		$rateLimit = Controller_Passkey::shared()->consume_begin_login_rate_limit();
		if (is_wp_error($rateLimit)) {
			self::send_json(array('error' => $rateLimit->get_error_message()));
		}

		$allowSameRelyingPartyFrame = isset($_POST['interim_login']) && is_string($_POST['interim_login']) && Utility_Number::truthyToBool($_POST['interim_login']);
		$result = Controller_Passkey::shared()->begin_login($allowSameRelyingPartyFrame);
		if (is_wp_error($result)) {
			self::send_json(array('error' => $result->get_error_message()));
		}

		self::send_json($result);
	}

	public function _ajax_finish_passkey_login_callback() {
		if (!Controller_Settings::shared()->are_passkeys_enabled()) {
			self::send_json(array('error' => __('Passkeys are currently disabled.', 'wordfence')));
		}
		$token = isset($_POST['token']) && is_string($_POST['token']) ? $_POST['token'] : '';
		$credential = isset($_POST['credential']) && is_array($_POST['credential']) ? $_POST['credential'] : null;
		$remember = false;
		foreach (array('rememberme', 'remember') as $key) {
			if (isset($_POST[$key])) {
				$remember = Utility_Number::truthyToBool($_POST[$key]);
				break;
			}
		}

		$user = Controller_Passkey::shared()->finish_and_authenticate_login($token, $credential, $remember);
		if (is_wp_error($user)) {
			$errorCode = $user->get_error_code();
			if (
				in_array($errorCode, array(
					'wfls_passkey_login_missing',
					'wfls_passkey_login_user_handle_mismatch',
					'wfls_passkey_login_signature_invalid',
				), true) ||
				strpos($errorCode, 'wfls_passkey_assertion_') === 0 ||
				strpos($errorCode, 'wfls_passkey_public_key_') === 0
			) {
				self::send_json(array('error' => __('The passkey login could not be completed. Please try again.', 'wordfence')));
			}
			self::send_json(array('error' => $user->get_error_message()));
		}

		$redirectTo = isset($_POST['redirect_to']) && is_string($_POST['redirect_to']) ? trim($_POST['redirect_to']) : '';
		if ($redirectTo !== '') {
			$redirectTo = wp_validate_redirect($redirectTo, '');
		}
		if ($redirectTo === '') {
			$redirectTo = admin_url();
		}

		self::send_json(array(
			'login' => 1,
			'redirect' => $redirectTo,
			'user' => $user->user_login,
		));
	}

	public function _ajax_register_support_callback() {
		$email = null;
		if (array_key_exists('email', $_POST) && is_string($_POST['email'])) {
			$email = $_POST['email'];
		}
		else if (array_key_exists('user_email', $_POST) && is_string($_POST['user_email'])) {
			$email = $_POST['user_email'];
		}
		if (
			$email === null ||
			!isset($_POST['wfls-message']) || !is_string($_POST['wfls-message']) ||
			!isset($_POST['wfls-message-nonce']) || !is_string($_POST['wfls-message-nonce'])) {
			self::send_json(array('error' => wp_kses(sprintf(__('<strong>ERROR</strong>: Unable to send message. Please refresh the page and try again.', 'wordfence')), array('strong'=>array()))));
		}
		
		$email = sanitize_email($email);
		$login = '';
		if (array_key_exists('user_login', $_POST) && is_string($_POST['user_login']))
			$login = sanitize_user($_POST['user_login']);
		$message = strip_tags($_POST['wfls-message']);
		$nonce = $_POST['wfls-message-nonce'];

		if ((isset($_POST['user_login']) && empty($login)) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($message)) {
			self::send_json(array('error' => wp_kses(sprintf(__('<strong>ERROR</strong>: Unable to send message. Please refresh the page and try again.', 'wordfence')), array('strong'=>array()))));
		}
		
		$jwt = Model_JWT::decode_jwt($_POST['wfls-message-nonce']);
		if ($jwt && isset($jwt->payload['ip']) && isset($jwt->payload['score'])) {
			$decryptedIP = Model_Symmetric::decrypt($jwt->payload['ip']);
			$decryptedScore = Model_Symmetric::decrypt($jwt->payload['score']);
			if ($decryptedIP === false || $decryptedScore === false || Model_IP::inet_pton($decryptedIP) !== Model_IP::inet_pton(Model_Request::current()->ip())) { //JWT IP and the current request's IP don't match, refuse the message
				self::send_json(array('error' => wp_kses(sprintf(__('<strong>ERROR</strong>: Unable to send message. Please refresh the page and try again.', 'wordfence')), array('strong'=>array()))));
			}
			
			$identifier = bin2hex(Model_IP::inet_pton($decryptedIP));
			$tokenBucket = new Model_TokenBucket('rate:' . $identifier, 2, 1 / (6 * Model_TokenBucket::HOUR)); //Maximum of two requests, refilling at a rate of one per six hours
			if (!$tokenBucket->consume(1)) {
				self::send_json(array('error' => wp_kses(sprintf(__('<strong>ERROR</strong>: Unable to send message. You have exceeded the maximum number of messages that may be sent at this time. Please try again later.', 'wordfence')), array('strong'=>array()))));
			}
			
			$email = array(
				'to'      => get_site_option('admin_email'),
				'subject' => __('Blocked User Registration Contact Form', 'wordfence'),
				'body'    => sprintf(/* translators: 1. IP address; 2. Username; 3. Email address; 4. Score; 5. Message */ __("A visitor blocked from registration sent the following message.\n\n----------------------------------------\n\nIP: %1\$s\nUsername: %2\$s\nEmail: %3\$s\nreCAPTCHA Score: %4\$f\n\n----------------------------------------\n\n%5\$s", 'wordfence'), $decryptedIP, $login, $email, $decryptedScore, $message),
				'headers' => '',
			);
			$success = wp_mail($email['to'], $email['subject'], $email['body'], $email['headers']);
			if ($success) {
				self::send_json(array('message' => wp_kses(sprintf(__('<strong>MESSAGE SENT</strong>: Your message was sent to the site owner.', 'wordfence')), array('strong'=>array()))));
			}
			
			self::send_json(array('error' => wp_kses(sprintf(__('<strong>ERROR</strong>: An error occurred while sending the message. Please try again.', 'wordfence')), array('strong'=>array()))));
		}
		
		self::send_json(array('error' => wp_kses(sprintf(__('<strong>ERROR</strong>: Unable to send message. Please refresh the page and try again.', 'wordfence')), array('strong'=>array()))));
	}
	
	public function _ajax_activate_callback() {
		if (!Controller_Settings::shared()->is_2fa_enabled()) {
			self::send_json(array('error' => __('Two-factor authentication is currently disabled.', 'wordfence')));
		}
		$userID = (int) Utility_Array::arrayGet($_POST, 'user', 0);
		$user = wp_get_current_user();
		if ($user->ID != $userID) {
			if (!user_can($user, Controller_Permissions::CAP_ACTIVATE_2FA_OTHERS)) {
				self::send_json(array('error' => __('You do not have permission to activate the given user.', 'wordfence')));
			}
			else {
				$user = new \WP_User($userID);
				if (!$user->exists()) {
					self::send_json(array('error' => __('The given user does not exist.', 'wordfence')));
				}
			}
		}
		else if (!user_can($user, Controller_Permissions::CAP_ACTIVATE_2FA_SELF)) {
			self::send_json(array('error' => __('You do not have permission to activate 2FA.', 'wordfence')));
		}
		
		if (Controller_Users::shared()->has_2fa_active($user)) {
			self::send_json(array('error' => __('The given user already has two-factor authentication active.', 'wordfence')));
		}
		
		$matches = (isset($_POST['secret']) && isset($_POST['code']) && is_string($_POST['secret']) && is_string($_POST['code']) && Controller_TOTP::shared()->check_code($_POST['secret'], $_POST['code']));
		if ($matches === false) {
			self::send_json(array('error' => __('The code provided does not match the expected value. Please verify that the time on your authenticator device is correct and that this server\'s time is correct.', 'wordfence')));
		}
		
		Controller_TOTP::shared()->activate_2fa($user, $_POST['secret'], $_POST['recovery'], $matches);
		Controller_Notices::shared()->remove_notice(false, 'wfls-will-be-required', $user);
		self::send_json(array(
			'activated' => 1,
			'text' => sprintf(/* translators: count */ _n('%d unused recovery code remains. You may generate a new set by clicking below.', '%d unused recovery codes remain. You may generate a new set by clicking below.', count($_POST['recovery']), 'wordfence'), count($_POST['recovery'])),
			'grace_period_visible' => false,
		));
	}
	
	public function _ajax_deactivate_callback() {
		$userID = (int) Utility_Array::arrayGet($_POST, 'user', 0);
		$user = wp_get_current_user();
		if ($user->ID != $userID) {
			if (!user_can($user, Controller_Permissions::CAP_ACTIVATE_2FA_OTHERS)) {
				self::send_json(array('error' => __('You do not have permission to deactivate the given user.', 'wordfence')));
			}
			else {
				$user = new \WP_User($userID);
				if (!$user->exists()) {
					self::send_json(array('error' => __('The user does not exist.', 'wordfence')));
				}
			}
		}
		else if (!user_can($user, Controller_Permissions::CAP_ACTIVATE_2FA_SELF)) {
			self::send_json(array('error' => __('You do not have permission to deactivate 2FA.', 'wordfence')));
		}
		
		if (!Controller_Users::shared()->has_2fa_active($user)) {
			self::send_json(array('error' => __('The user specified does not have two-factor authentication active.', 'wordfence')));
		}
		
		Controller_Users::shared()->deactivate_2fa($user);
		$requires2FA = Controller_Users::shared()->requires_2fa($user, $inGracePeriod, $requiredAt);
		$lockedOut = $requires2FA;
		self::send_json(array(
			'deactivated' => 1,
			'grace_period_visible' => $lockedOut || $inGracePeriod,
		));
	}
	
	public function _ajax_regenerate_callback() {
		$userID = (int) Utility_Array::arrayGet($_POST, 'user', 0);
		$user = wp_get_current_user();
		if ($user->ID != $userID) {
			if (!user_can($user, Controller_Permissions::CAP_ACTIVATE_2FA_OTHERS)) {
				self::send_json(array('error' => __('You do not have permission to generate new recovery codes for the given user.', 'wordfence')));
			}
			else {
				$user = new \WP_User($userID);
				if (!$user->exists()) {
					self::send_json(array('error' => __('The user does not exist.', 'wordfence')));
				}
			}
		}
		else if (!user_can($user, Controller_Permissions::CAP_ACTIVATE_2FA_SELF)) {
			self::send_json(array('error' => __('You do not have permission to generate new recovery codes.', 'wordfence')));
		}
		
		if (!Controller_Users::shared()->has_2fa_active($user)) {
			self::send_json(array('error' => __('The user specified does not have two-factor authentication active.', 'wordfence')));
		}
		
		$codes = Controller_Users::shared()->regenerate_recovery_codes($user);
		self::send_json(array('regenerated' => 1, 'recovery' => array_map(function($r) { return implode(' ', str_split(bin2hex($r), 4)); }, $codes), 'text' => sprintf(/* translators: count */ _n('%d unused recovery code remains. You may generate a new set by clicking below.', '%d unused recovery codes remain. You may generate a new set by clicking below.', count($codes), 'wordfence'), count($codes))));
	}

	/**
	 * Resolves the user being managed for passkey operations.
	 *
	 * @param int $userID
	 * @param bool $allowOtherUserManagement Whether a user other than the current viewer may be targeted.
	 * @return \WP_User|\WP_Error
	 */
	private function _resolve_managed_user($userID, $allowOtherUserManagement = true) {
		$viewer = wp_get_current_user();
		$user = $viewer;
		if ($viewer->ID != $userID) {
			if (!$allowOtherUserManagement) {
				return new \WP_Error('wfls_permission_denied', __('You may only add passkeys for your own account.', 'wordfence'));
			}
			if (!user_can($viewer, Controller_Permissions::CAP_MANAGE_PASSKEY_OTHERS)) {
				return new \WP_Error('wfls_permission_denied', __('You do not have permission to manage the given user.', 'wordfence'));
			}

			$user = new \WP_User($userID);
			if (!$user->exists()) {
				return new \WP_Error('wfls_user_missing', __('The given user does not exist.', 'wordfence'));
			}
		}
		else if (!user_can($viewer, Controller_Permissions::CAP_MANAGE_PASSKEY_SELF)) {
			return new \WP_Error('wfls_permission_denied', __('You do not have permission to manage passkeys for this account.', 'wordfence'));
		}

		if (!Controller_Users::shared()->can_manage_passkey($user)) {
			return new \WP_Error('wfls_user_disallowed', __('Passkeys are not available for the given user.', 'wordfence'));
		}

		return $user;
	}

	public function _ajax_begin_passkey_registration_callback() {
		$userID = (int) Utility_Array::arrayGet($_POST, 'user', 0);
		$user = $this->_resolve_managed_user($userID, false);
		if (is_wp_error($user)) {
			self::send_json(array('error' => $user->get_error_message()));
		}

		$label = isset($_POST['label']) && is_string($_POST['label']) ? wp_unslash($_POST['label']) : '';
		$result = Controller_Passkey::shared()->begin_registration($user, $label);
		if (is_wp_error($result)) {
			self::send_json(array('error' => $result->get_error_message()));
		}

		self::send_json($result);
	}

	public function _ajax_finish_passkey_registration_callback() {
		$userID = (int) Utility_Array::arrayGet($_POST, 'user', 0);
		$user = $this->_resolve_managed_user($userID, false);
		if (is_wp_error($user)) {
			self::send_json(array('error' => $user->get_error_message()));
		}

		$token = isset($_POST['token']) && is_string($_POST['token']) ? $_POST['token'] : '';
		$label = isset($_POST['label']) && is_string($_POST['label']) ? wp_unslash($_POST['label']) : '';
		$credential = isset($_POST['credential']) && is_array($_POST['credential']) ? $_POST['credential'] : null;
		$uiStyleContext = isset($_POST['ui_style_context']) && is_string($_POST['ui_style_context']) ? Controller_WordfenceLS::normalize_ui_style_context($_POST['ui_style_context']) : Controller_WordfenceLS::UI_STYLE_CONTEXT_WFLS;
		$result = Controller_Passkey::shared()->finish_registration($user, $token, $credential, $label, $uiStyleContext);
		if (is_wp_error($result)) {
			self::send_json(array('error' => $result->get_error_message()));
		}

		self::send_json(array(
			'registered' => 1,
			'item_html' => $result['item_html'],
		));
	}

	public function _ajax_remove_passkey_callback() {
		$userID = (int) Utility_Array::arrayGet($_POST, 'user', 0);
		$user = $this->_resolve_managed_user($userID);
		if (is_wp_error($user)) {
			self::send_json(array('error' => $user->get_error_message()));
		}

		$passkeyID = (int) Utility_Array::arrayGet($_POST, 'passkey_id', 0);
		$result = Controller_Passkey::shared()->remove_passkey($user, $passkeyID);
		if (is_wp_error($result)) {
			self::send_json(array('error' => $result->get_error_message()));
		}

		self::send_json(array('removed' => 1));
	}

	public function _ajax_set_passkey_password_auth_callback() {
		$userID = (int) Utility_Array::arrayGet($_POST, 'user', 0);
		$user = $this->_resolve_managed_user($userID);
		if (is_wp_error($user)) {
			self::send_json(array('error' => $user->get_error_message()));
		}
		if (!Controller_Passkey::shared()->can_change_username_password_auth($user)) {
			self::send_json(array('error' => __('This option cannot be changed because passkeys are required for one or more of this user\'s roles.', 'wordfence')));
		}

		$enabled = Utility_Number::truthyToBool(Utility_Array::arrayGet($_POST, 'enabled', true));
		if (!Controller_Passkey::shared()->set_username_password_auth_enabled($user, $enabled)) {
			self::send_json(array('error' => __('Unable to save the user-specific passkey options.', 'wordfence')));
		}
		$enabled = Controller_Passkey::shared()->is_username_password_auth_enabled($user);

		self::send_json(array(
			'saved' => 1,
			'enabled' => $enabled,
		));
	}

	public function _ajax_save_options_callback() {
		if (!empty($_POST['changes']) && is_string($_POST['changes']) && is_array($changes = json_decode(stripslashes($_POST['changes']), true))) {
			try {
				$errors = Controller_Settings::shared()->validate_multiple($changes);
				if ($errors !== true) {
					if (count($errors) == 1) {
						$e = array_shift($errors);
						self::send_json(array('error' => esc_html(sprintf(/* translators: Error message. */ __('An error occurred while saving the configuration: %s', 'wordfence'), $e))));
					}
					else if (count($errors) > 1) {
						$compoundMessage = array();
						foreach ($errors as $e) {
							$compoundMessage[] = esc_html($e);
						}
						self::send_json(array(
							'error' => wp_kses(sprintf(__('Errors occurred while saving the configuration: %s', 'wordfence'), '<ul><li>' . implode('</li><li>', $compoundMessage) . '</li></ul>'), array('ul'=>array(), 'li'=>array())),
							'html' => true,
						));
					}
					
					self::send_json(array(
						'error' => esc_html__('Errors occurred while saving the configuration.', 'wordfence'),
					));
				}
				
				Controller_Settings::shared()->set_multiple($changes);

				if (array_key_exists(Controller_Settings::OPTION_ENABLE_WOOCOMMERCE_ACCOUNT_INTEGRATION, $changes) || array_key_exists(Controller_Settings::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION, $changes))
					Controller_WordfenceLS::shared()->refresh_rewrite_rules();
				
				$response = array('success' => true);
				self::send_json($response);
			}
			catch (\Exception $e) {
				self::send_json(array(
					'error' => $e->getMessage(),
				));
			}
		}
		
		self::send_json(array(
			'error' => esc_html__('No configuration changes were provided to save.', 'wordfence'),
		));
	}

	public function _ajax_update_public_suffix_list_callback() {
		$result = Utility_URL::fetch_public_suffix_list_update();
		$this->_send_public_suffix_list_update_response($result);
	}

	public function _ajax_save_public_suffix_list_update_callback() {
		$token = isset($_POST['token']) && is_string($_POST['token']) ? $_POST['token'] : '';
		$result = Utility_URL::save_pending_public_suffix_list_update($token);
		$this->_send_public_suffix_list_update_response($result);
	}

	private function _send_public_suffix_list_update_response($result) {
		$status = is_array($result) && isset($result['status']) ? $result['status'] : Utility_URL::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_ERROR;
		if ($status === Utility_URL::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_UPDATED || $status === Utility_URL::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_UP_TO_DATE) {
			self::send_json(array(
				'success' => true,
				'status' => $status,
			));
		}
		if ($status === Utility_URL::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_RP_CHANGED && isset($result['token']) && is_string($result['token'])) {
			self::send_json(array(
				'success' => false,
				'status' => $status,
				'token' => $result['token'],
			));
		}
		if ($status === Utility_URL::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_INVALID) {
			self::send_json(array(
				'status' => $status,
				'error' => __('The downloaded public suffix list could not be validated. No changes were saved.', 'wordfence'),
			));
		}

		self::send_json(array(
			'status' => $status,
			'error' => __('The public suffix list update could not be completed. Please try again.', 'wordfence'),
		));
	}
	
	public function _ajax_send_grace_period_notification_callback() {
		$notifyAll = isset($_POST['notify_all']) && Utility_Number::truthyToBool($_POST['notify_all']);
		$users = Controller_Users::shared()->get_users_by_role($_POST['role'], $notifyAll ? null: self::MAX_USERS_TO_NOTIFY + 1);
		$url = $_POST['url'];
		if (!empty($url)) {
			$url = get_site_url(null, $url);
			if (filter_var($url, FILTER_VALIDATE_URL) === false) {
				self::send_json(array('error' => __('The specified URL is invalid.', 'wordfence')));
			}
		}
		$userCount = count($users);
		if (!$notifyAll && $userCount > self::MAX_USERS_TO_NOTIFY) {
			self::send_json(array('error' => sprintf(/* translators: user count */ __('More than %d users exist for the selected role. This notification is not designed to handle large groups of users. In such instances, using a different solution for notifying users of upcoming login requirements is recommended.', 'wordfence'), self::MAX_USERS_TO_NOTIFY), 'limit_exceeded' => true));
		}
		
		$sent = 0;
		$failed = 0;
		foreach ($users as $user) {
			$userUrl = $url;
			if (empty($url)) {
				$userUrl = (is_multisite() && is_super_admin($user->ID)) ? network_admin_url('admin.php?page=WFLS') : admin_url('admin.php?page=WFLS');
			}
			
			Controller_Users::shared()->requires_2fa($user, $maybe2FAInGracePeriod, $maybe2FARequiredAt);
			Controller_Users::shared()->requires_passkey($user, $maybePasskeyInGracePeriod, $maybePasskeyRequiredAt);
			$send = false;
			if ($maybe2FAInGracePeriod && $maybePasskeyInGracePeriod && !Controller_Users::shared()->has_2fa_active($user) && !Controller_Users::shared()->has_passkey_active($user)) {
				$subject = sprintf(/* translators: site url */ __('Additional authentication will soon be required on %s', 'wordfence'), home_url());
				$requiredDate = Controller_Time::format_local_time('F j, Y g:i A', min($maybe2FARequiredAt, $maybePasskeyRequiredAt));

				$message = sprintf(
				/* translators: 1. Date; 2. Configuration URL */ __('<html><body><p>You do not currently have additional authentication active on your account, which will be required beginning %1$s.</p><p><a href="%2$s">Configure Additional Authentication</a></p></body></html>', 'wordfence'), $requiredDate, htmlentities($userUrl));
				$send = true;
			}
			else if ($maybePasskeyInGracePeriod && !Controller_Users::shared()->has_passkey_active($user)) {
				$subject = sprintf(/* translators: site url */ __('A passkey will soon be required on %s', 'wordfence'), home_url());
				$requiredDate = Controller_Time::format_local_time('F j, Y g:i A', $maybePasskeyRequiredAt);
				
				$message = sprintf(
				/* translators: 1. Date; 2. Configuration URL */ __('<html><body><p>You do not currently have a passkey active on your account, which will be required beginning %1$s.</p><p><a href="%2$s">Add a Passkey</a></p></body></html>', 'wordfence'), $requiredDate, htmlentities($userUrl));
				$send = true;
			}
			else if ($maybe2FAInGracePeriod && !Controller_Users::shared()->has_2fa_active($user)) {
				$subject = sprintf(/* translators: site url */ __('2FA will soon be required on %s', 'wordfence'), home_url());
				$requiredDate = Controller_Time::format_local_time('F j, Y g:i A', $maybe2FARequiredAt);
				
				$message = sprintf(
				/* translators: 1. Date; 2. Configuration URL */ __('<html><body><p>You do not currently have two-factor authentication active on your account, which will be required beginning %1$s.</p><p><a href="%2$s">Configure 2FA</a></p></body></html>', 'wordfence'), $requiredDate, htmlentities($userUrl));
				$send = true;
			}
			
			if ($send) {
				if (wp_mail($user->user_email, $subject, $message, array('Content-Type: text/html'))) {
					$sent++;
				}
				else {
					$failed++;
				}
			}
		}

		if ($userCount == 0) {
			self::send_json(array('error' => __('No users currently exist with the selected role.', 'wordfence')));
		}
		else if ($sent == 0 && $failed == 0) {
			self::send_json(array('confirmation' => __('All users with the selected role already have additional authentication activated or have been locked out.', 'wordfence')));
		}
		else if ($sent > 0 && $failed > 0) {
			self::send_json(array('confirmation' =>
				sprintf(/* translators: number of users */ _n('A reminder to activate additional authentication was sent to %d user.', 'A reminder to activate additional authentication was sent to %d users.', $sent, 'wordfence'), $sent)
				. ' ' .
				sprintf(/* translators: number of users */ _n('It failed sending to %d user. Failures typically occur because of a missing or invalid email address.', 'It failed sending to %d users. Failures typically occur because of a missing or invalid email address.', $failed, 'wordfence'), $failed)
			));
		}
		else if ($sent > 0) {
			self::send_json(array('confirmation' => sprintf(/* translators: number of users */ _n('A reminder to activate additional authentication was sent to %d user.', 'A reminder to activate additional authentication was sent to %d users.', $sent, 'wordfence'), $sent)));
		}
		self::send_json(array('confirmation' => sprintf(/* translators: number of users */ _n('A reminder to activate additional authentication failed sending to %d user.', 'A reminder to activate additional authentication failed sending to %d users.', $failed, 'wordfence'), $failed)));
	}
	
	public function _ajax_update_ip_preview_callback() {
		$source = $_POST['ip_source'];
		$raw_proxies = $_POST['ip_source_trusted_proxies'];
		if (!is_string($source) || !is_string($raw_proxies)) {
			die();
		}
		
		$valid = array();
		$invalid = array();
		$test = preg_split('/[\r\n,]+/', $raw_proxies);
		foreach ($test as $value) {
			if (strlen($value) > 0) {
				if (Model_IP::is_valid_ip($value) || Model_IP::is_valid_cidr_range($value)) {
					$valid[] = $value;
				}
				else {
					$invalid[] = $value;
				}
			}
		}
		$trusted_proxies = $valid;
		
		$preview = Model_Request::current()->detected_ip_preview($source, $trusted_proxies);
		$ip = Model_Request::current()->ip_for_field($source, $trusted_proxies);
		self::send_json(array('ip' => $ip[0], 'preview' => $preview));
	}
	
	public function _ajax_dismiss_notice_callback() {
		Controller_Notices::shared()->remove_notice($_POST['id'], false, wp_get_current_user());
	}
	
	public function _ajax_reset_recaptcha_stats_callback() {
		Controller_Settings::shared()->set(Controller_Settings::OPTION_CAPTCHA_STATS, array('counts' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0), 'avg' => 0));
		$response = array('success' => true);
		self::send_json($response);
	}

	public function _ajax_reset_2fa_grace_period_callback() {
		$userId = (int) $_POST['user_id'];
		$gracePeriodOverride = array_key_exists('grace_period_override', $_POST) ? (int) $_POST['grace_period_override'] : null;
		$user = get_userdata($userId);
		if ($user === false)
			self::send_json(array('error' => esc_html__('Invalid user specified', 'wordfence')));
		if ($gracePeriodOverride < 0 || $gracePeriodOverride > Controller_Settings::MAX_REQUIRE_2FA_USER_GRACE_PERIOD)
			self::send_json(array('error' => esc_html__('Invalid grace period override', 'wordfence')));
		$gracePeriodAllowed = Controller_Users::shared()->get_grace_period_allowed_flag($userId);
		if (!$gracePeriodAllowed)
			Controller_Users::shared()->allow_grace_period($userId);
		if (!Controller_Users::shared()->reset_grace_period($user, $gracePeriodOverride))
			self::send_json(array('error' => esc_html__('Failed to reset grace period', 'wordfence')));
		self::send_json(array('success' => true));
	}

	public function _ajax_revoke_2fa_grace_period_callback() {
		$user = get_userdata((int) $_POST['user_id']);
		if ($user === false)
			self::send_json(array('error' => esc_html__('Invalid user specified', 'wordfence')));
		Controller_Users::shared()->revoke_grace_period($user);
		self::send_json(array('success' => true));
	}

	public function _ajax_reset_ntp_failure_count_callback() {
		Controller_Settings::shared()->reset_ntp_failure_count();
	}

	public function _ajax_disable_ntp_callback() {
		Controller_Settings::shared()->disable_ntp_cron();
	}

	public function _ajax_dismiss_persistent_notice_callback() {
		$userId = get_current_user_id();
		$noticeId = $_POST['notice_id'];
		if ($userId !== 0 && Controller_Notices::shared()->dismiss_persistent_notice($userId, $noticeId))
			self::send_json(array('success' => true));
		self::send_json(array(
			'error' => esc_html__('Unable to dismiss notice', 'wordfence')
		));
	}

	public function _ajax_dismiss_passkey_hostname_lockout_notice_callback() {
		$signature = isset($_POST['signature']) && is_string($_POST['signature']) ? $_POST['signature'] : '';
		if (Controller_WordfenceLS::shared()->dismiss_current_passkey_hostname_lockout_notice($signature))
			self::send_json(array('success' => true));
		self::send_json(array(
			'error' => esc_html__('Unable to dismiss notice', 'wordfence')
		));
	}
}
