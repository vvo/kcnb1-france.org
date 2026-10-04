<?php

namespace WordfenceLS;

use WordfenceLS\Crypto\Model_JWT;

class Controller_REST {
	const NAMESPACE = 'wordfence-login-security/v1';
	const ROUTE_INTERNAL_PASSKEY_LOGIN_BEGIN = '/internal/passkey-login/begin';
	const ROUTE_INTERNAL_PASSKEY_LOGIN_FINISH = '/internal/passkey-login/finish';
	const ROUTE_PASSKEY_MANAGEMENT_STATUS = '/passkeys';
	const ROUTE_PASSKEY_REGISTRATION_BEGIN = '/passkeys/register/begin';
	const ROUTE_PASSKEY_REGISTRATION_FINISH = '/passkeys/register/finish';
	const ROUTE_PASSKEY_REMOVE = '/passkeys/(?P<passkey_id>\d+)';
	const ROUTE_PASSKEY_PASSWORD_AUTH = '/passkeys/password-auth';

	/**
	 * Returns the singleton Controller_REST.
	 *
	 * @return Controller_REST
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_REST();
		}
		return $_shared;
	}

	/**
	 * Registers REST route initialization hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action('rest_api_init', array($this, '_register_routes'));
	}

	/**
	 * Registers internal passkey login REST routes.
	 *
	 * @return void
	 */
	public function _register_routes() {
		if (!function_exists('register_rest_route')) {
			return;
		}

		$creatable = class_exists('WP_REST_Server') ? \WP_REST_Server::CREATABLE : 'POST';
		$internalRoute = array(
			'methods' => $creatable,
			'permission_callback' => array($this, '_internal_permission_callback'),
		);
		register_rest_route(self::NAMESPACE, self::ROUTE_INTERNAL_PASSKEY_LOGIN_BEGIN, $internalRoute + array(
			'callback' => array($this, '_begin_passkey_login_callback'),
		));
		register_rest_route(self::NAMESPACE, self::ROUTE_INTERNAL_PASSKEY_LOGIN_FINISH, $internalRoute + array(
			'callback' => array($this, '_finish_passkey_login_callback'),
		));

		if (!self::passkey_management_rest_enabled()) {
			return;
		}

		$managementRoute = array(
			'methods' => $creatable,
			'permission_callback' => array($this, '_passkey_management_permission_callback'),
		);
		register_rest_route(self::NAMESPACE, self::ROUTE_PASSKEY_MANAGEMENT_STATUS, array(
			'methods' => 'GET',
			'permission_callback' => array($this, '_passkey_management_permission_callback'),
			'callback' => array($this, '_passkey_management_status_callback'),
		));
		register_rest_route(self::NAMESPACE, self::ROUTE_PASSKEY_REGISTRATION_BEGIN, $managementRoute + array(
			'callback' => array($this, '_begin_passkey_registration_callback'),
		));
		register_rest_route(self::NAMESPACE, self::ROUTE_PASSKEY_REGISTRATION_FINISH, $managementRoute + array(
			'callback' => array($this, '_finish_passkey_registration_callback'),
		));
		register_rest_route(self::NAMESPACE, self::ROUTE_PASSKEY_REMOVE, array(
			'methods' => 'DELETE',
			'permission_callback' => array($this, '_passkey_management_permission_callback'),
			'callback' => array($this, '_remove_passkey_callback'),
		));
		register_rest_route(self::NAMESPACE, self::ROUTE_PASSKEY_PASSWORD_AUTH, array(
			'methods' => 'PATCH',
			'permission_callback' => array($this, '_passkey_management_permission_callback'),
			'callback' => array($this, '_set_passkey_password_auth_callback'),
		));
	}

	/**
	 * Returns whether the authenticated passkey management REST routes should be registered.
	 *
	 * @return bool
	 */
	public static function passkey_management_rest_enabled() {
		/**
		 * Filters whether authenticated passkey management REST routes should be registered.
		 *
		 * The passkey management endpoints are not registered by default. Custom integrations that need browser-side
		 * current-user passkey management can return true to expose these routes.
		 *
		 * @param bool $enabled Whether the passkey management REST routes should be registered.
		 */
		return apply_filters('wordfence_ls_passkey_management_rest_enabled', false) === true;
	}

	/**
	 * Authorizes internal passkey login REST requests through the integration filter.
	 *
	 * @param mixed $request REST request object or request-like payload.
	 * @return true|\WP_Error
	 */
	public function _internal_permission_callback($request) {
		/**
		 * Filters whether an internal passkey login REST request is authorized.
		 *
		 * The internal passkey login endpoints are denied by default. Custom integrations that call these endpoints
		 * server-to-server should validate their own request authentication here and return true only for trusted
		 * login calls.
		 *
		 * @param bool|null|\WP_Error $allowed Whether the request is allowed. Return true to allow, false or null to deny, or a WP_Error to deny with a custom error.
		 * @param \WP_REST_Request $request The current REST request.
		 */
		$allowed = apply_filters('wordfence_ls_internal_passkey_request_allowed', null, $request);
		if (is_wp_error($allowed)) {
			return $allowed;
		}
		if ($allowed === true) {
			return true;
		}

		return $this->forbidden_error();
	}

	/**
	 * Requires an authenticated WordPress user for passkey management routes.
	 *
	 * @param mixed $request REST request object or request-like payload.
	 * @return true|\WP_Error
	 */
	public function _passkey_management_permission_callback($request) {
		$user = $this->current_user();
		if (is_wp_error($user)) {
			return $user;
		}
		if (!$this->valid_rest_nonce($request)) {
			return new \WP_Error('wfls_rest_nonce_invalid', __('You must provide a valid REST nonce to manage passkeys.', 'wordfence'), array('status' => 403));
		}
		return true;
	}

	/**
	 * Begins a headless passkey login request and returns WebAuthn options.
	 *
	 * @param mixed $request REST request object or request-like payload.
	 * @return \WP_REST_Response|array
	 */
	public function _begin_passkey_login_callback($request) {
		if (!Controller_Settings::shared()->are_passkeys_enabled()) {
			return $this->rest_response(array(
				'available' => false,
				'passkeys_active' => false,
				'reason' => 'disabled',
			));
		}
		$data = $this->request_data($request);
		$passkeyController = Controller_Passkey::shared();
		$clientIp = $this->string_value($data, 'client_ip', null);
		if ($clientIp === null) {
			$clientIp = $this->string_value($data, 'clientIp', null);
		}
		$rateLimit = $passkeyController->consume_begin_login_rate_limit($clientIp);
		if (is_wp_error($rateLimit)) {
			return $this->error_response($rateLimit);
		}

		if (!$passkeyController->any_passkeys_active()) {
			return $this->rest_response(array(
				'available' => false,
				'passkeys_active' => false,
				'reason' => 'no_passkeys',
			));
		}

		$allowSameRelyingPartyFrame = $this->truthy_value($data, array('allow_same_relying_party_frame', 'allowSameRelyingPartyFrame', 'interim_login'));
		$result = $passkeyController->begin_login($allowSameRelyingPartyFrame);
		if (is_wp_error($result)) {
			return $this->error_response($result);
		}

		$options = isset($result['options']) && is_array($result['options']) ? $result['options'] : array();
		return $this->rest_response(array(
			'available' => true,
			'passkeys_active' => true,
			'token' => isset($result['token']) ? $result['token'] : '',
			'publicKey' => $options, // camelCase to match navigator.credentials.get({ publicKey }); options is the server-friendly alias.
			'options' => $options,
			'rp_id' => isset($options['rpId']) ? $options['rpId'] : '',
			'allow_same_relying_party_frame' => $allowSameRelyingPartyFrame,
			'login_token_expires_in' => Controller_Passkey::LOGIN_TOKEN_DURATION,
		));
	}

	/**
	 * Finishes a headless passkey login request and returns cookie metadata.
	 *
	 * @param mixed $request REST request object or request-like payload.
	 * @return \WP_REST_Response|array
	 */
	public function _finish_passkey_login_callback($request) {
		if (!Controller_Settings::shared()->are_passkeys_enabled()) {
			return $this->error_response(new \WP_Error(
				'wfls_passkeys_disabled',
				__('Passkeys are currently disabled.', 'wordfence'),
				array('status' => 403)
			));
		}
		$data = $this->request_data($request);
		$token = $this->string_value($data, 'token', '');
		$credential = $this->credential_value($data);
		$remember = $this->truthy_value($data, array('remember', 'rememberme'));
		$clientIp = $this->string_value($data, 'client_ip', null);
		if ($clientIp === null) {
			$clientIp = $this->string_value($data, 'clientIp', null);
		}

		$user = Controller_Passkey::shared()->finish_and_authenticate_login($token, $credential, $remember, false, $clientIp);
		if (is_wp_error($user)) {
			return $this->error_response($user);
		}
		if (!$user instanceof \WP_User || !$user->exists()) {
			return $this->error_response(new \WP_Error('wfls_passkey_login_user_invalid', __('The passkey login could not be completed. Please try again.', 'wordfence')));
		}

		$browserOrigin = $this->browser_origin_from_credential($credential);
		if (is_wp_error($browserOrigin)) {
			return $this->error_response($browserOrigin);
		}

		return $this->make_authenticated_response($user, $remember, $browserOrigin);
	}

	/**
	 * Returns passkey management state for the requested user.
	 *
	 * @param mixed $request REST request object or request-like payload.
	 * @return \WP_REST_Response|array
	 */
	public function _passkey_management_status_callback($request) {
		$user = $this->current_user();
		if (is_wp_error($user)) {
			return $this->error_response($user);
		}

		return $this->rest_response($this->passkey_management_status($user));
	}

	/**
	 * Begins passkey registration for the requested user's own account.
	 *
	 * @param mixed $request REST request object or request-like payload.
	 * @return \WP_REST_Response|array
	 */
	public function _begin_passkey_registration_callback($request) {
		$data = $this->request_data($request);
		$user = $this->manageable_current_user(true);
		if (is_wp_error($user)) {
			return $this->error_response($user);
		}

		$result = Controller_Passkey::shared()->begin_registration($user, $this->string_value($data, 'label', ''));
		if (is_wp_error($result)) {
			return $this->error_response($result);
		}

		return $this->rest_response($result);
	}

	/**
	 * Finishes passkey registration for the requested user's own account.
	 *
	 * @param mixed $request REST request object or request-like payload.
	 * @return \WP_REST_Response|array
	 */
	public function _finish_passkey_registration_callback($request) {
		$data = $this->request_data($request);
		$user = $this->manageable_current_user(true);
		if (is_wp_error($user)) {
			return $this->error_response($user);
		}

		$credential = $this->credential_value($data);
		$result = Controller_Passkey::shared()->finish_registration(
			$user,
			$this->string_value($data, 'token', ''),
			$credential,
			$this->string_value($data, 'label', ''),
			Controller_WordfenceLS::UI_STYLE_CONTEXT_WFLS
		);
		if (is_wp_error($result)) {
			return $this->error_response($result);
		}

		return $this->rest_response(array(
			'registered' => true,
			'status' => $this->passkey_management_status($user),
		));
	}

	/**
	 * Removes a registered passkey from the requested user's account.
	 *
	 * @param mixed $request REST request object or request-like payload.
	 * @return \WP_REST_Response|array
	 */
	public function _remove_passkey_callback($request) {
		$data = $this->request_data($request);
		$user = $this->manageable_current_user();
		if (is_wp_error($user)) {
			return $this->error_response($user);
		}

		$passkeyID = $this->int_value($data, 'passkey_id', 0);
		if ($passkeyID <= 0) {
			$passkeyID = $this->int_value($data, 'passkeyId', 0);
		}
		$result = Controller_Passkey::shared()->remove_passkey($user, $passkeyID);
		if (is_wp_error($result)) {
			return $this->error_response($result);
		}

		return $this->rest_response(array(
			'removed' => true,
			'status' => $this->passkey_management_status($user),
		));
	}

	/**
	 * Updates the requested user's username/password authentication preference for passkey accounts.
	 *
	 * @param mixed $request REST request object or request-like payload.
	 * @return \WP_REST_Response|array
	 */
	public function _set_passkey_password_auth_callback($request) {
		$data = $this->request_data($request);
		$user = $this->manageable_current_user();
		if (is_wp_error($user)) {
			return $this->error_response($user);
		}
		if (!Controller_Passkey::shared()->can_change_username_password_auth($user)) {
			return $this->error_response(new \WP_Error('wfls_passkey_password_auth_locked', __('This option cannot be changed because passkeys are required for one or more of this user\'s roles.', 'wordfence')));
		}

		if (!is_array($data) || !array_key_exists('enabled', $data) || !is_scalar($data['enabled'])) {
			return $this->error_response(new \WP_Error('wfls_passkey_password_auth_enabled_missing', __('The username/password authentication setting is missing or invalid.', 'wordfence')));
		}

		$enabled = $this->truthy_scalar($data['enabled']);
		if (!Controller_Passkey::shared()->set_username_password_auth_enabled($user, $enabled)) {
			return $this->error_response(new \WP_Error('wfls_passkey_password_auth_save_failed', __('Unable to save the user-specific passkey options.', 'wordfence')));
		}
		$enabled = Controller_Passkey::shared()->is_username_password_auth_enabled($user);

		return $this->rest_response(array(
			'saved' => true,
			'enabled' => $enabled,
			'status' => $this->passkey_management_status($user),
		));
	}

	/**
	 * Builds an authenticated login response containing WordPress auth cookie metadata.
	 *
	 * @param \WP_User $user Existing user object.
	 * @param bool $remember Whether to create persistent browser cookies.
	 * @param string $browserOrigin Signed and verified public browser origin for the passkey assertion.
	 * @return \WP_REST_Response|array
	 */
	public function make_authenticated_response($user, $remember = false, $browserOrigin = '') {
		if (!$user instanceof \WP_User || !$user->exists()) {
			return $this->error_response(new \WP_Error('wfls_passkey_login_user_invalid', __('The passkey login could not be completed. Please try again.', 'wordfence')));
		}
		$browserOriginSecure = $this->browser_origin_cookie_security($browserOrigin);
		if (is_wp_error($browserOriginSecure)) {
			return $this->error_response($browserOriginSecure);
		}
		$userID = (int) $user->ID;
		$remember = (bool) $remember;
		$duration = $remember ? 14 * $this->day_in_seconds() : 2 * $this->day_in_seconds();
		$expiration = time() + apply_filters('auth_cookie_expiration', $duration, $userID, $remember);
		$expire = $remember ? $expiration + 12 * $this->hour_in_seconds() : 0;
		$secure = (bool) apply_filters('secure_auth_cookie', $browserOriginSecure, $userID);
		$secure = $browserOriginSecure || $secure;
		$scheme = $secure ? 'secure_auth' : 'auth';
		$authCookieName = $secure ? $this->secure_auth_cookie_name() : $this->auth_cookie_name();
		$secureLoggedInCookie = $secure && $this->home_url_is_https();
		$secureLoggedInCookie = (bool) apply_filters('secure_logged_in_cookie', $secureLoggedInCookie, $userID, $secure);
		$secureLoggedInCookie = $browserOriginSecure || $secureLoggedInCookie;
		$manager = \WP_Session_Tokens::get_instance($userID);
		$token = $manager->create($expiration);
		$authCookie = wp_generate_auth_cookie($userID, $expiration, $scheme, $token);
		$loggedInCookie = wp_generate_auth_cookie($userID, $expiration, 'logged_in', $token);
		$cookieDomain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
		$cookies = array(
			$this->cookie_response($authCookieName, $authCookie, $this->plugins_cookie_path(), $cookieDomain, $secure, $expire),
			$this->cookie_response($authCookieName, $authCookie, $this->admin_cookie_path(), $cookieDomain, $secure, $expire),
			$this->cookie_response($this->logged_in_cookie_name(), $loggedInCookie, $this->cookie_path(), $cookieDomain, $secureLoggedInCookie, $expire),
		);
		$siteCookiePath = $this->site_cookie_path();
		if ($siteCookiePath !== $this->cookie_path()) {
			$cookies[] = $this->cookie_response($this->logged_in_cookie_name(), $loggedInCookie, $siteCookiePath, $cookieDomain, $secureLoggedInCookie, $expire);
		}

		do_action('set_auth_cookie', $authCookie, $expire, $expiration, $userID, $scheme, $token);
		do_action('set_logged_in_cookie', $loggedInCookie, $expire, $expiration, $userID, 'logged_in', $token);
		if (!apply_filters('send_auth_cookies', true, $expire, $expiration, $userID, $scheme, $token)) {
			$cookies = array();
		}
		do_action('wp_login', $user->user_login, $user);

		return $this->rest_response(array(
			'user_id' => $userID,
			'cookies' => $cookies,
		));
	}

	/**
	 * Returns the default forbidden error for unauthorized internal REST access.
	 *
	 * @return \WP_Error
	 */
	private function forbidden_error() {
		return new \WP_Error('wfls_internal_passkey_forbidden', __('Passkey API access is not authorized.', 'wordfence'), array('status' => 403));
	}

	/**
	 * Converts a passkey login error into the REST error response shape.
	 *
	 * @param \WP_Error|mixed $error Error object or fallback error value.
	 * @return \WP_REST_Response|array
	 */
	private function error_response($error) {
		$message = is_wp_error($error) ? $error->get_error_message() : __('The passkey login could not be completed. Please try again.', 'wordfence');
		$code = 'wfls_passkey_login_failed';
		if (is_wp_error($error) && method_exists($error, 'get_error_code')) {
			$code = $error->get_error_code();
		}
		$status = $this->error_status($error);

		return $this->rest_response(array(
			'available' => false,
			'code' => $code,
			'error' => $message,
			'errors' => array(
				'general' => $message,
			),
		), $status);
	}

	/**
	 * Returns the HTTP status for an error response.
	 *
	 * @param \WP_Error|mixed $error Error object or fallback error value.
	 * @return int
	 */
	private function error_status($error) {
		if (is_wp_error($error) && method_exists($error, 'get_error_data')) {
			$data = $error->get_error_data();
			if (is_array($data) && isset($data['status']) && is_numeric($data['status'])) {
				$status = (int) $data['status'];
				if ($status >= 400 && $status <= 599) {
					return $status;
				}
			}
		}
		return 400;
	}

	/**
	 * Wraps response data in a REST response when WordPress REST classes are available.
	 *
	 * @param array $data Response data.
	 * @param int $status HTTP response status.
	 * @return \WP_REST_Response|array
	 */
	private function rest_response($data, $status = 200) {
		if (class_exists('WP_REST_Response')) {
			return new \WP_REST_Response($data, $status);
		}
		return $data;
	}

	/**
	 * Returns normalized request data, unwrapping a top-level data object when present.
	 *
	 * @param mixed $request REST request object or request-like payload.
	 * @return array
	 */
	private function request_data($request) {
		$payload = $this->request_payload($request);
		if (isset($payload['data']) && is_array($payload['data'])) {
			return $payload['data'];
		}
		return $payload;
	}

	/**
	 * Extracts request payload data from arrays, REST request objects, or ArrayAccess payloads.
	 *
	 * @param mixed $request REST request object or request-like payload.
	 * @return array
	 */
	private function request_payload($request) {
		if (is_array($request)) {
			return $request;
		}
		if (is_object($request)) {
			if (method_exists($request, 'get_params')) {
				$params = $request->get_params();
				if (is_array($params)) {
					return $params;
				}
			}
			if (method_exists($request, 'get_json_params')) {
				$params = $request->get_json_params();
				if (is_array($params)) {
					return $params;
				}
			}
			if ($request instanceof \ArrayAccess) {
				$payload = array();
				foreach ($this->request_keys() as $key) {
					if (isset($request[$key])) {
						$payload[$key] = $request[$key];
					}
				}
				return $payload;
			}
		}
		return array();
	}

	/**
	 * Returns request keys accepted from ArrayAccess request objects.
	 *
	 * @return string[]
	 */
	private function request_keys() {
		return array(
			'data',
			'signature',
			'token',
			'label',
			'credential',
			'assertion',
			'passkey_id',
			'passkeyId',
			'enabled',
			'remember',
			'rememberme',
			'client_ip',
			'clientIp',
			'allow_same_relying_party_frame',
			'allowSameRelyingPartyFrame',
			'interim_login',
		);
	}

	/**
	 * Returns a scalar request value as a string.
	 *
	 * @param array $data Request data.
	 * @param string $key Request key.
	 * @param string|null $default Default value when the key is absent or non-scalar.
	 * @return string|null
	 */
	private function string_value($data, $key, $default = '') {
		if (is_array($data) && array_key_exists($key, $data) && is_scalar($data[$key])) {
			return (string) $data[$key];
		}
		return $default;
	}

	/**
	 * Returns an integer request value.
	 *
	 * @param array $data Request data.
	 * @param string $key Request key.
	 * @param int $default Default value when the key is absent or non-scalar.
	 * @return int
	 */
	private function int_value($data, $key, $default = 0) {
		if (is_array($data) && array_key_exists($key, $data) && is_scalar($data[$key])) {
			return (int) $data[$key];
		}
		return (int) $default;
	}

	/**
	 * Resolves the current authenticated WordPress user.
	 *
	 * @return \WP_User|\WP_Error
	 */
	private function current_user() {
		if (!function_exists('wp_get_current_user')) {
			return new \WP_Error('wfls_user_missing', __('You must be logged in to manage passkeys.', 'wordfence'), array('status' => 401));
		}

		$user = wp_get_current_user();
		if (!$user instanceof \WP_User || !$user->exists()) {
			return new \WP_Error('wfls_user_missing', __('You must be logged in to manage passkeys.', 'wordfence'), array('status' => 401));
		}

		return $user;
	}

	/**
	 * Validates that the request came from a nonce-authenticated WordPress REST browser session.
	 *
	 * @param mixed $request REST request object or request-like payload.
	 * @return bool
	 */
	private function valid_rest_nonce($request) {
		if (!function_exists('wp_verify_nonce')) {
			return false;
		}

		$nonce = $this->rest_nonce_value($request);
		return is_string($nonce) && $nonce !== '' && wp_verify_nonce($nonce, 'wp_rest') !== false;
	}

	/**
	 * Extracts a WordPress REST nonce from a REST request object or request-like test payload.
	 *
	 * @param mixed $request REST request object or request-like payload.
	 * @return string|null
	 */
	private function rest_nonce_value($request) {
		if (is_object($request)) {
			if (method_exists($request, 'get_header')) {
				foreach (array('X-WP-Nonce', 'x-wp-nonce') as $header) {
					$value = $request->get_header($header);
					if (is_scalar($value) && (string) $value !== '') {
						return (string) $value;
					}
				}
			}
			if (method_exists($request, 'get_param')) {
				$value = $request->get_param('_wpnonce');
				if (is_scalar($value) && (string) $value !== '') {
					return (string) $value;
				}
			}
		}

		if (is_array($request)) {
			foreach (array('headers', 'data') as $key) {
				if (isset($request[$key]) && is_array($request[$key])) {
					foreach (array('X-WP-Nonce', 'x-wp-nonce', '_wpnonce') as $nonceKey) {
						if (isset($request[$key][$nonceKey]) && is_scalar($request[$key][$nonceKey]) && (string) $request[$key][$nonceKey] !== '') {
							return (string) $request[$key][$nonceKey];
						}
					}
				}
			}
			if (isset($request['_wpnonce']) && is_scalar($request['_wpnonce']) && (string) $request['_wpnonce'] !== '') {
				return (string) $request['_wpnonce'];
			}
		}

		return null;
	}

	/**
	 * Resolves and authorizes the current user for passkey management.
	 *
	 * @param bool $registration Whether the operation is creating a new passkey.
	 * @return \WP_User|\WP_Error
	 */
	private function manageable_current_user($registration = false) {
		$user = $this->current_user();
		if (is_wp_error($user)) {
			return $user;
		}

		$passkeyController = Controller_Passkey::shared();
		$canManage = $passkeyController->can_manage_passkeys($user, $user);
		$canRegister = $passkeyController->can_register_passkeys($user, $user);
		if (($registration && !$canRegister) || (!$registration && !$canManage)) {
			return new \WP_Error('wfls_permission_denied', __('You do not have permission to manage passkeys for this account.', 'wordfence'));
		}

		return $user;
	}

	/**
	 * Builds the passkey management status payload for Central.
	 *
	 * @param \WP_User $user
	 * @return array
	 */
	private function passkey_management_status($user) {
		$passkeyController = Controller_Passkey::shared();
		$canManage = $passkeyController->can_manage_passkeys($user, $user);
		$passkeys = $canManage ? $passkeyController->get_passkeys($user) : array();
		$canRegister = $passkeyController->can_register_passkeys($user, $user) && $passkeyController->has_passkey_capacity($passkeys);
		$requiresPasskey = Controller_Users::shared()->requires_passkey($user, $inGracePeriod, $requiredAt);
		$hasPasskeys = !empty($passkeys);
		$roleAllowsPasswordAuth = $passkeyController->can_change_username_password_auth($user);
		$canChangePasswordAuth = $canManage && $roleAllowsPasswordAuth;
		$passwordAuthEnabled = !$hasPasskeys && $roleAllowsPasswordAuth
			? true
			: $passkeyController->is_username_password_auth_enabled($user);
		if (!Controller_Settings::shared()->are_passkeys_enabled()) {
			$effectivePasswordAuthEnabled = true;
		}
		else if (!$roleAllowsPasswordAuth) {
			$effectivePasswordAuthEnabled = false;
		}
		else if (!$canManage) {
			$effectivePasswordAuthEnabled = true;
		}
		else {
			$effectivePasswordAuthEnabled = $passwordAuthEnabled || !$hasPasskeys;
		}

		return array(
			'available' => $canManage,
			'can_manage' => $canManage,
			'can_register' => $canRegister,
			'has_passkeys' => $hasPasskeys,
			'passkeys' => array_map(array($this, 'passkey_response'), $passkeys),
			'username_password_auth_enabled' => $passwordAuthEnabled,
			'effective_username_password_auth_enabled' => $effectivePasswordAuthEnabled,
			'can_change_username_password_auth' => $canChangePasswordAuth,
			'password_auth_blocked' => $hasPasskeys && !$effectivePasswordAuthEnabled,
			'requires_passkey' => $requiresPasskey,
			'passkey_required_grace_period' => (bool) $inGracePeriod,
			'passkey_required_at' => $requiredAt === null ? null : (int) $requiredAt,
			'registration_token_expires_in' => Controller_Passkey::REGISTRATION_TOKEN_DURATION,
		);
	}

	/**
	 * Builds the public passkey record shape used by Central.
	 *
	 * @param array $passkey Database passkey record.
	 * @return array
	 */
	private function passkey_response($passkey) {
		return array(
			'id' => isset($passkey['id']) ? (int) $passkey['id'] : 0,
			'label' => isset($passkey['label']) ? (string) $passkey['label'] : '',
			'created_at' => isset($passkey['ctime']) ? (int) $passkey['ctime'] : null,
			'last_used_at' => !empty($passkey['last_used_at']) ? (int) $passkey['last_used_at'] : null,
		);
	}

	/**
	 * Returns the boolean interpretation of the first present key.
	 *
	 * @param array $data Request data.
	 * @param string[] $keys Candidate keys in priority order.
	 * @return bool
	 */
	private function truthy_value($data, $keys) {
		if (!is_array($data)) {
			return false;
		}
		foreach ($keys as $key) {
			if (array_key_exists($key, $data)) {
				return $this->truthy_scalar($data[$key]);
			}
		}
		return false;
	}

	/**
	 * Coerces scalar and collection values to boolean semantics.
	 *
	 * @param mixed $value Value to coerce.
	 * @return bool
	 */
	private function truthy_scalar($value) {
		if ($value === null || is_scalar($value)) {
			return Utility_Number::truthyToBool($value);
		}
		return !empty($value);
	}

	/**
	 * Builds one cookie metadata entry for the integration response.
	 *
	 * @param string $name Cookie name.
	 * @param string $value Cookie value.
	 * @param string $path Cookie path.
	 * @param string $domain Cookie domain.
	 * @param bool $secure Whether the cookie should be secure-only.
	 * @param int $expiration Browser cookie expiration timestamp, or 0 for a session cookie.
	 * @return array
	 */
	private function cookie_response($name, $value, $path, $domain, $secure, $expiration) {
		return array(
			'name' => $name,
			'value' => $value,
			'path' => $path,
			'domain' => $domain,
			'secure' => (bool) $secure,
			'http_only' => true,
			'expiration' => $expiration,
		);
	}

	/**
	 * Returns the non-secure auth cookie name.
	 *
	 * @return string
	 */
	private function auth_cookie_name() {
		return defined('AUTH_COOKIE') ? AUTH_COOKIE : 'wordpress';
	}

	/**
	 * Returns the secure auth cookie name.
	 *
	 * @return string
	 */
	private function secure_auth_cookie_name() {
		return defined('SECURE_AUTH_COOKIE') ? SECURE_AUTH_COOKIE : 'wordpress_sec';
	}

	/**
	 * Returns the logged-in cookie name.
	 *
	 * @return string
	 */
	private function logged_in_cookie_name() {
		return defined('LOGGED_IN_COOKIE') ? LOGGED_IN_COOKIE : 'wordpress_logged_in';
	}

	/**
	 * Returns the plugins auth cookie path.
	 *
	 * @return string
	 */
	private function plugins_cookie_path() {
		return defined('PLUGINS_COOKIE_PATH') ? PLUGINS_COOKIE_PATH : '/wp-content/plugins';
	}

	/**
	 * Returns the admin auth cookie path.
	 *
	 * @return string
	 */
	private function admin_cookie_path() {
		return defined('ADMIN_COOKIE_PATH') ? ADMIN_COOKIE_PATH : '/wp-admin';
	}

	/**
	 * Returns the default logged-in cookie path.
	 *
	 * @return string
	 */
	private function cookie_path() {
		return defined('COOKIEPATH') ? COOKIEPATH : '/';
	}

	/**
	 * Returns the site logged-in cookie path.
	 *
	 * @return string
	 */
	private function site_cookie_path() {
		return defined('SITECOOKIEPATH') ? SITECOOKIEPATH : $this->cookie_path();
	}

	/**
	 * Returns whether the configured home URL uses HTTPS.
	 *
	 * @return bool
	 */
	private function home_url_is_https() {
		$url = function_exists('get_option') ? get_option('home') : '';
		if (!is_string($url) || $url === '') {
			$url = function_exists('home_url') ? home_url() : '';
		}
		$parsed = function_exists('wp_parse_url') ? wp_parse_url($url) : parse_url($url);
		return is_array($parsed) && isset($parsed['scheme']) && strtolower($parsed['scheme']) === 'https';
	}

	/**
	 * Extracts the effective public browser origin from a passkey assertion credential.
	 *
	 * A validated top origin represents the browser-facing application for framed passkey logins. The assertion
	 * origin is used for normal and legacy framed responses that do not include topOrigin.
	 *
	 * @param array|null $credential Passkey assertion credential submitted by the integration.
	 * @return string|\WP_Error Effective browser origin, or an error when it cannot be determined safely.
	 */
	private function browser_origin_from_credential($credential) {
		$encoded = is_array($credential) && isset($credential['response']) && is_array($credential['response']) && isset($credential['response']['clientDataJSON']) ? $credential['response']['clientDataJSON'] : null;
		if (!is_string($encoded)) {
			return $this->browser_origin_error();
		}

		$decoded = Model_JWT::base64url_decode($encoded);
		$clientData = $decoded === false ? null : json_decode($decoded, true);
		if (!is_array($clientData)) {
			return $this->browser_origin_error();
		}

		$key = array_key_exists('topOrigin', $clientData) ? 'topOrigin' : 'origin';
		return isset($clientData[$key]) && is_string($clientData[$key]) && $clientData[$key] !== '' ? $clientData[$key] : $this->browser_origin_error();
	}

	/**
	 * Resolves whether cookies for a verified public browser origin must be secure-only.
	 *
	 * @param string $browserOrigin Signed and verified public browser origin.
	 * @return bool|\WP_Error Whether cookies must be secure, or an error for an ambiguous origin.
	 */
	private function browser_origin_cookie_security($browserOrigin) {
		if (!is_string($browserOrigin)) {
			return $this->browser_origin_error();
		}

		$parsed = parse_url($browserOrigin);
		if (!is_array($parsed) || !isset($parsed['scheme'], $parsed['host']) || $parsed['scheme'] === '' || $parsed['host'] === '') {
			return $this->browser_origin_error();
		}
		// A WebAuthn origin contains only a scheme, host, and optional port, never userinfo or URL resource components.
		// If any of these components are present, the URL is considered malformed.
		foreach (array('user', 'pass', 'path', 'query', 'fragment') as $component) {
			if (array_key_exists($component, $parsed)) {
				return $this->browser_origin_error();
			}
		}

		$scheme = strtolower($parsed['scheme']);
		$host = strtolower($parsed['host']);
		if ($scheme === 'https') {
			return true;
		}
		if ($scheme === 'http' && $host === 'localhost') {
			return false;
		}
		return $this->browser_origin_error();
	}

	/**
	 * Returns the fail-closed error for an ambiguous headless browser origin.
	 *
	 * @return \WP_Error
	 */
	private function browser_origin_error() {
		return new \WP_Error('wfls_passkey_login_browser_origin_invalid', __('The passkey login browser origin could not be verified. Please try again.', 'wordfence'));
	}

	/**
	 * Returns the credential assertion from request data.
	 *
	 * @param array $data Request data.
	 * @return array|null
	 */
	private function credential_value($data) {
		$credential = null;
		if (is_array($data) && array_key_exists('credential', $data)) {
			$credential = $data['credential'];
		}
		else if (is_array($data) && array_key_exists('assertion', $data)) {
			$credential = $data['assertion'];
		}

		if (is_string($credential)) {
			$decoded = json_decode($credential, true);
			return is_array($decoded) ? $decoded : null;
		}
		return is_array($credential) ? $credential : null;
	}

	/**
	 * Returns the number of seconds in a day.
	 *
	 * @return int
	 */
	private function day_in_seconds() {
		return defined('DAY_IN_SECONDS') ? DAY_IN_SECONDS : 86400;
	}

	/**
	 * Returns the number of seconds in an hour.
	 *
	 * @return int
	 */
	private function hour_in_seconds() {
		return defined('HOUR_IN_SECONDS') ? HOUR_IN_SECONDS : 3600;
	}
}
