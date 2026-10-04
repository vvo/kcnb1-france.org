<?php

namespace WordfenceLS;

use WordfenceLS\Controller_Users;
use WordfenceLS\Controller_Permissions;
use WordfenceLS\Controller_Javascript;
use WordfenceLS\Crypto\Model_JWT;
use WordfenceLS\Crypto\Model_Symmetric;
use WordfenceLS\Text\Model_HTML;
use WordfenceLS\Utility_URL;
use WordfenceLS\View\Model_Tab;
use WordfenceLS\View\Model_Title;

class Controller_WordfenceLS {
	const VERSION_KEY = 'wordfence_ls_version';
	const USERS_PER_PAGE = 25;
	const SHORTCODE_2FA_MANAGEMENT = 'wordfence_2fa_management';
	const SHORTCODE_PASSKEY_MANAGEMENT = 'wordfence_passkey_management';
	const WOOCOMMERCE_ENDPOINT = 'wordfence-2fa';
	const WOOCOMMERCE_PASSKEY_ENDPOINT = 'wordfence-passkeys';
	const WOOCOMMERCE_ENDPOINTS_VERSION_KEY = 'wordfence_ls_woocommerce_endpoints_version';
	const WOOCOMMERCE_ENDPOINTS_VERSION = 2;
	const UI_STYLE_CONTEXT_CORE = 'core';
	const UI_STYLE_CONTEXT_WFLS = 'wfls';
	const USER_OPTION_DISMISSED_PASSKEY_HOSTNAME_LOCKOUT_SIGNATURE = 'wfls-dismissed-passkey-hostname-lockout-signature';

	private $management_assets_registered = false;
	private $management_assets_enqueued = false;
	private $use_core_font_awesome_styles = null;
	private $authentication_context_stack = array();
	private static $_pendingApplicationPasswordAuthentication = array();
	private static $_applicationPasswordAuthentication = array();
	
	/**
	 * Returns the singleton Controller_Wordfence2FA.
	 *
	 * @return Controller_WordfenceLS
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_WordfenceLS();
		}
		return $_shared;
	}
	
	public function init() {
		$this->_init_actions();
		Controller_AJAX::shared()->init();
		Controller_Passkey::shared()->init();
		Controller_REST::shared()->init();
		Controller_Users::shared()->init();
		Controller_Time::shared()->init();
		Controller_Permissions::shared()->init();
		Controller_CLI::shared()->init();
	}
	
	protected function _init_actions() {
		register_activation_hook(WORDFENCE_LS_FCPATH, array($this, '_install_plugin'));
		register_deactivation_hook(WORDFENCE_LS_FCPATH, array($this, '_uninstall_plugin'));
		
		$versionInOptions = ((is_multisite() && function_exists('get_network_option')) ? get_network_option(null, self::VERSION_KEY, false) : get_option(self::VERSION_KEY, false));
		if (!$versionInOptions || version_compare(WORDFENCE_LS_VERSION, $versionInOptions, '>')) { //Either there is no version in options or the version in options is greater and we need to run the upgrade
			$this->_install();
		}
		
		if (!Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ALLOW_XML_RPC)) {
			add_filter('xmlrpc_enabled', array($this, '_block_xml_rpc'));
		}
		
		add_action('admin_init', array($this, '_admin_init'));
		add_action('login_enqueue_scripts', array($this, '_login_enqueue_scripts'));
		add_filter('authenticate', array($this, '_authenticate'), 25, 3);
		add_action('wp_authenticate_application_password_errors', array($this, '_record_application_password_check'), PHP_INT_MAX, 4);
		add_action('application_password_did_authenticate', array($this, '_record_application_password_authentication'), 10, 2);
		add_action('set_logged_in_cookie', array($this, '_set_logged_in_cookie'), 25, 4);
		add_action('wp_login', array($this, '_record_login'), 999, 1);
		add_action('register_post', array($this, '_register_post'), 25, 3);
		add_filter('wp_login_errors', array($this, '_wp_login_errors'), 25, 3);
		if ($this->is_woocommerce_integration_enabled()) {
			$this->init_woocommerce_actions();
			if ($this->is_woocommerce_account_integration_enabled()) {
				$this->maybe_purge_outdated_woocommerce_rewrite_rules();
			}
		}
		add_action('user_new_form', array($this, '_user_new_form'));
		add_action('user_register', array($this, '_user_register'));
		
		$useSubmenu = WORDFENCE_LS_FROM_CORE;
		if (is_multisite() && !is_network_admin()) {
			$useSubmenu = false;
		}
		
		add_action('admin_menu', array($this, '_admin_menu'), $useSubmenu ? 55 : 10);
		if (is_multisite()) {
			add_action('network_admin_menu', array($this, '_admin_menu'), $useSubmenu ? 55 : 10);
		}
		add_action('admin_enqueue_scripts', array($this, '_admin_enqueue_scripts'));
		add_action('admin_print_scripts', array($this, '_setupImportMap'), 0);
		add_filter('script_loader_tag', array($this, '_tagVueScriptAsModule') , 10, 3);
		
		add_action('show_user_profile', array($this, '_edit_user_profile'), 0); //We can't add it to the password section directly -- priority 0 is as close as we can get
		add_action('edit_user_profile', array($this, '_edit_user_profile'), 0);

		add_action('init', array($this, '_wordpress_init'));
		if ($this->is_shortcode_enabled())
			add_action('wp_enqueue_scripts', array($this, '_handle_shortcode_prerequisites'));
		
		Controller_Permissions::_init_actions();
	}

	public function _wordpress_init() {
		if (!WORDFENCE_LS_FROM_CORE)
			load_plugin_textdomain('wordfence-login-security', false, WORDFENCE_LS_PATH . 'languages');
		if ($this->is_shortcode_enabled()) {
			add_shortcode(self::SHORTCODE_2FA_MANAGEMENT, array($this, '_handle_user_2fa_management_shortcode'));
			add_shortcode(self::SHORTCODE_PASSKEY_MANAGEMENT, array($this, '_handle_user_passkey_management_shortcode'));
		}
	}

	private function init_woocommerce_actions() {
		add_action('woocommerce_before_customer_login_form', array($this, '_woocommerce_login_enqueue_scripts'));
		add_action('woocommerce_before_checkout_form', array($this, '_woocommerce_checkout_login_enqueue_scripts'));
		add_action('wp_loaded', array($this, '_handle_woocommerce_registration'), 10, 0); //Woocommerce uses priority 20

		if ($this->is_woocommerce_account_integration_enabled()) {
			add_filter('woocommerce_account_menu_items', array($this, '_woocommerce_account_menu_items'));
			add_action('woocommerce_account_' . self::WOOCOMMERCE_PASSKEY_ENDPOINT . '_endpoint', array($this, '_woocommerce_account_passkey_menu_content'));
			add_action('woocommerce_account_' . self::WOOCOMMERCE_ENDPOINT . '_endpoint', array($this, '_woocommerce_account_menu_content'));
			add_filter('woocommerce_get_query_vars', array($this, '_woocommerce_get_query_vars'));
			add_action('wp_enqueue_scripts', array($this, '_woocommerce_account_enqueue_assets'));
			add_action('template_redirect', array($this, '_woocommerce_account_2fa_endpoint_access'));
		}
	}
	
	public function _admin_init() {
		if (WORDFENCE_LS_FROM_CORE) {
			\wfModuleController::shared()->addOptionIndex('wfls-option-enable-2fa-roles', __('Login Security: Enable 2FA for these roles', 'wordfence'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-enable-passkey-roles', __('Login Security: Enable Passkeys for these roles', 'wordfence'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-passkey-allowed-hostnames', __('Login Security: Allowed Passkey Hostnames', 'wordfence'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-passkey-relying-party-override', __('Login Security: Passkey Credential Domain', 'wordfence'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-allow-remember', __('Login Security: Allow remembering device for 30 days', 'wordfence'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-always-show-login-security-menu', __('Login Security: Always show Login Security menu', 'wordfence'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-require-2fa-xml-rpc', __('Login Security: Require 2FA for XML-RPC call authentication', 'wordfence'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-disable-xml-rpc', __('Login Security: Disable XML-RPC authentication', 'wordfence'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-whitelist-2fa', __('Login Security: Allowlisted IP addresses that bypass 2FA, passkey requirements, and reCAPTCHA', 'wordfence'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-enable-captcha', __('Login Security: Enable reCAPTCHA on the login and user registration pages', 'wordfence'));
			
			$title = __('Login Security Options', 'wordfence');
			$description = __('Login Security options are available on the Login Security options page', 'wordfence');
			$url = esc_url(network_admin_url('admin.php?page=WFLS#top#settings'));
			$link = __('Login Security Options', 'wordfence');;
			\wfModuleController::shared()->addOptionBlock(<<<END
<div class="wf-row">
	<div class="wf-col-xs-12">
		<div class="wf-block wf-always-active" data-persistence-key="">
			<div class="wf-block-header">
				<div class="wf-block-header-content">
					<div class="wf-block-title">
						<strong>{$title}</strong>
					</div>
				</div>
			</div>
			<div class="wf-block-content">
				<ul class="wf-block-list">
					<li>
						<ul class="wf-flex-horizontal wf-flex-vertical-xs wf-flex-full-width wf-add-top wf-add-bottom">
							<li>{$description}</li>
							<li class="wf-right wf-left-xs wf-padding-add-top-xs-small">
								<a href="{$url}" class="wf-btn wf-btn-primary wf-btn-callout-subtle" id="wf-login-security-options">{$link}</a>
							</li>
						</ul>
						<input type="hidden" id="wfls-option-enable-2fa-roles">
						<input type="hidden" id="wfls-option-enable-passkey-roles">
						<input type="hidden" id="wfls-option-passkey-allowed-hostnames">
						<input type="hidden" id="wfls-option-passkey-relying-party-override">
						<input type="hidden" id="wfls-option-allow-remember">
						<input type="hidden" id="wfls-option-always-show-login-security-menu">
						<input type="hidden" id="wfls-option-require-2fa-xml-rpc">
						<input type="hidden" id="wfls-option-disable-xml-rpc">
						<input type="hidden" id="wfls-option-whitelist-2fa">
						<input type="hidden" id="wfls-option-enable-captcha">
					</li>
				</ul>
			</div>
		</div>
	</div>
</div> <!-- end ls options -->
END
);
		}

		if (Controller_Permissions::shared()->can_manage_settings()) {
			if ((is_plugin_active('jetpack/jetpack.php') || (is_multisite() && is_plugin_active_for_network('jetpack/jetpack.php'))) && !Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ALLOW_XML_RPC)) {
				if (is_multisite()) {
					add_action('network_admin_notices', array($this, '_jetpack_xml_rpc_notice'));
				}
				else {
					add_action('admin_notices', array($this, '_jetpack_xml_rpc_notice'));
				}
			}

			if (Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_CAPTCHA_TEST_MODE) && Controller_CAPTCHA::shared()->enabled()) {
				if (is_multisite()) {
					add_action('network_admin_notices', array($this, '_recaptcha_test_notice'));
				}
				else {
					add_action('admin_notices', array($this, '_recaptcha_test_notice'));
				}
			}

			if ($this->should_show_passkey_hostname_lockout_notice()) {
				add_action(is_multisite() ? 'network_admin_notices' : 'admin_notices', array($this, '_passkey_hostname_lockout_notice'));
			}

			if ($this->has_woocommerce() && !Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION)) {
				if (!Controller_Notices::shared()->is_persistent_notice_dismissed(get_current_user_id(), Controller_Notices::PERSISTENT_NOTICE_WOOCOMMERCE_INTEGRATION)) {
					Controller_Notices::shared()->register_persistent_notice(Controller_Notices::PERSISTENT_NOTICE_WOOCOMMERCE_INTEGRATION);
					add_action(is_multisite() ? 'network_admin_notices' : 'admin_notices', array($this, '_woocommerce_integration_notice'));
				}
			}



			if (!WORDFENCE_LS_FROM_CORE) {
				if (!Controller_Notices::shared()->is_persistent_notice_dismissed(get_current_user_id(), Controller_Notices::PERSISTENT_NOTICE_STANDALONE_DISCONTINUING)) {
					Controller_Notices::shared()->register_persistent_notice(Controller_Notices::PERSISTENT_NOTICE_STANDALONE_DISCONTINUING);
					add_action('admin_notices', array($this, '_standalone_discontinuing_integration_notice'));
					if (is_multisite()) {
						add_action('network_admin_notices', array($this, '_standalone_discontinuing_integration_notice'));
					}
				}
				else if (isset($_GET['page']) && $_GET['page'] == 'WFLS') {
					add_action('admin_notices', array($this, '_standalone_discontinuing_integration_notice'));
					if (is_multisite()) {
						add_action('network_admin_notices', array($this, '_standalone_discontinuing_integration_notice'));
					}
				}
			}
		}
	}
	
	/**
	 * Notices
	 */
	
	public function _jetpack_xml_rpc_notice() {
		echo '<div class="notice notice-warning"><p>' . wp_kses(sprintf(/* translators: Configuration URL */ __('XML-RPC authentication is disabled. Jetpack is currently active and requires XML-RPC authentication to work correctly. <a href="%s">Manage Settings</a>', 'wordfence'), esc_url(network_admin_url('admin.php?page=WFLS#top#settings'))), array('a'=>array('href'=>array()))) . '</p></div>';
	}
	
	public function _recaptcha_test_notice() {
		echo '<div class="notice notice-warning"><p>' . wp_kses(sprintf(/* translators: Configuration URL */ __('reCAPTCHA test mode is enabled. While enabled, login and registration requests will be checked for their score but will not be blocked if the score is below the minimum score. <a href="%s">Manage Settings</a>', 'wordfence'), esc_url(network_admin_url('admin.php?page=WFLS#top#settings'))), array('a'=>array('href'=>array()))) . '</p></div>';
	}

	/**
	 * Parses a URL into the host, scheme, effective port, and display authority used by passkey diagnostics.
	 *
	 * @param string $url URL to parse.
	 * @return array|null
	 */
	private function passkey_origin_context_from_url($url) {
		$parts = is_string($url) ? wp_parse_url($url) : false;
		if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
			return null;
		}

		$scheme = strtolower($parts['scheme']);
		if ($scheme !== 'https' && $scheme !== 'http') {
			return null;
		}
		$host = strtolower(rtrim(trim($parts['host'], '[]'), '.'));
		$port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'http' ? 80 : 443);
		if ($host === '' || $port < 1 || $port > 65535) {
			return null;
		}
		$displayHost = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '[' . $host . ']' : $host;
		$defaultPort = $scheme === 'http' ? 80 : 443;

		return array(
			'scheme' => $scheme,
			'host' => $host,
			'port' => $port,
			'authority' => $displayHost . ($port === $defaultPort ? '' : ':' . $port),
		);
	}

	/**
	 * Returns the current admin request URL, falling back to the configured admin URL when needed.
	 *
	 * @return string
	 */
	private function current_admin_request_url() {
		$url = '';
		if (isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== '') {
			$url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
		}
		else {
			$url = (function_exists('is_network_admin') && is_network_admin()) ? network_admin_url() : admin_url();
		}

		return $url;
	}

	/**
	 * Returns the canonical WordPress login URL.
	 *
	 * @return string
	 */
	private function canonical_login_url() {
		return function_exists('wp_login_url') ? wp_login_url() : site_url('wp-login.php');
	}

	/**
	 * Returns a stable hash for the active passkey hostname lockout notice state.
	 *
	 * @param array $contextBlockers Hostname blockers from passkey_hostname_login_context_blockers().
	 * @return string
	 */
	private function passkey_hostname_lockout_signature($contextBlockers) {
		$signature = array();
		foreach ($contextBlockers as $context) {
			if (empty($context['host']) || empty($context['blockers']) || !is_array($context['blockers'])) {
				continue;
			}

			$host = strtolower(rtrim(trim($context['host']), '.'));
			if ($host === '') {
				continue;
			}

			$blockerKeys = array_keys($context['blockers']);
			sort($blockerKeys, SORT_STRING);
			$signature[$host] = $blockerKeys;
		}

		if (empty($signature)) {
			return '';
		}

		ksort($signature, SORT_STRING);
		$signature = array(
			'version' => Controller_Settings::shared()->get_int(Controller_Settings::OPTION_PASSKEY_HOSTNAME_WARNING_VERSION),
			'blockers' => $signature,
		);
		$encoded = function_exists('wp_json_encode') ? wp_json_encode($signature) : json_encode($signature);
		return is_string($encoded) ? hash('sha256', $encoded) : '';
	}

	/**
	 * Returns the current passkey hostname lockout notice signature.
	 *
	 * @return string
	 */
	public function current_passkey_hostname_lockout_signature() {
		return $this->passkey_hostname_lockout_signature($this->passkey_hostname_login_context_blockers());
	}

	/**
	 * Returns whether the passkey hostname lockout notice should be shown to the current user.
	 *
	 * @return bool
	 */
	public function should_show_passkey_hostname_lockout_notice() {
		$signature = $this->current_passkey_hostname_lockout_signature();
		if ($signature === '') {
			return false;
		}

		return get_user_option(self::USER_OPTION_DISMISSED_PASSKEY_HOSTNAME_LOCKOUT_SIGNATURE, get_current_user_id()) !== $signature;
	}

	/**
	 * Dismisses the current passkey hostname lockout notice for the current user.
	 *
	 * @param string|null $expectedSignature Expected notice signature, or null to dismiss the current signature.
	 * @return bool
	 */
	public function dismiss_current_passkey_hostname_lockout_notice($expectedSignature = null) {
		$userId = get_current_user_id();
		$signature = $this->current_passkey_hostname_lockout_signature();
		if ($userId === 0 || $signature === '') {
			return false;
		}
		if ($expectedSignature !== null && !hash_equals($signature, $expectedSignature)) {
			return false;
		}

		return (bool) update_user_option($userId, self::USER_OPTION_DISMISSED_PASSKEY_HOSTNAME_LOCKOUT_SIGNATURE, $signature, true);
	}

	/**
	 * Returns the initial allowed passkey hostnames preview for a multisite super admin.
	 *
	 * @param \WP_User $user User whose passkey form is being displayed.
	 * @return string[]
	 */
	private function initial_allowed_passkey_hostnames_for_registration($user) {
		if (!is_multisite() || !function_exists('is_super_admin') || !is_super_admin($user->ID) || !Controller_Settings::shared()->passkey_allowed_hostnames_missing()) {
			return array();
		}

		return Controller_Settings::shared()->initial_passkey_allowed_hostnames(
			Controller_Passkey::shared()->get_rp_id(),
			$this->current_admin_request_url()
		);
	}

	/**
	 * Returns whether passkey hostname lockout checks apply for the current admin.
	 *
	 * @param \WP_User $user User to evaluate.
	 * @param Controller_Passkey $passkeyController Passkey controller.
	 * @return bool
	 */
	private function passkey_hostname_login_blocker_applies($user, $passkeyController) {
		if (!Controller_Settings::shared()->are_passkeys_enabled()
			|| !$user instanceof \WP_User
			|| !$user->exists()
			|| !Controller_Permissions::shared()->can_manage_settings($user)) {
			return false;
		}

		return $passkeyController->any_passkeys_active();
	}

	/**
	 * Returns passkey hostname-policy problems for a single browser-facing origin context.
	 *
	 * @param Controller_Passkey $passkeyController Passkey controller.
	 * @param array $context Parsed origin context.
	 * @return array
	 */
	private function passkey_hostname_policy_blockers($passkeyController, $context) {
		if (!is_array($context) || empty($context['host']) || empty($context['scheme']) || empty($context['port']) || empty($context['authority'])) {
			return array();
		}

		$rpId = $passkeyController->get_rp_id();
		$blockers = array();
		if (!$passkeyController->is_origin_host_valid_for_rp($context['host'], $rpId)) {
			$blockers['credential_domain'] = array(
				'host' => $context['authority'],
				'rp_id' => $rpId,
			);
		}
		if (!$passkeyController->is_allowed_origin_host($context['host'], $context['port'], $context['scheme'])) {
			$blockers['allowed_hostnames'] = array(
				'host' => $context['authority'],
			);
		}
		return $blockers;
	}

	/**
	 * Returns passkey hostname-policy problems that could prevent the admin from logging back in on a hostname.
	 *
	 * @param \WP_User|null $user User to evaluate, or current user when omitted.
	 * @param string|null $host Hostname to evaluate, or current admin request hostname when omitted.
	 * @return array
	 */
	public function passkey_hostname_login_blockers($user = null, $host = null) {
		if (!$user instanceof \WP_User) {
			$user = wp_get_current_user();
		}

		$passkeyController = Controller_Passkey::shared();
		if (!$this->passkey_hostname_login_blocker_applies($user, $passkeyController)) {
			return array();
		}

		if ($host === null) {
			$context = $this->passkey_origin_context_from_url($this->current_admin_request_url());
		}
		else if (is_string($host) && strpos($host, '://') !== false) {
			$context = $this->passkey_origin_context_from_url($host);
		}
		else {
			$parsed = Controller_Settings::shared()->parse_passkey_allowed_hostname($host);
			$context = is_array($parsed) ? array(
				'scheme' => 'https',
				'host' => $parsed['host'],
				'port' => $parsed['port'] === null ? 443 : $parsed['port'],
				'authority' => $parsed['entry'],
			) : null;
		}
		return $this->passkey_hostname_policy_blockers($passkeyController, $context);
	}

	/**
	 * Returns passkey hostname-policy problems for login-relevant hostnames.
	 *
	 * @param \WP_User|null $user User to evaluate, or current user when omitted.
	 * @return array
	 */
	public function passkey_hostname_login_context_blockers($user = null) {
		if (!$user instanceof \WP_User) {
			$user = wp_get_current_user();
		}

		$passkeyController = Controller_Passkey::shared();
		if (!$this->passkey_hostname_login_blocker_applies($user, $passkeyController)) {
			return array();
		}

		$contexts = array(
			'current_admin' => array(
				'label' => __('current admin hostname', 'wordfence'),
				'origin' => $this->passkey_origin_context_from_url($this->current_admin_request_url()),
			),
			'canonical_login' => array(
				'label' => __('canonical login hostname', 'wordfence'),
				'origin' => $this->passkey_origin_context_from_url($this->canonical_login_url()),
			),
		);

		$results = array();
		$seenHosts = array();
		foreach ($contexts as $key => $context) {
			$origin = $context['origin'];
			$host = is_array($origin) ? $origin['authority'] : '';
			if ($host === '' || in_array($host, $seenHosts, true)) {
				continue;
			}
			$seenHosts[] = $host;
			$blockers = $this->passkey_hostname_policy_blockers($passkeyController, $origin);
			if (!empty($blockers)) {
				unset($context['origin']);
				$context['host'] = $host;
				$context['blockers'] = $blockers;
				$results[$key] = $context;
			}
		}
		return $results;
	}

	public function _passkey_hostname_lockout_notice() {
		$contextBlockers = $this->passkey_hostname_login_context_blockers();
		$signature = $this->passkey_hostname_lockout_signature($contextBlockers);
		if (empty($contextBlockers) || $signature === '' || get_user_option(self::USER_OPTION_DISMISSED_PASSKEY_HOSTNAME_LOCKOUT_SIGNATURE, get_current_user_id()) === $signature) {
			return;
		}

		$settingNames = array();
		$affectedHostLabels = array();
		foreach ($contextBlockers as $context) {
			$affectedHostLabels[] = sprintf('%s (%s)', $context['label'], $context['host']);
			if (array_key_exists('credential_domain', $context['blockers'])) {
				$settingNames['credential_domain'] = __('Passkey Credential Domain', 'wordfence');
			}
			if (array_key_exists('allowed_hostnames', $context['blockers'])) {
				$settingNames['allowed_hostnames'] = __('Allowed Passkey Hostnames', 'wordfence');
			}
		}
		$settingsURL = esc_url(network_admin_url('admin.php?page=WFLS#top#settings'));
		$helpURL = Controller_Support::esc_supportURL(Controller_Support::ITEM_MODULE_LOGIN_SECURITY_PASSKEY_HOSTNAME_WARNING);
		$signatureAttribute = function_exists('esc_attr') ? esc_attr($signature) : htmlspecialchars($signature, ENT_QUOTES, 'UTF-8');
		?>
		<div class="notice notice-error is-dismissible wfls-passkey-hostname-lockout-notice" data-passkey-hostname-lockout-signature="<?php echo $signatureAttribute; ?>">
			<p><strong><?php echo esc_html__('Passkey login may be blocked on this site.', 'wordfence'); ?></strong></p>
			<p>
				<?php
				echo esc_html(sprintf(
					/* translators: 1. Comma-separated hostname labels. 2. Comma-separated setting names. */
					__('The current passkey hostname settings do not accept one or more hostnames or ports this site may use for login: %1$s. Affected settings: %2$s. Because at least one passkey exists on this site, signing out may prevent affected users from logging back in with a passkey.', 'wordfence'),
					implode(', ', $affectedHostLabels),
					implode(', ', $settingNames)
				));
				?>
			</p>
			<p>
				<?php
				echo wp_kses(sprintf(
					/* translators: 1. Configuration URL. */
					__('Before signing out, update <a href="%1$s">Passkey settings</a> so the Passkey Credential Domain matches this hostname or a parent domain and so this hostname and any port other than 443 are listed in Allowed Passkey Hostnames. If wp-admin is no longer accessible, you may use WP-CLI recovery options to correct the passkey hostname settings.', 'wordfence'),
					$settingsURL
				), array('a' => array('href' => array())));
				?>
			</p>
			<p><a href="<?php echo $helpURL; ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Learn more about passkey hostname settings', 'wordfence'); ?></a></p>
		</div>
		<?php
	}

	public function _woocommerce_integration_notice() {
?>
		<div id="<?php echo esc_attr(Controller_Notices::PERSISTENT_NOTICE_WOOCOMMERCE_INTEGRATION) ?>" class="notice notice-warning is-dismissible wfls-persistent-notice">
			<p>
				<?php esc_html_e('WooCommerce appears to be installed, but the Wordfence Login Security WooCommerce integration is not currently enabled. Without this feature, WooCommerce forms will not support all functionality provided by Wordfence Login Security, including CAPTCHA for the login page and user registration.', 'wordfence'); ?>
				<a href="<?php echo esc_attr(esc_url(network_admin_url('admin.php?page=WFLS#top#settings'))) ?>"><?php esc_html_e('Manage Settings', 'wordfence') ?></a>
			</p>
		</div>
<?php
	}

	public function _standalone_discontinuing_integration_notice() {
		?>
		<div id="<?php echo esc_attr(Controller_Notices::PERSISTENT_NOTICE_STANDALONE_DISCONTINUING) ?>" class="notice notice-warning <?php if (!(isset($_GET['page']) && $_GET['page'] == 'WFLS')): ?>is-dismissible<?php endif; ?> wfls-persistent-notice">
			<p><strong><?php esc_html_e('Your site is currently using the "Wordfence Login Security” plugin.', 'wordfence') ?></strong></p>
			<p><?php esc_html_e('This plugin will be discontinued on or around August 12, 2026, because its features are already included in the main Wordfence plugin.', 'wordfence') ?></p>
			<p><?php esc_html_e('To continue receiving updates and security improvements, please install and activate the main Wordfence plugin — also available for free.', 'wordfence') ?></p>
			<p><a class="wfls-btn wfls-btn-primary wfls-btn-sm" href="<?php echo esc_url(Utility_URL::maybe_network_admin_url('plugin-install.php?s=wordfence&tab=search&type=term')) ?>"><?php esc_html_e('Install Wordfence', 'wordfence') ?></a></p>
		</div>
		<?php
	}
	
	/**
	 * Installation/Uninstallation
	 */
	
	public function _install_plugin() {
		$this->_install();
	}
	
	public function _uninstall_plugin() {
		Controller_Time::shared()->uninstall();
		Controller_Permissions::shared()->uninstall();
		
		foreach (array(self::VERSION_KEY, self::WOOCOMMERCE_ENDPOINTS_VERSION_KEY) as $opt) {
			if (is_multisite() && function_exists('delete_network_option')) {
				delete_network_option(null, $opt);
			}
			delete_option($opt);
		}
		
		if (Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_DELETE_ON_DEACTIVATION)) {
			Controller_DB::shared()->uninstall();
		}

		$this->purge_rewrite_rules();
	}
	
	protected function _install() {
		static $_runInstallCalled = false;
		if ($_runInstallCalled) { return; }
		$_runInstallCalled = true;
		
		if (function_exists('ignore_user_abort') && is_callable('ignore_user_abort')) {
			@ignore_user_abort(true);
		}
		
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		
		$previousVersion = ((is_multisite() && function_exists('get_network_option')) ? get_network_option(null, self::VERSION_KEY, false) : get_option(self::VERSION_KEY, false));
		if (is_multisite() && function_exists('update_network_option')) {
			update_network_option(null, self::VERSION_KEY, WORDFENCE_LS_VERSION); //In case we have a fatal error we don't want to keep running install.	
		}
		else {
			update_option(self::VERSION_KEY, WORDFENCE_LS_VERSION); //In case we have a fatal error we don't want to keep running install.
		}
		
		$previousSchemaVersion = Controller_DB::shared()->install();
		$freshInstall = $previousVersion === false && $previousSchemaVersion === 0;
		$initializePasskeyRoles = $previousSchemaVersion < Controller_DB::PASSKEY_SCHEMA_VERSION;
		$initialSettings = array();
		if ($freshInstall) {
			$initialSettings[Controller_Settings::OPTION_ENABLE_2FA] = Controller_Settings::DEFAULT_ENABLE_2FA;
		}
		if ($initializePasskeyRoles) {
			$initialSettings[Controller_Settings::OPTION_ENABLE_PASSKEYS] = Controller_Settings::DEFAULT_ENABLE_PASSKEYS;
		}
		if (!empty($initialSettings)) {
			Controller_Settings::shared()->set_multiple($initialSettings, true);
		}
		Controller_Settings::shared()->migrate_admin_2fa_requirements_to_roles();
		Controller_Settings::shared()->set_defaults();
		if (!function_exists('is_main_site') || is_main_site()) {
			Utility_URL::fetch_and_cache_public_suffix_list_if_missing();
		}
		
		if (\WordfenceLS\Controller_Time::time() > Controller_Settings::shared()->get_int(Controller_Settings::OPTION_LAST_SECRET_REFRESH) + 180 * 86400) {
			Model_Crypto::refresh_secrets();
		}

		Controller_Time::shared()->install();
		Controller_Permissions::shared()->install($freshInstall, $initializePasskeyRoles);

		$this->purge_rewrite_rules();
	}

	private function purge_rewrite_rules() {
		// This is usually done internally in WP_Rewrite::flush_rules, but is followed there by WP_Rewrite::wp_rewrite_rules which repopulates it. This should cause it to be repopulated on the next request.
		update_option('rewrite_rules', '');
	}

	/**
	 * Invalidates cached rewrite rules once when the WooCommerce endpoint set changes.
	 *
	 * @return void
	 */
	private function maybe_purge_outdated_woocommerce_rewrite_rules() {
		if ((int) get_option(self::WOOCOMMERCE_ENDPOINTS_VERSION_KEY, 0) >= self::WOOCOMMERCE_ENDPOINTS_VERSION) {
			return;
		}

		$this->purge_rewrite_rules();
		update_option(self::WOOCOMMERCE_ENDPOINTS_VERSION_KEY, self::WOOCOMMERCE_ENDPOINTS_VERSION);
	}

	/**
	 * In most cases, this will be done internally by WooCommerce since we are using the woocommerce_get_query_vars filter, but when toggling the option on our settings page we must still do this manually
	 */
	private function register_rewrite_endpoints() {
		add_rewrite_endpoint(self::WOOCOMMERCE_ENDPOINT, $this->is_woocommerce_account_integration_enabled() ? EP_PAGES : EP_NONE);
		add_rewrite_endpoint(self::WOOCOMMERCE_PASSKEY_ENDPOINT, $this->is_woocommerce_account_integration_enabled() ? EP_PAGES : EP_NONE);
	}

	public function refresh_rewrite_rules() {
		$this->register_rewrite_endpoints();
		flush_rewrite_rules();
		update_option(self::WOOCOMMERCE_ENDPOINTS_VERSION_KEY, self::WOOCOMMERCE_ENDPOINTS_VERSION);
	}
	
	public function _block_xml_rpc() {
		/**
		 * Fires just prior to blocking an XML-RPC request. After firing this action hook the XML-RPC request is blocked.
		 *
		 * @param int $source The source code of the block.
		 */
		do_action('wfls_xml_rpc_blocked', 2);
		return false;
	}

	private function has_woocommerce() {
		return class_exists('woocommerce');
	}

	private function is_woocommerce_integration_enabled() {
		return Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION);
	}

	private function is_woocommerce_account_integration_enabled() {
		return $this->is_woocommerce_integration_enabled() && Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ENABLE_WOOCOMMERCE_ACCOUNT_INTEGRATION);
	}

	private function is_shortcode_enabled() {
		return Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ENABLE_SHORTCODE);
	}

	public function _woocommerce_login_enqueue_scripts() {
		wp_enqueue_style('dashicons');
		$this->_login_enqueue_scripts();
	}

	public function _woocommerce_checkout_login_enqueue_scripts() {
		/**
		 * This is the same check used in WooCommerce to determine whether or not to display the checkout login form
		 * @see templates/checkout/form-login.php in WooCommerce
		 */
		if ( is_user_logged_in() || 'no' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
			return;
		}
		$this->_woocommerce_login_enqueue_scripts();
	}
	
	/**
	 * Login Page
	 */	
	public function _login_enqueue_scripts() {
		$useCAPTCHA = Controller_CAPTCHA::shared()->enabled();
		if ($useCAPTCHA) {
			wp_enqueue_script('wordfence-ls-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . urlencode(Controller_Settings::shared()->get(Controller_Settings::OPTION_RECAPTCHA_SITE_KEY)));
		}

		$hasPasskeys = false;
		$shouldEnqueue = $useCAPTCHA;
		if (!$shouldEnqueue && Controller_Settings::shared()->is_2fa_enabled()) {
			$shouldEnqueue = Controller_Users::shared()->any_2fa_active();
		}
		if (!$shouldEnqueue && Controller_Settings::shared()->are_passkeys_enabled()) {
			$hasPasskeys = Controller_Passkey::shared()->any_passkeys_active();
			$shouldEnqueue = $hasPasskeys;
		}

		if ($shouldEnqueue) {
			if (!$hasPasskeys && Controller_Settings::shared()->are_passkeys_enabled()) {
				$hasPasskeys = Controller_Passkey::shared()->any_passkeys_active();
			}
			Model_Script::create('wflsi18njs', Model_Asset::js('wflsi18n.js'), array(), WORDFENCE_LS_VERSION)
				->withTranslations(Controller_Javascript::i18nStrings())
				->setTranslationObjectName('WordfenceLSI18nStrings')
				->enqueue();
			
			Model_Script::create('wordfence-ls-login', Model_Asset::js('login.js'), array('jquery', 'wflsi18njs'), WORDFENCE_LS_VERSION)
				->withTranslations(array(
					'Message to Support' => __('Message to Support', 'wordfence'),
					'Send' => __('Send', 'wordfence'),
					'An error was encountered while trying to send the message. Please try again.' => __('An error was encountered while trying to send the message. Please try again.', 'wordfence'),
					'<strong>ERROR</strong>: An error was encountered while trying to send the message. Please try again.' => wp_kses(__('<strong>ERROR</strong>: An error was encountered while trying to send the message. Please try again.', 'wordfence'), array('strong' => array())),
					'Login failed with status code 403. Please contact the site administrator.' => __('Login failed with status code 403. Please contact the site administrator.', 'wordfence'),
					'<strong>ERROR</strong>: Login failed with status code 403. Please contact the site administrator.' => wp_kses(__('<strong>ERROR</strong>: Login failed with status code 403. Please contact the site administrator.', 'wordfence'), array('strong' => array())),
					'Login failed with status code 503. Please contact the site administrator.' => __('Login failed with status code 503. Please contact the site administrator.', 'wordfence'),
					'<strong>ERROR</strong>: Login failed with status code 503. Please contact the site administrator.' => wp_kses(__('<strong>ERROR</strong>: Login failed with status code 503. Please contact the site administrator.', 'wordfence'), array('strong' => array())),
						'Wordfence 2FA Code' => __('Wordfence 2FA Code', 'wordfence'),
						'Remember for 30 days' => __('Remember for 30 days', 'wordfence'),
						'Log In' => __('Log In', 'wordfence'),
						'<strong>ERROR</strong>: An error was encountered while trying to authenticate. Please try again.' => wp_kses(__('<strong>ERROR</strong>: An error was encountered while trying to authenticate. Please try again.', 'wordfence'), array('strong' => array())),
						'The Wordfence 2FA Code can be found within the authenticator app you used when first activating two-factor authentication. You may also use one of your recovery codes.' => __('The Wordfence 2FA Code can be found within the authenticator app you used when first activating two-factor authentication. You may also use one of your recovery codes.', 'wordfence')
				))
				->setTranslationObjectName('WFLS_LOGIN_TRANSLATIONS')
				->enqueue();
			wp_enqueue_style('wordfence-ls-login', Model_Asset::css('login.css'), array(), WORDFENCE_LS_VERSION);
			wp_localize_script('wordfence-ls-login', 'WFLSVars', $this->get_wfls_script_vars(array('hasPasskeys' => $hasPasskeys)));
		}
	}

	private function get_wfls_script_vars($additional = array()) {
		$this->validate_email_verification_token(null, $verification);
		$hasPasskeys = array_key_exists('hasPasskeys', $additional) ? $additional['hasPasskeys'] : Controller_Passkey::shared()->any_passkeys_active();

		return array_merge(array(
			'ajaxurl' => Utility_URL::relative_admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('wp-ajax'),
			'passkeyIconUrl' => Model_Asset::img('passkey.svg'),
			'recaptchasitekey' => Controller_Settings::shared()->get(Controller_Settings::OPTION_RECAPTCHA_SITE_KEY),
			'useCAPTCHA' => Controller_CAPTCHA::shared()->enabled(),
			'hasPasskeys' => $hasPasskeys,
			'allowremember' => Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_REMEMBER_DEVICE_ENABLED),
			'verification' => $verification,
		), $additional);
	}

	private function get_2fa_management_script_data() {
		return array(
			'WFLSVars' => $this->get_wfls_script_vars(),
		);
	}

	public function should_use_core_font_awesome_styles() {
		if ($this->use_core_font_awesome_styles === null) {
			$this->use_core_font_awesome_styles = wp_style_is('wordfence-font-awesome-style');
		}
		return $this->use_core_font_awesome_styles;
	}

	public static function normalize_ui_style_context($context) {
		return $context === self::UI_STYLE_CONTEXT_CORE ? self::UI_STYLE_CONTEXT_CORE : self::UI_STYLE_CONTEXT_WFLS;
	}

	public function ui_style_context() {
		return $this->should_use_core_font_awesome_styles() ? self::UI_STYLE_CONTEXT_CORE : self::UI_STYLE_CONTEXT_WFLS;
	}

	private function get_2fa_management_assets($embedded = false) {
		$assets = array(
			Model_Script::create('jquery-ui-dialog')->setRegistered(),
			Model_Script::create('wordfence-ls-jquery.qrcode', Model_Asset::js('jquery.qrcode.min.js'), array('jquery'), WORDFENCE_LS_VERSION),
		);
		$assets[] = Model_Script::create('wordfence-ls-admin', Model_Asset::js('admin.js'), array('jquery', 'jquery-ui-dialog'), WORDFENCE_LS_VERSION)
			->withTranslation('You have unsaved changes to your options. If you leave this page, those changes will be lost.', __('You have unsaved changes to your options. If you leave this page, those changes will be lost.', 'wordfence'))
			->setTranslationObjectName('WFLS_ADMIN_TRANSLATIONS');
		$registered = array(
			Model_Script::create('chart-js', Model_Asset::js('chart.umd.js'), array('jquery'), '4.2.1')->setRegistered(),
		);
		if (!WORDFENCE_LS_FROM_CORE && !$this->management_assets_registered) {
			foreach ($registered as $asset)
				$asset->register();
			$this->management_assets_registered = true;
		}
		$assets = array_merge($assets, $registered);
		$assets[] = Model_Style::create('wp-jquery-ui-dialog')->setRegistered();
		$assets[] = Model_Style::create('dashicons')->setRegistered();
		$assets[] = Model_Style::create('wordfence-ls-admin', Model_Asset::css('admin.css'), array('wp-jquery-ui-dialog', 'dashicons'), WORDFENCE_LS_VERSION);
		$assets[] = Model_Style::create('wordfence-ls-ionicons', Model_Asset::css('ionicons.css'), array(), WORDFENCE_LS_VERSION);
		if ($embedded) {
			$assets[] = Model_Style::create('wordfence-ls-embedded', Model_Asset::css('embedded.css'), array(), WORDFENCE_LS_VERSION);
		}
		else {
			$assets[] = Model_Script::create('wflsi18njs', Model_Asset::js('wflsi18n.js'), array(), WORDFENCE_LS_VERSION)->withTranslations(Controller_Javascript::i18nStrings())->setTranslationObjectName('WordfenceLSI18nStrings');
			if (!WORDFENCE_LS_FROM_CORE) {
				$assets[] = Model_Script::create('wordfence-ls-vue', Model_Asset::js('wordfence-login-security.js'), array('jquery'), WORDFENCE_LS_VERSION);
			}
		}

		if (!$this->should_use_core_font_awesome_styles()) {
			$assets[] = Model_Style::create('wordfence-ls-font-awesome', Model_Asset::css('font-awesome.css'), array(), WORDFENCE_LS_VERSION);
		}


		return $assets;
	}

	private function enqueue_2fa_management_assets($embedded = false) {
		if ($this->management_assets_enqueued) { return; }
		foreach ($this->get_2fa_management_assets($embedded) as $asset) {
			$asset->enqueue();
		}
		foreach ($this->get_2fa_management_script_data() as $key => $data) {
			wp_localize_script('wordfence-ls-admin', $key, $data);
		}
		$this->setupJSConstants(!$embedded);
		$this->management_assets_enqueued = true;
	}

	/**
	 * Admin Pages
	 */
	public function _admin_enqueue_scripts($hookSuffix) {
		if (isset($_GET['page']) && $_GET['page'] == 'WFLS') {
			$this->enqueue_2fa_management_assets();
		}
		else {
			wp_enqueue_style('wordfence-ls-admin-global', Model_Asset::css('admin-global.css'), array(), WORDFENCE_LS_VERSION);
		}
		
		if (Controller_Notices::shared()->has_notice(wp_get_current_user()) || $this->should_show_passkey_hostname_lockout_notice() || in_array($hookSuffix, array('user-edit.php', 'user-new.php', 'profile.php'))) {
			wp_enqueue_script('wordfence-ls-admin-global', Model_Asset::js('admin-global.js'), array('jquery'), WORDFENCE_LS_VERSION);
			
			wp_localize_script('wordfence-ls-admin-global', 'GWFLSVars', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('wp-ajax'),
			));
		}

	}

	/**
	 * Leverages an internalized version of `wp_localize_script` to pass through a set of constants for the Vue side to
	 * avoid hardcoding values.
	 *
	 * @param bool $includeUserSummary Whether to include settings-page user summary data.
	 */
	private function setupJSConstants($includeUserSummary = false) {
		static $called;
		if ($called) {
			return;
		}
		$called = true;

		global $wp_scripts;
		$script = "var WordfenceLSJSConstants = " . wp_json_encode(Controller_Javascript::jsConstants($includeUserSummary)) . ";\n";

		$handle = WORDFENCE_LS_FROM_CORE ? 'wordfenceVuejs' : 'wordfence-ls-vue';
		$data = $wp_scripts->get_data($handle, 'data');

		if (!empty($data)) {
			$script = "$data\n$script";
		}

		$wp_scripts->add_data($handle, 'data', $script);
	}

	public function _setupImportMap() {
		if (!WORDFENCE_LS_FROM_CORE && isset($_GET['page']) && $_GET['page'] == 'WFLS') {
			echo "<script type=\"importmap\">" . wp_json_encode(Controller_Javascript::importMap()) . "</script>\n";
		}
	}

	public function _tagVueScriptAsModule($tag, $handle, $src) {
		if (WORDFENCE_LS_FROM_CORE) {
			return $tag;
		}

		if ('wordfence-ls-vue' == $handle && strpos($tag, 'module') === false) {
			if (($typeIndex = strpos($tag, 'type=')) !== false) {
				$quoteChar = substr($tag, $typeIndex + 5, 1);
				$closingQuoteIndex = strpos($tag, $quoteChar, $typeIndex + 6);
				$tag = str_replace(substr($tag, $typeIndex, $closingQuoteIndex - $typeIndex + 1), 'type="module"', $tag);
			}
			else {
				$tag = str_replace(' src', ' type="module" src', $tag);
			}
		}
		return $tag;
	}
	
	public function _edit_user_profile($user) {
		$viewerIsUser = $user->ID == get_current_user_id();
		$viewerCanManage2fa = current_user_can(Controller_Permissions::CAP_ACTIVATE_2FA_OTHERS);
		$viewerCanManagePasskeys = current_user_can(Controller_Permissions::CAP_MANAGE_PASSKEY_OTHERS);
		if ($viewerIsUser || (!$viewerCanManage2fa && !$viewerCanManagePasskeys)) {
			$manageURL = admin_url('admin.php?page=WFLS');
		}
		else {
			$manageURL = admin_url('admin.php?page=WFLS&user=' . ((int) $user->ID));
		}
		
		if (is_multisite() && is_super_admin()) {
			if ($viewerIsUser) {
				$manageURL = network_admin_url('admin.php?page=WFLS');
			}
			else {
				$manageURL = network_admin_url('admin.php?page=WFLS&user=' . ((int) $user->ID));
			}
		}
		$manage2FAURL = $manageURL . '#top#manage';
		$managePasskeyURL = $manageURL . '#top#passkey';
		$settingsURL = is_multisite() ? network_admin_url('admin.php?page=WFLS#top#settings') : admin_url('admin.php?page=WFLS#top#settings');
		$twoFactorGloballyEnabled = Controller_Settings::shared()->is_2fa_enabled();
		$passkeysGloballyEnabled = Controller_Settings::shared()->are_passkeys_enabled();
		$userAllowed2fa = Controller_Users::shared()->can_activate_2fa($user);
		$userAllowedPasskeys = Controller_Users::shared()->can_manage_passkey($user);
		$viewerCanManageSettings = Controller_Permissions::shared()->can_manage_settings();
		$showDisabledSelfAuthSections = $viewerIsUser && Controller_Settings::shared()->should_always_show_login_security_menu() && current_user_can(Controller_Permissions::CAP_SHOW_LOGIN_SECURITY);
		$showDisabled2FASection = !$userAllowed2fa && ($viewerCanManage2fa || $viewerCanManageSettings || $showDisabledSelfAuthSections);
		$showDisabledPasskeySection = !$userAllowedPasskeys && ($viewerCanManagePasskeys || $viewerCanManageSettings || $showDisabledSelfAuthSections);
		$show2FASection = ($userAllowed2fa && ($viewerIsUser || $viewerCanManage2fa)) || $showDisabled2FASection;
		$showPasskeySection = ($userAllowedPasskeys && ($viewerIsUser || $viewerCanManagePasskeys)) || $showDisabledPasskeySection;
		$requires2fa = Controller_Users::shared()->requires_2fa($user, $inGracePeriod, $requiredAt);
		$has2fa = Controller_Users::shared()->has_2fa_active($user);
		$lockedOut = $requires2fa && !$has2fa;
		$requiresPasskey = Controller_Users::shared()->requires_passkey($user, $inPasskeyGracePeriod, $passkeyRequiredAt);
		$hasPasskey = Controller_Users::shared()->has_passkey_active($user);
		$passkeyLockedOut = $requiresPasskey && !$hasPasskey;
		$show2FAManageButton = $userAllowed2fa && ($viewerIsUser || $viewerCanManage2fa);
		$showPasskeyManageButton = $userAllowedPasskeys && ($viewerIsUser || $viewerCanManagePasskeys);
		$disabled2FAMessage = !$twoFactorGloballyEnabled
			? __('Signing in using 2FA is currently disabled for this site. Existing credentials and role settings are preserved.', 'wordfence')
			: ($viewerIsUser
				? __('Your role does not have permission to activate two-factor authentication.', 'wordfence')
				: ($viewerCanManageSettings ? __('Enable two-factor authentication on the settings page for this user\'s role to manage 2FA for the user.', 'wordfence') : __('Two-factor authentication is not enabled for this user\'s role.', 'wordfence')));
		$disabledPasskeyMessage = !$passkeysGloballyEnabled
			? __('Signing in using a passkey is currently disabled for this site. Existing passkeys and role settings are preserved.', 'wordfence')
			: ($viewerIsUser
				? __('Your role does not have permission to use passkeys.', 'wordfence')
				: ($viewerCanManageSettings ? __('Enable passkeys on the settings page for this user\'s role to manage the user\'s passkeys.', 'wordfence') : __('Passkeys are not enabled for this user\'s role.', 'wordfence')));
		if ($show2FASection || $showPasskeySection):
?>
		<h2 id="wfls-user-settings"><?php esc_html_e('Wordfence Login Security', 'wordfence'); ?></h2>
		<table class="form-table">
			<?php if ($show2FASection): ?>
			<tr id="wordfence-ls">
				<th><label for="wordfence-ls-btn"><?php esc_html_e('2FA Status', 'wordfence'); ?></label></th>
				<td>
					<?php if ($userAllowed2fa): ?>
						<p>
							<strong><?php echo $lockedOut ? esc_html__('Locked Out', 'wordfence') : ($has2fa ? esc_html__('Active', 'wordfence') :  esc_html__('Inactive', 'wordfence')); ?>:</strong>
							<?php echo $lockedOut ?
								($viewerIsUser ? esc_html__('Two-factor authentication is required for your account, but has not been configured.', 'wordfence') : esc_html__('Two-factor authentication is required for this user, but has not been configured.', 'wordfence'))
								: ($has2fa ? esc_html__('Wordfence 2FA is active.', 'wordfence') :  esc_html__('Wordfence 2FA is inactive.', 'wordfence')); ?>
							<a href="<?php echo Controller_Support::esc_supportURL(Controller_Support::ITEM_MODULE_LOGIN_SECURITY_2FA); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn More', 'wordfence'); ?></a>
						</p>
						<?php if (!$has2fa && $inGracePeriod): ?>
							<p><strong><?php echo sprintf($viewerIsUser ?
										/* translators: Date */ esc_html__('Two-factor authentication must be activated for your account prior to %s to avoid losing access.', 'wordfence')
								: /* translators: Date */ esc_html__('Two-factor authentication must be activated for this user prior to %s.', 'wordfence')
								, Controller_Time::format_local_time('F j, Y g:i A', $requiredAt)) ?></strong></p>
						<?php endif ?>
						<?php if ($show2FAManageButton): ?><p><a href="<?php echo esc_url($manage2FAURL); ?>" class="button"><?php echo (!$has2fa && $viewerIsUser ? esc_html__('Activate 2FA', 'wordfence') : esc_html__('Manage 2FA', 'wordfence')); ?></a></p><?php endif ?>
					<?php else: ?>
						<p><strong><?php esc_html_e('Disabled', 'wordfence'); ?>:</strong> <?php echo esc_html($disabled2FAMessage); ?></p>
						<?php if ($viewerCanManageSettings): ?>
							<p><a href="<?php echo esc_url($settingsURL); ?>" class="button" aria-label="<?php esc_attr_e('Manage Login Security Settings', 'wordfence'); ?>"><span class="wfls-btn-label-full" aria-hidden="true"><?php esc_html_e('Manage Login Security Settings', 'wordfence'); ?></span><span class="wfls-btn-label-xs" aria-hidden="true"><?php esc_html_e('Manage', 'wordfence'); ?></span></a></p>
						<?php endif ?>
					<?php endif ?>
					<?php if ($userAllowed2fa && $viewerCanManage2fa): ?>
						<?php if ($lockedOut): ?>
							<?php echo Model_View::create(
								'common/reset-grace-period',
								array(
									'user' => $user,
									'gracePeriod' => $inGracePeriod
								))->render() ?>
						<?php elseif ($inGracePeriod && Controller_Users::shared()->has_revokable_grace_period($user)): ?>
							<?php echo Model_View::create(
								'common/revoke-grace-period',
								array(
									'user' => $user
								))->render() ?>
							<?php endif ?>
					<?php endif ?>
				</td>
			</tr>
			<?php endif ?>
			<?php if ($showPasskeySection): ?>
			<tr id="wordfence-ls-passkey">
				<th><label for="wordfence-ls-passkey-btn"><?php esc_html_e('Passkey Status', 'wordfence'); ?></label></th>
				<td>
					<?php if ($userAllowedPasskeys): ?>
						<p>
							<strong><?php echo $passkeyLockedOut ? esc_html__('Locked Out', 'wordfence') : ($hasPasskey ? esc_html__('Active', 'wordfence') :  esc_html__('Inactive', 'wordfence')); ?>:</strong>
							<?php echo $passkeyLockedOut ?
								($viewerIsUser ? esc_html__('A passkey is required for your account, but has not been configured.', 'wordfence') : esc_html__('A passkey is required for this user, but has not been configured.', 'wordfence'))
								: ($hasPasskey ? esc_html__('Wordfence passkeys are active.', 'wordfence') :  esc_html__('Wordfence passkeys are inactive.', 'wordfence')); ?>
							<a href="<?php echo Controller_Support::esc_supportURL(Controller_Support::ITEM_MODULE_LOGIN_SECURITY_PASSKEYS); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn More', 'wordfence'); ?></a>
						</p>
						<?php if (!$hasPasskey && $inPasskeyGracePeriod): ?>
							<p><strong><?php echo sprintf($viewerIsUser ?
										/* translators: Date */ esc_html__('A passkey must be added for your account prior to %s to avoid losing access.', 'wordfence')
								: /* translators: Date */ esc_html__('A passkey must be added for this user prior to %s.', 'wordfence')
								, Controller_Time::format_local_time('F j, Y g:i A', $passkeyRequiredAt)) ?></strong></p>
						<?php endif ?>
						<?php if ($showPasskeyManageButton): ?><p><a href="<?php echo esc_url($managePasskeyURL); ?>" class="button"><?php echo (!$hasPasskey && $viewerIsUser ? esc_html__('Add Passkey', 'wordfence') : esc_html__('Manage Passkeys', 'wordfence')); ?></a></p><?php endif ?>
					<?php else: ?>
						<p><strong><?php esc_html_e('Disabled', 'wordfence'); ?>:</strong> <?php echo esc_html($disabledPasskeyMessage); ?></p>
						<?php if ($viewerCanManageSettings): ?>
							<p><a href="<?php echo esc_url($settingsURL); ?>" class="button" aria-label="<?php esc_attr_e('Manage Login Security Settings', 'wordfence'); ?>"><span class="wfls-btn-label-full" aria-hidden="true"><?php esc_html_e('Manage Login Security Settings', 'wordfence'); ?></span><span class="wfls-btn-label-xs" aria-hidden="true"><?php esc_html_e('Manage', 'wordfence'); ?></span></a></p>
						<?php endif ?>
					<?php endif ?>
				</td>
			</tr>
			<?php endif ?>
		</table>
<?php
		endif;
	}
	
	/**
	 * Authentication
	 */

	private function _is_woocommerce_login() {
		if (!$this->has_woocommerce())
			return false;
		$nonceValue = '';
		foreach (array('woocommerce-login-nonce', '_wpnonce') as $key) {
			if (array_key_exists($key, $_REQUEST)) {
				$nonceValue = $_REQUEST[$key];
				break;
			}
		}

		return ( isset( $_POST['login'], $_POST['username'], $_POST['password'] ) && is_string($nonceValue) && wp_verify_nonce( $nonceValue, 'woocommerce-login' ) );
	}

	public function _record_application_password_check($error, $user, $item, $password) {
		// Core reaches this hook only after availability checks pass and the supplied application password matches.
		if ($this->_wp_error_has_errors($error)) {
			return;
		}
		if (!($user instanceof \WP_User) || !$user->exists() || !is_string($password)) {
			return;
		}

		$hash = $this->_application_password_authentication_hash($user->ID, $password);
		if ($hash !== '') {
			self::$_pendingApplicationPasswordAuthentication[(int) $user->ID] = array(
				'hash' => $hash,
				'item' => $this->_application_password_item_identifier($item),
			);
		}
	}

	public function _record_application_password_authentication($user, $item = null) {
		if (!($user instanceof \WP_User) || !$user->exists()) {
			return;
		}

		$userID = (int) $user->ID;
		if (!isset(self::$_pendingApplicationPasswordAuthentication[$userID])) {
			return;
		}

		$pending = self::$_pendingApplicationPasswordAuthentication[$userID];
		$itemIdentifier = $this->_application_password_item_identifier($item);
		if ($pending['item'] !== '' && $itemIdentifier !== '' && $pending['item'] !== $itemIdentifier) {
			return;
		}

		self::$_applicationPasswordAuthentication[$userID] = $pending['hash'];
	}

	private function _is_application_password_authentication($user, $password) {
		if (!($user instanceof \WP_User) || !$user->exists() || !is_string($password)) {
			return false;
		}

		$userID = (int) $user->ID;
		if (!isset(self::$_applicationPasswordAuthentication[$userID])) {
			return false;
		}

		$hash = $this->_application_password_authentication_hash($userID, $password);
		return $hash !== '' && hash_equals(self::$_applicationPasswordAuthentication[$userID], $hash);
	}

	private function _application_password_authentication_hash($userID, $password) {
		if (!is_string($password)) {
			return '';
		}

		$password = preg_replace('/[^a-z\d]/i', '', $password);
		if (!is_string($password) || $password === '') {
			return '';
		}

		$secret = Model_Crypto::shared_hash_secret();
		if (!is_string($secret)) {
			$secret = (string) $secret;
		}
		return hash_hmac('sha256', (int) $userID . "\0" . $password, $secret);
	}

	private function _application_password_item_identifier($item) {
		if (is_array($item) && isset($item['uuid']) && is_scalar($item['uuid'])) {
			return (string) $item['uuid'];
		}
		return '';
	}

	private function _wp_error_has_errors($error) {
		if (!is_wp_error($error)) {
			return false;
		}
		if (method_exists($error, 'has_errors')) {
			return $error->has_errors();
		}
		if (isset($error->errors) && is_array($error->errors)) {
			return !empty($error->errors);
		}
		return false;
	}

	/**
	 * Returns an authentication error for username/password logins blocked by passkey requirements.
	 *
	 * @param string $code Error code.
	 * @param \WP_User|null $user User whose username/password authentication was blocked.
	 * @return \WP_Error
	 */
	private function _passkey_required_password_auth_disabled_error($code, $user = null) {
		if ($user instanceof \WP_User) {
			do_action('wordfence_ls_passkey_password_auth_blocked', $user, array(
				'code' => $code,
				'user_id' => (int) $user->ID,
				'username' => $user->user_login,
				'ip' => Model_Request::current()->ip(),
				'xmlrpc' => defined('XMLRPC_REQUEST') && XMLRPC_REQUEST,
			));
		}

		return new \WP_Error($code, wp_kses(sprintf(
			/* translators: Support URL. */
			__('<strong>PASSKEY REQUIRED</strong>: A passkey is required for authentication on this account. <a href="%s" target="_blank" rel="noopener noreferrer">Learn More</a>', 'wordfence'),
			Controller_Support::esc_supportURL(Controller_Support::ITEM_MODULE_LOGIN_SECURITY_PASSKEY_REQUIRED)
		), array(
			'strong' => array(),
			'a' => array(
				'href' => array(),
				'target' => array(),
				'rel' => array(),
			),
		)));
	}

	/**
	 * Returns an authentication error when passkey policy blocks password authentication for a user.
	 *
	 * @param \WP_User $user User being authenticated.
	 * @return \WP_Error|null
	 */
	private function _passkey_password_auth_policy_error($user) {
		if (Controller_Users::shared()->requires_passkey($user, $passkeyInGracePeriod, $passkeyRequiredAt)) {
			return $this->_passkey_required_password_auth_disabled_error('wfls_passkey_role_password_auth_disabled', $user);
		}
		if (Controller_Passkey::shared()->should_block_username_password_auth($user)) {
			return $this->_passkey_required_password_auth_disabled_error('wfls_passkey_user_password_auth_disabled', $user);
		}
		return null;
	}

	/**
	 * Runs WordPress authentication within a scoped WFLS authentication context.
	 *
	 * @param string $username Username to authenticate.
	 * @param string $password Password to authenticate.
	 * @param bool $isAuthenticationPreflight Whether the call is an AJAX authentication preflight.
	 * @param bool $isCombinedCheck Whether the call is checking the base password from combined credentials.
	 * @return array The authentication result and whether combined 2FA validated in this context.
	 */
	private function _authenticate_with_context($username, $password, $isAuthenticationPreflight, $isCombinedCheck) {
		$this->authentication_context_stack[] = array(
			'username' => sanitize_user($username),
			'password' => trim($password),
			'claimed' => false,
			'is_authentication_preflight' => (bool) $isAuthenticationPreflight,
			'is_combined_check' => (bool) $isCombinedCheck,
			'combined_2fa_valid' => false,
		);
		$contextIndex = count($this->authentication_context_stack) - 1;

		try {
			$user = wp_authenticate($username, $password);
			return array(
				'user' => $user,
				'combined_2fa_valid' => $this->authentication_context_stack[$contextIndex]['combined_2fa_valid'],
			);
		}
		finally {
			array_pop($this->authentication_context_stack);
		}
	}

	/**
	 * Checks credentials for the AJAX login preflight without weakening later authentication calls.
	 *
	 * @param string $username Username to authenticate.
	 * @param string $password Password to authenticate.
	 * @return array The authentication result and whether combined 2FA validated during the preflight.
	 */
	public function authenticate_preflight($username, $password) {
		return $this->_authenticate_with_context($username, $password, true, false);
	}

	public function _authenticate($user, $username, $password) {
		if (Controller_Whitelist::shared()->is_whitelisted(Model_Request::current()->ip())) { //Whitelisted, so we're not enforcing 2FA/passkey
			return $user;
		}
		if (Controller_Passkey::shared()->is_verified_authentication_request($username, $password)) {
			return $user;
		}
		// The marker is set only after Core accepts this same user/password as an application password.
		if ($this->_is_application_password_authentication($user, $password)) {
			return $user;
		}

		if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
			if ($user && $user instanceof \WP_User && Controller_Users::shared()->requires_passkey($user, $inGracePeriod, $timeRequired)) {
				return $this->_passkey_required_password_auth_disabled_error('wfls_xmlrpc_passkey_role_password_auth_disabled', $user);
			}
			else if ($user && $user instanceof \WP_User && Controller_Passkey::shared()->should_block_username_password_auth($user)) {
				return $this->_passkey_required_password_auth_disabled_error('wfls_xmlrpc_passkey_user_password_auth_disabled', $user);
			}
			else if (!Controller_Settings::shared()->is_2fa_enabled() || !Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_XMLRPC_ENABLED)) { //XML-RPC call and we're not enforcing 2FA on it
				return $user;
			}
		}

		$contextIndex = count($this->authentication_context_stack) - 1;
		$authenticationContext = null;
		if ($contextIndex >= 0) {
			$candidateContext = $this->authentication_context_stack[$contextIndex];
			if (!$candidateContext['claimed'] && $candidateContext['username'] === $username && $candidateContext['password'] === $password) {
				$this->authentication_context_stack[$contextIndex]['claimed'] = true;
				$authenticationContext = $candidateContext;
			}
		}
		$isLogin = $authenticationContext === null;
		$isAuthenticationPreflight = $authenticationContext !== null && $authenticationContext['is_authentication_preflight'];
		$isCombinedCheck = $authenticationContext !== null && $authenticationContext['is_combined_check'];
		$enforcePasswordAuthPolicy = $isLogin || ($isAuthenticationPreflight && !$isCombinedCheck);
		$combinedTwoFactor = false;
		$combinedTwoFactorUpdate = null;
		$passkeyPasswordAuthUser = null;

		/*
		 * If we don't have a valid $user at this point, it means the $username/$password combo is invalid. We'll check
		 * to see if the user has provided a combined password in the format `<password><code>`, populating $user from
		 * that if so.
		 */
		if (Controller_Settings::shared()->is_2fa_enabled() && !$isCombinedCheck && (!isset($_POST['wfls-token']) || !is_string($_POST['wfls-token'])) && (!is_object($user) || !($user instanceof \WP_User))) {
			//Compatibility with WF legacy 2FA
			$combinedTOTPRegex = '/((?:[0-9]{3}\s*){2})$/i';
			$combinedRecoveryRegex = '/((?:[a-f0-9]{4}\s*){4})$/i';
			if ($this->legacy_2fa_active()) {
				$combinedTOTPRegex = '/(?<! wf)((?:[0-9]{3}\s*){2})$/i';
				$combinedRecoveryRegex = '/(?<! wf)((?:[a-f0-9]{4}\s*){4})$/i';
			}

			if (preg_match($combinedTOTPRegex, $password, $matches)) { //Possible TOTP code
				if (strlen($password) > strlen($matches[1])) {
					$revisedPassword = substr($password, 0, strlen($password) - strlen($matches[1]));
					$code = $matches[1];
				}
			}
			else if (preg_match($combinedRecoveryRegex, $password, $matches)) { //Possible recovery code
				if (strlen($password) > strlen($matches[1])) {
					$revisedPassword = substr($password, 0, strlen($password) - strlen($matches[1]));
					$code = $matches[1];
				}
			}

			if (isset($revisedPassword)) {
				$combinedCheck = $this->_authenticate_with_context($username, $revisedPassword, $isAuthenticationPreflight, true);
				$revisedUser = $combinedCheck['user'];
				if (is_object($revisedUser) && ($revisedUser instanceof \WP_User)) {
					$passkeyPasswordAuthUser = $revisedUser;
					if (Controller_TOTP::shared()->validate_2fa($revisedUser, $code, false, $combinedTwoFactorUpdate)) {
						if ($authenticationContext !== null) {
							$this->authentication_context_stack[$contextIndex]['combined_2fa_valid'] = true;
						}
						$user = $revisedUser;
						$combinedTwoFactor = true;
					}
				}
			}
		}
		
		/*
		 * CAPTCHA Check
		 * 
		 * It will be enforced so long as:
		 * 
		 * 1. It's enabled and keys are set.
		 * 2. This is not an XML-RPC request. An XML-RPC request is de facto an automated request, so a CAPTCHA makes
		 *    no sense.
		 * 3. A filter does not override it. This is to allow plugins with REST endpoints that handle authentication
		 *    themselves to opt out of the requirement.
		 * 4. The user is not providing a combined credentials + 2FA authentication login request.
		 * 5. The request is not a WooCommerce login while WC integration is disabled
		 */
		if (!$combinedTwoFactor && !$isCombinedCheck && !empty($username) && (!$this->_is_woocommerce_login() || Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION))) { //Login attempt, not just a wp-login.php page load

			$requireCAPTCHA = Controller_CAPTCHA::shared()->is_captcha_required();
			$performVerification = false;
			
			$token = Controller_CAPTCHA::shared()->get_token();
			if ($requireCAPTCHA && empty($token) && !Controller_CAPTCHA::shared()->test_mode()) { //No CAPTCHA token means forced additional verification (if neither 2FA nor test mode are active)
				$performVerification = true;
			}
			
			if (is_object($user) && $user instanceof \WP_User && $this->validate_email_verification_token($user)) { //Skip the CAPTCHA check if the email address was verified
				$requireCAPTCHA = false;
				$performVerification = false;
				
				//Reset token rate limit
				$identifier = sprintf('wfls-captcha-%d', $user->ID);
				$tokenBucket = new Model_TokenBucket('rate:' . $identifier, 3, 1 / (WORDFENCE_LS_EMAIL_VALIDITY_DURATION_MINUTES * Model_TokenBucket::MINUTE)); //Maximum of three requests, refilling at a rate of one per token expiration period
				$tokenBucket->reset();
			}
			
			$score = false;
			if ($requireCAPTCHA && !$performVerification) {
				$expired = false;
				if (is_object($user) && $user instanceof \WP_User) {
					$score = Controller_Users::shared()->cached_captcha_score($token, $user, $expired);
				}
				
				if ($score === false) {
					if ($expired) {
						return new \WP_Error('wfls_captcha_expired', wp_kses(__('<strong>CAPTCHA EXPIRED</strong>: The CAPTCHA verification for this login attempt has expired. Please try again.', 'wordfence'), array('strong'=>array())));
					}
					
					$score = Controller_CAPTCHA::shared()->score($token);
					
					if ($score !== false && is_object($user) && $user instanceof \WP_User) {
						Controller_Users::shared()->cache_captcha_score($token, $score, $user);
						Controller_Users::shared()->record_captcha_score($user, $score);
					}
				}
				
				if ($score === false && !Controller_CAPTCHA::shared()->test_mode()) { //An invalid token will require additional verification (if test mode is not active)
					$performVerification = true;
				}
			}
			
			if ($requireCAPTCHA) {
				if ($performVerification || !Controller_CAPTCHA::shared()->is_human($score)) {
					if (is_object($user) && $user instanceof \WP_User) {
						$identifier = sprintf('wfls-captcha-%d', $user->ID);
						$tokenBucket = new Model_TokenBucket('rate:' . $identifier, 3, 1 / (WORDFENCE_LS_EMAIL_VALIDITY_DURATION_MINUTES * Model_TokenBucket::MINUTE)); //Maximum of three requests, refilling at a rate of one per token expiration period
						if ($tokenBucket->consume(1)) {
							if ($this->has_woocommerce() && array_key_exists('woocommerce-login-nonce', $_POST)) {
								$loginUrl = get_permalink(get_option('woocommerce_myaccount_page_id'));
							}
							else {
								$loginUrl = wp_login_url();
							}
							$verificationUrl = add_query_arg(
								array(
									'wfls-email-verification' => rawurlencode(Controller_Users::shared()->generate_verification_token($user))
								),
								$loginUrl
							);
							$view = new Model_View('email/login-verification', array(
								'siteName' => get_bloginfo('name', 'raw'),
								'verificationURL' => $verificationUrl,
								'ip' => Model_Request::current()->ip(),
								'canEnable2FA' => Controller_Users::shared()->can_activate_2fa($user),
							));
							wp_mail($user->user_email, __('Login Verification Required', 'wordfence'), $view->render(), "Content-Type: text/html");
						}
					}

					Utility_Sleep::sleep(Model_Crypto::random_int(0, 2000) / 1000);
					return new \WP_Error('wfls_captcha_verify', wp_kses(__('<strong>VERIFICATION REQUIRED</strong>: Additional verification is required for login. If there is a valid account for the provided login credentials, please check the email address associated with it for a verification link to continue logging in.', 'wordfence'), array('strong' => array())));
				}
			}
		}

		if ($enforcePasswordAuthPolicy) {
			$passwordAuthUser = $passkeyPasswordAuthUser instanceof \WP_User ? $passkeyPasswordAuthUser : $user;
			$passkeyPasswordAuthError = $passwordAuthUser instanceof \WP_User ? $this->_passkey_password_auth_policy_error($passwordAuthUser) : null;
			if ($passkeyPasswordAuthError !== null) {
				return $passkeyPasswordAuthError;
			}
		}

		if ($combinedTwoFactor && $isLogin && !Controller_TOTP::shared()->commit_deferred_2fa_validation($combinedTwoFactorUpdate)) {
			return new \WP_Error('wfls_twofactor_failed', wp_kses(__('<strong>CODE INVALID</strong>: The 2FA code provided is either expired or invalid. Please try again.', 'wordfence'), array('strong'=>array())));
		}

		if (!$combinedTwoFactor) {
			if ($isLogin && $user instanceof \WP_User) {
				$inGracePeriod = false;
				$timeRequired = null;
				$requiresAdditionalAuth = Controller_Users::shared()->requires_additional_auth($user, $inGracePeriod, $timeRequired);

				if (Controller_Users::shared()->has_2fa_active($user)) {
					if (Controller_Users::shared()->has_remembered_2fa($user)) {
						if ($inGracePeriod) { $this->_add_additional_auth_notice($user, $timeRequired); }
						return $user;
					}
					elseif (array_key_exists('wfls-token', $_POST)) {
						if (is_string($_POST['wfls-token']) && Controller_TOTP::shared()->validate_2fa($user, $_POST['wfls-token'])) {
							if ($inGracePeriod) { $this->_add_additional_auth_notice($user, $timeRequired); }
							return $user;
						}
						else {
							return new \WP_Error('wfls_twofactor_failed', wp_kses(__('<strong>CODE INVALID</strong>: The 2FA code provided is either expired or invalid. Please try again.', 'wordfence'), array('strong'=>array())));
						}
					}
				}

				if (Controller_Users::shared()->has_2fa_active($user)) {
					$legacy2FAActive = Controller_WordfenceLS::shared()->legacy_2fa_active();
					if ($legacy2FAActive) {
						return new \WP_Error('wfls_twofactor_required', wp_kses(__('<strong>CODE REQUIRED</strong>: Please enter your 2FA code immediately after your password in the same field.', 'wordfence'), array('strong'=>array())));
					}
					return new \WP_Error('wfls_twofactor_required', wp_kses(__('<strong>CODE REQUIRED</strong>: Please provide your 2FA code when prompted.', 'wordfence'), array('strong'=>array())));
				}
				//else if (Controller_Users::shared()->has_passkey_active($user)) { } //NOTE: Passkeys do not flow through this hook
				else if ($requiresAdditionalAuth) {
					return new \WP_Error('wfls_additionalauth_blocked', wp_kses(__('<strong>LOGIN BLOCKED</strong>: Additional authentication is required to be active on your account. Please contact the site administrator.', 'wordfence'), array('strong'=>array())));
				}
				else if ($inGracePeriod) {
					$this->_add_additional_auth_notice($user, $timeRequired);
				}
			}
		}

		return $user;
	}

	private function _add_additional_auth_notice($user, $timeRequired) {
		$pendingMethods = Controller_Users::shared()->required_auth_methods_in_grace_period($user);
		$configureURL = esc_url((is_multisite() && is_super_admin($user->ID)) ? network_admin_url('admin.php?page=WFLS') : admin_url('admin.php?page=WFLS'));
		if ($pendingMethods === array(Controller_Users::AUTH_METHOD_2FA)) {
			$message = sprintf(
				/* translators: 1. Date; 2. Configuration URL */
				__('You do not currently have two-factor authentication active on your account, which will be required beginning %1$s. <a href="%2$s">Configure 2FA</a>', 'wordfence'),
				Controller_Time::format_local_time('F j, Y g:i A', $timeRequired),
				$configureURL
			);
		}
		else if ($pendingMethods === array(Controller_Users::AUTH_METHOD_PASSKEY)) {
			$message = sprintf(
				/* translators: 1. Date; 2. Configuration URL */
				__('You do not currently have a passkey active on your account, which will be required beginning %1$s. <a href="%2$s">Add a Passkey</a>', 'wordfence'),
				Controller_Time::format_local_time('F j, Y g:i A', $timeRequired),
				$configureURL
			);
		}
		else {
			$message = sprintf(
				/* translators: 1. Date; 2. Configuration URL */
				__('You do not currently have additional authentication active on your account, which will be required beginning %1$s. <a href="%2$s">Configure Authentication</a>', 'wordfence'),
				Controller_Time::format_local_time('F j, Y g:i A', $timeRequired),
				$configureURL
			);
		}
		Controller_Notices::shared()->add_notice(Model_Notice::SEVERITY_CRITICAL, new Model_HTML(wp_kses($message, array('a'=>array('href'=>array())))), 'wfls-will-be-required', $user);
	}
	
	public function _set_logged_in_cookie($logged_in_cookie, $expire, $expiration, $user_id) {
		$user = new \WP_User($user_id);
		if (Controller_Users::shared()->has_2fa_active($user) && isset($_POST['wfls-remember-device']) && $_POST['wfls-remember-device']) {
			Controller_Users::shared()->remember_2fa($user);
		}
		delete_user_meta($user_id, 'wfls-captcha-nonce');
	}
	
	public function _record_login($user_login/*, $user -- we'd like to use the second parameter instead, but too many plugins call this hook and only provide one of the two required parameters*/) {
		$user = get_user_by('login', $user_login);
		if (is_object($user) && $user instanceof \WP_User && $user->exists()) {
			update_user_meta($user->ID, 'wfls-last-login', Controller_Time::time());
		}
	}
	
	public function _register_post($sanitized_user_login, $user_email, $errors) {
		if (!empty($sanitized_user_login)) {
			$captchaResult = $this->process_registration_captcha_with_hooks();
			if ($captchaResult !== true) {
				$errors->add($captchaResult['category'], $captchaResult['message']);
			}
		}
	}

	private function validate_email_verification_token($user = null, &$token = null) {
		$token = isset($_REQUEST['wfls-email-verification']) ? $_REQUEST['wfls-email-verification'] : null;
		if (empty($token))
			return null;
		return is_string($token) && Controller_Users::shared()->validate_verification_token($token, $user);
	}

	/**
	 * @param \WP_Error $errors
	 * @param string $redirect_to
	 * @return \WP_Error
	 */
	public function _wp_login_errors($errors, $redirect_to) {
		$has_errors = (method_exists($errors, 'has_errors') ? $errors->has_errors() : !empty($errors->errors)); //has_errors was added in WP 5.1
		$emailVerificationTokenValid = $this->validate_email_verification_token();
		if (!$has_errors && $emailVerificationTokenValid !== null) {
			if ($emailVerificationTokenValid) {
				$errors->add('wfls_email_verified', esc_html__('Email verification succeeded. Please continue logging in.', 'wordfence'), 'message');
			}
			else {
				$errors->add('wfls_email_not_verified', esc_html__('Email verification invalid or expired. Please try again.', 'wordfence'), 'message');
			}
		}
		return $errors;
	}
	
	public function legacy_2fa_active() {
		$wfLegacy2FAActive = false;
		if (class_exists('wfConfig') && \wfConfig::get('isPaid')) {
			$twoFactorUsers = \wfConfig::get_ser('twoFactorUsers', array());
			if (is_array($twoFactorUsers) && count($twoFactorUsers) > 0) {
				foreach ($twoFactorUsers as $t) {
					if ($t[3] == 'activated') {
						$testUser = get_user_by('ID', $t[0]);
						if (is_object($testUser) && $testUser instanceof \WP_User && \wfUtils::isAdmin($testUser)) {
							$wfLegacy2FAActive = true;
							break;
						}
					}
				}
			}
			
			if ($wfLegacy2FAActive && class_exists('wfCredentialsController') && method_exists('wfCredentialsController', 'useLegacy2FA') && !\wfCredentialsController::useLegacy2FA()) {
				$wfLegacy2FAActive = false;
			}
		}
		return $wfLegacy2FAActive;
	}
	
	/**
	 * Menu
	 */
	
	public function _admin_menu() {
		$user = wp_get_current_user();
		if (!Controller_Permissions::shared()->can_manage_settings($user)
			&& !Controller_Settings::shared()->should_always_show_login_security_menu()
			&& (!Controller_Users::shared()->can_activate_2fa($user) || $this->is_2fa_management_hidden_by_passkey_only($user))
			&& !Controller_Users::shared()->can_manage_passkey($user)) {
			return;
		}
		if (Controller_Notices::shared()->has_notice($user)) {
			Controller_Users::shared()->requires_additional_auth($user, $gracePeriod);
			if (!$gracePeriod) {
				Controller_Notices::shared()->remove_notice(false, 'wfls-will-be-required', $user);
			}
		}
		
		Controller_Notices::shared()->enqueue_notices();
		
		$useSubmenu = WORDFENCE_LS_FROM_CORE && current_user_can('activate_plugins');
		if (is_multisite() && !is_network_admin()) {
			$useSubmenu = false;
			
			if (is_super_admin()) {
				return;
			}
		}
		
		if ($useSubmenu) {
			add_submenu_page('Wordfence', __('Login Security', 'wordfence'), __('Login Security', 'wordfence'), Controller_Permissions::CAP_SHOW_LOGIN_SECURITY, 'WFLS', array($this, '_menu'));
		}
		else {
			add_menu_page(__('Login Security', 'wordfence'), __('Login Security', 'wordfence'), Controller_Permissions::CAP_SHOW_LOGIN_SECURITY, 'WFLS', array($this, '_menu'), Model_Asset::img('menu.svg'));
		}
	}
	
	public function _menu() {
		$viewer = wp_get_current_user();
		$user = $viewer;
		$administrator = false;
		if (Controller_Permissions::shared()->can_manage_settings($viewer)) {
			$administrator = true;
		}
		
		$canEditOtherUsers2FA = user_can($viewer, Controller_Permissions::CAP_ACTIVATE_2FA_OTHERS);
		$canEditOtherUsersPasskeys = user_can($viewer, Controller_Permissions::CAP_MANAGE_PASSKEY_OTHERS);
		$requestedTargetUserMissing = false;
		if (isset($_GET['user'])) {
			$requestedUser = new \WP_User((int) $_GET['user']);
			if ($requestedUser->exists()) {
				$user = $requestedUser;
			}
			else {
				$requestedTargetUserMissing = true;
			}
		}
		$viewingOtherUser = $user instanceof \WP_User && $viewer instanceof \WP_User && $user->exists() && $viewer->exists() && (int) $user->ID !== (int) $viewer->ID;
		$targetUserRequestDenied = $requestedTargetUserMissing || ($viewingOtherUser && !$canEditOtherUsers2FA && !$canEditOtherUsersPasskeys);
		$targetUser2FAPermissionDenied = $viewingOtherUser && !$canEditOtherUsers2FA;
		$targetUserPasskeyPermissionDenied = $viewingOtherUser && !$canEditOtherUsersPasskeys;

		$sections = array();
		$featured = false;

		if ($targetUserRequestDenied) {
			$sections[] = array(
				'tab' => new Model_Tab('manage', 'manage', __('Login Security', 'wordfence'), __('Login Security', 'wordfence')),
				'title' => new Model_Title('manage', __('Login Security', 'wordfence')),
				'content' => new Model_View('page/feature-disabled', array(
					'title' => __('Permission Denied', 'wordfence'),
					'message' => __('You are not allowed to view or edit that user.', 'wordfence'),
					'showSettingsButton' => false,
				)),
			);
		}
		else if (isset($_GET['role']) && $canEditOtherUsers2FA) {
			$roleKey = $_GET['role'];
			$roles = new \WP_Roles();
			$role = $roles->get_role($roleKey);
			$roleTitle = $roleKey === 'super-admin' ? __('Super Administrator', 'wordfence') : $roles->role_names[$roleKey];
			$requiredAt = Controller_Settings::shared()->get_required_2fa_role_activation_time($roleKey);
			$states = array(
				'grace_period' => array(
					'title' => __('Grace Period', 'wordfence'),
					'gracePeriod' => true
				),
				'locked_out' => array(
					'title' => __('Locked Out', 'wordfence'),
					'gracePeriod' => false
				)
			);
			foreach ($states as $key => $state) {
				$pageKey = "page_$key";
				$page = isset($_GET[$pageKey]) ? max((int) $_GET[$pageKey], 1) : 1;
				$title = $state['title'];
				$lastPage = true;
				if ($requiredAt === false) {
					$users = array();
				}
				else {
					$users = Controller_Users::shared()->get_inactive_2fa_users($roleKey, $state['gracePeriod'], $page, self::USERS_PER_PAGE, $lastPage);
				}
				$sections[] = array(
					'tab' => new Model_Tab($key, $key, $title, $title),
					'title' => new Model_Title($key, sprintf(/* translators: User count */ __('Users without 2FA active (%s)', 'wordfence'), $title) . ' - ' . $roleTitle),
					'content' => new Model_View('page/role', array(
						'role' => $role,
						'roleTitle' => $roleTitle,
						'stateTitle' => $title,
						'requiredAt' => $requiredAt,
						'state' => $state,
						'users' => $users,
						'page' => $page,
						'lastPage' => $lastPage,
						'pageKey' => $pageKey,
						'stateKey' => $key
					)),
				);
			}
		}
		else if (isset($_GET['role'])) {
			$sections[] = array(
				'tab' => new Model_Tab('manage', 'manage', __('2FA', 'wordfence'), __('Two-Factor Authentication', 'wordfence'), false, __('2FA', 'wordfence')),
				'title' => new Model_Title('manage', __('Two-Factor Authentication', 'wordfence'), Controller_Support::supportURL(Controller_Support::ITEM_MODULE_LOGIN_SECURITY_2FA), new Model_HTML(wp_kses(__('Learn more<span class="wfls-hidden-xs"> about Two-Factor Authentication</span>', 'wordfence'), array('span'=>array('class'=>array()))))),
				'content' => new Model_View('page/feature-disabled', array(
					'title' => __('Permission Denied', 'wordfence'),
					'message' => __('You are not allowed to view or edit users for this role.', 'wordfence'),
					'showSettingsButton' => false,
				)),
			);
		}
		else {
			$featured = true;
			$settingsBaseURL = is_multisite() ? network_admin_url('admin.php?page=WFLS') : admin_url('admin.php?page=WFLS');
			$settingsURL = $settingsBaseURL . '#top#settings';
			$enablePasskeysURL = add_query_arg('wfls-settings-anchor', 'enable-passkeys', $settingsBaseURL) . '#top#settings';
			$showAdminAuthTabs = $administrator;
			$showDisabledSelfAuthTabs = !$viewingOtherUser && Controller_Settings::shared()->should_always_show_login_security_menu() && user_can($user, Controller_Permissions::CAP_SHOW_LOGIN_SECURITY);
			$canManageUserPasskey = !$targetUserPasskeyPermissionDenied && $this->can_manage_user_passkey($user);
			$passkeysEnabledForUser = $targetUserPasskeyPermissionDenied ? true : Controller_Users::shared()->can_manage_passkey($user);
			$showDisabledPasskeyTab = !$passkeysEnabledForUser && ($showDisabledSelfAuthTabs || ($viewingOtherUser && !$targetUserPasskeyPermissionDenied));
			$passkeyAccountContext = null;
			$twoFactorAccountContext = null;
			if ($canEditOtherUsersPasskeys || $canEditOtherUsers2FA) {
				$accountContextHTML = get_avatar($user->ID, 24, '', $user->user_login) . '<span><strong>' . esc_html__('User', 'wordfence') . '</strong>: <code>' . esc_html($user->user_login . (!$viewingOtherUser ? ' ' . __('(you)', 'wordfence') : '')) . '</code></span>';
				$userEditURL = get_edit_user_link($user->ID);
				if ($userEditURL) {
					$accountContextHTML = '<a class="wfls-section-title-context-link" href="' . esc_url($userEditURL) . '">' . $accountContextHTML . '</a>';
				}
				$accountContext = new Model_HTML($accountContextHTML);
				$passkeyAccountContext = $canEditOtherUsersPasskeys ? $accountContext : null;
				$twoFactorAccountContext = $canEditOtherUsers2FA ? $accountContext : null;
			}
			if ($canManageUserPasskey || $targetUserPasskeyPermissionDenied || $showAdminAuthTabs || $showDisabledPasskeyTab) {
				$passkeyTabTitle = $viewingOtherUser ? __('Passkeys', 'wordfence') : __('My Passkeys', 'wordfence');
				$canRegisterUserPasskey = $passkeysEnabledForUser && $this->can_register_user_passkey($user);
				$sections[] = array(
					'tab' => new Model_Tab('passkey', 'passkey', $passkeyTabTitle, $passkeyTabTitle, false, null, array('icon' => 'passkey')),
					'title' => new Model_Title('passkey', $passkeyTabTitle, Controller_Support::supportURL(Controller_Support::ITEM_MODULE_LOGIN_SECURITY_PASSKEYS), new Model_HTML(wp_kses(__('Learn more<span class="wfls-hidden-xs"> about passkeys</span>', 'wordfence'), array('span'=>array('class'=>array())))), array(
						'subtitle' => __('Passkeys let you sign in securely using your fingerprint, face, device PIN, or password.', 'wordfence'),
						'icon' => 'passkey',
						'context' => $passkeyAccountContext,
						'featured' => true,
					)),
					'content' => new Model_View('page/passkey', array(
						'user' => $user,
						'canRegisterPasskeys' => $canRegisterUserPasskey,
						'passkeysEnabledForUser' => $passkeysEnabledForUser,
						'targetUserPermissionDenied' => $targetUserPasskeyPermissionDenied,
						'settingsURL' => $settingsURL,
						'enablePasskeysURL' => $enablePasskeysURL,
						'showSettingsButton' => $administrator,
						'initialAllowedHostnames' => $canRegisterUserPasskey ? $this->initial_allowed_passkey_hostnames_for_registration($user) : array(),
					)),
				);
			}

			$hide2FAForPasskeyOnly = $this->is_2fa_management_hidden_by_passkey_only($user);
			$canManageUser2FA = !$targetUser2FAPermissionDenied && $this->can_manage_user_2fa($user);
			$twoFactorEnabledForUser = $targetUser2FAPermissionDenied ? true : Controller_Users::shared()->can_activate_2fa($user);
			$showDisabled2FATab = !$twoFactorEnabledForUser && ($showDisabledSelfAuthTabs || ($viewingOtherUser && !$targetUser2FAPermissionDenied));
			if (!$hide2FAForPasskeyOnly && ($canManageUser2FA || $targetUser2FAPermissionDenied || $showAdminAuthTabs || $showDisabled2FATab)) {
				$twoFactorTabTitle = $viewingOtherUser ? __('2FA', 'wordfence') : __('My 2FA', 'wordfence');
				$twoFactorPageTitle = $viewingOtherUser ? __('Two-Factor Authentication', 'wordfence') : __('My 2FA', 'wordfence');
				$sections[] = array(
					'tab' => new Model_Tab('manage', 'manage', $twoFactorTabTitle, $twoFactorPageTitle, false, $twoFactorTabTitle, array('icon' => '2fa')),
					'title' => new Model_Title('manage', $twoFactorPageTitle, Controller_Support::supportURL(Controller_Support::ITEM_MODULE_LOGIN_SECURITY_2FA), new Model_HTML(wp_kses(__('Learn more<span class="wfls-hidden-xs"> about Two-Factor Authentication</span>', 'wordfence'), array('span'=>array('class'=>array())))), array(
						'subtitle' => __('Protect password sign-in with two-factor authentication.', 'wordfence'),
						'description' => new Model_HTML(wp_kses(sprintf(/* translators: Support URL */ __('Works with Google Authenticator, FreeOTP, Authy, and other TOTP-compatible apps. <a href="%s" target="_blank" rel="noopener noreferrer">View tested apps</a>', 'wordfence'), Controller_Support::esc_supportURL(Controller_Support::ITEM_MODULE_LOGIN_SECURITY_2FA)), array('a'=>array('href'=>array(), 'target'=>array(), 'rel'=>array())))),
						'icon' => '2fa',
						'context' => $twoFactorAccountContext,
						'featured' => true,
					)),
					'content' => new Model_View('page/manage', array(
						'user' => $user,
						'twoFactorEnabledForUser' => $twoFactorEnabledForUser,
						'targetUserPermissionDenied' => $targetUser2FAPermissionDenied,
						'settingsURL' => $settingsURL,
						'showSettingsButton' => $administrator,
					)),
				);
			}
			
			if ($administrator) {
				$settingsActions = new Model_HTML('<div class="wfls-section-title-settings-actions wordfence-vue-wrapper" data-base-component="WFLSSettingsButtons"></div>');
				$sections[] = array(
					'tab' => new Model_Tab('settings', 'settings', __('Settings', 'wordfence'), __('Settings', 'wordfence'), false, null, array('icon' => 'settings')),
					'title' => new Model_Title('settings', __('Login Security Settings', 'wordfence'), Controller_Support::supportURL(Controller_Support::ITEM_MODULE_LOGIN_SECURITY), new Model_HTML(wp_kses(__('Learn more<span class="wfls-hidden-xs"> about Login Security</span>', 'wordfence'), array('span'=>array('class'=>array())))), array(
						'subtitle' => __('Control how users sign in and which authentication methods are required.', 'wordfence'),
						'icon' => 'settings',
						'action' => $settingsActions,
						'helpBelowSubtitle' => true,
						'featured' => true,
					)),
					'content' => new Model_View('page/settings', array(
						'hasWoocommerce' => $this->has_woocommerce()
					)),
				);
			}
		}
		
		$view = new Model_View('page/page', array(
			'sections' => $sections,
			'featured' => $featured,
		));
		echo $view->render();
	}

	private function process_registration_captcha() {
		if (Controller_Whitelist::shared()->is_whitelisted(Model_Request::current()->ip())) { //Whitelisted, so we're not enforcing 2FA
			return true;
		}

		$captchaController = Controller_CAPTCHA::shared();
		$requireCaptcha = $captchaController->is_captcha_required();
		$token = $captchaController->get_token();

		if ($requireCaptcha) {
			if ($token === null && !$captchaController->test_mode()) {
				return array(
					'message' => wp_kses(__('<strong>REGISTRATION ATTEMPT BLOCKED</strong>: This site requires a security token created when the page loads for all registration attempts. Please ensure JavaScript is enabled and try again.', 'wordfence'), array('strong'=>array())),
					'category' => 'wfls_captcha_required'
				);
			}
			$score = $captchaController->score($token);
			if ($score === false && !$captchaController->test_mode()) {
				return array(
					'message' => wp_kses(__('<strong>REGISTRATION ATTEMPT BLOCKED</strong>: The security token for the login attempt was invalid or expired. Please reload the page and try again.', 'wordfence'), array('strong'=>array())),
					'category' => 'wfls_captcha_required'
				);
			}
			else if (is_numeric($score)) {
				Controller_Users::shared()->record_captcha_score(null, $score);
			}

			if (!$captchaController->is_human($score)) {
				$encryptedIP = Model_Symmetric::encrypt(Model_Request::current()->ip());
				$encryptedScore = Model_Symmetric::encrypt($score);
				$result = array(
					'category' => 'wfls_registration_blocked'
				);
				if ($encryptedIP && $encryptedScore && filter_var(get_site_option('admin_email'), FILTER_VALIDATE_EMAIL)) {
					$jwt = new Model_JWT(array('ip' => $encryptedIP, 'score' => $encryptedScore), Controller_Time::time() + 600);
					$result['message'] = wp_kses(sprintf(/* translators: Security token */ __('<strong>REGISTRATION BLOCKED</strong>: The registration request was blocked because it was flagged as spam. Please try again or <a href="#" class="wfls-registration-captcha-contact" data-token="%s">contact the site owner</a> for help.', 'wordfence'), esc_attr((string)$jwt)), array('strong'=>array(), 'a'=>array('href'=>array(), 'class'=>array(), 'data-token'=>array())));
				}
				else {
					$result['message'] = wp_kses(__('<strong>REGISTRATION BLOCKED</strong>: The registration request was blocked because it was flagged as spam. Please try again or contact the site owner for help.', 'wordfence'), array('strong'=>array()));
				}
				return $result;
			}
		}
		return true;
	}

	/**
	 * @param int $endpointType the type of endpoint being processed
	 *	The default value of 1 corresponds to a regular login
	 *	@see wordfence::wfsnEndpointType()
	 */
	private function process_registration_captcha_with_hooks($endpointType = 1) {
		$result = $this->process_registration_captcha();
		if ($result !== true) {
			if ($result['category'] === 'wfls_registration_blocked') {
				/**
				 * Fires just prior to blocking user registration due to a failed CAPTCHA. After firing this action hook
				 * the registration attempt is blocked.
				 *
				 * @param int $source The source code of the block.
				 */
				do_action('wfls_registration_blocked', $endpointType);

				/**
				 * Filters the message to show if registration is blocked due to a captcha rejection.
				 *
				 * @since 1.0.0
				 *
				 * @param string $message The message to display, HTML allowed.
				 */
				$result['message'] = apply_filters('wfls_registration_blocked_message', $result['message']);
			}
		}
		return $result;
	}

	private function disable_woocommerce_registration($message) {
		if ($this->has_woocommerce()) {
			remove_action('wp_loaded', array('WC_Form_Handler', 'process_registration'), 20);
			wc_add_notice($message, 'error');
		}
	}

	public function _handle_woocommerce_registration() {
		if ($this->has_woocommerce() && isset($_POST['register'], $_POST['email']) && (isset($_POST['_wpnonce']) || isset($_POST['woocommerce-register-nonce']))) {
			$captchaResult = $this->process_registration_captcha_with_hooks();
			if ($captchaResult !== true) {
				$this->disable_woocommerce_registration($captchaResult['message']);
			}
		}
	}

	public function _user_new_form() {
		if (Controller_Settings::shared()->get_user_2fa_grace_period())
			echo Model_View::create('user/grace-period-toggle', array())->render();
	}

	public function _user_register($newUserId) {
		$creator = wp_get_current_user();
		if (!Controller_Permissions::shared()->can_manage_settings($creator) || $creator->ID == $newUserId)
			return;
		if (isset($_POST['wfls-grace-period-toggle']))
			Controller_Users::shared()->allow_grace_period($newUserId); 
	}

	public function _woocommerce_account_menu_items($items) {
		$user = wp_get_current_user();
		$menuItems = array();
		if (Controller_Users::shared()->can_manage_passkey($user)) {
			$menuItems[self::WOOCOMMERCE_PASSKEY_ENDPOINT] = __('Wordfence Passkeys', 'wordfence');
		}
		if (Controller_Users::shared()->can_activate_2fa($user) && !$this->is_2fa_management_hidden_by_passkey_only($user)) {
			$menuItems[self::WOOCOMMERCE_ENDPOINT] = __('Wordfence 2FA', 'wordfence');
		}

		$insertAfter = 'edit-account';
		foreach ($menuItems as $endpointId => $label) {
			if (!Utility_Array::insertAfter($items, $insertAfter, $endpointId, $label)) {
				$items[$endpointId] = $label;
			}
			$insertAfter = $endpointId;
		}
		return $items;
	}

	/**
	 * Returns whether an active role or user-level Passkeys-only requirement supersedes 2FA management for a user.
	 *
	 * @param \WP_User $user The user whose management navigation is being evaluated.
	 * @return bool
	 */
	private function is_2fa_management_hidden_by_passkey_only($user) {
		return Controller_Settings::shared()->are_passkeys_enabled()
			&& !Controller_Passkey::shared()->is_effective_username_password_auth_enabled($user);
	}

	/**
	 * Converts unavailable WooCommerce authentication-management endpoints to a 404 for the current user.
	 *
	 * @return void
	 */
	public function _woocommerce_account_2fa_endpoint_access() {
		if (!function_exists('is_wc_endpoint_url')) {
			return;
		}

		$user = wp_get_current_user();
		if (is_wc_endpoint_url(self::WOOCOMMERCE_PASSKEY_ENDPOINT)) {
			$available = Controller_Users::shared()->can_manage_passkey($user);
		}
		else if (is_wc_endpoint_url(self::WOOCOMMERCE_ENDPOINT)) {
			$available = Controller_Users::shared()->can_activate_2fa($user)
				&& !$this->is_2fa_management_hidden_by_passkey_only($user);
		}
		else {
			return;
		}

		if ($available) {
			return;
		}

		global $wp_query;
		if (is_object($wp_query) && method_exists($wp_query, 'set_404')) {
			$wp_query->set_404();
		}
		status_header(404);
		nocache_headers();
	}

	public function _woocommerce_get_query_vars($query_vars) {
		$query_vars[self::WOOCOMMERCE_ENDPOINT] = self::WOOCOMMERCE_ENDPOINT;
		$query_vars[self::WOOCOMMERCE_PASSKEY_ENDPOINT] = self::WOOCOMMERCE_PASSKEY_ENDPOINT;
		return $query_vars;
	}

	private function can_user_activate_2fa_self($user = null) {
		if ($user === null) {
			$user = wp_get_current_user();
		}
		return user_can($user, Controller_Permissions::CAP_ACTIVATE_2FA_SELF);
	}

	private function should_show_disabled_self_authentication_views($user = null) {
		if ($user === null) {
			$user = wp_get_current_user();
		}
		$viewer = wp_get_current_user();
		return $user instanceof \WP_User
			&& $viewer instanceof \WP_User
			&& $user->exists()
			&& $viewer->exists()
			&& (int) $viewer->ID === (int) $user->ID
			&& Controller_Settings::shared()->should_always_show_login_security_menu();
	}

	private function can_manage_user_2fa($user) {
		$viewer = wp_get_current_user();
		if (!Controller_Users::shared()->can_activate_2fa($user)) {
			return false;
		}

		if ($viewer->ID === $user->ID) {
			return $this->can_user_activate_2fa_self($viewer);
		}

		return current_user_can(Controller_Permissions::CAP_ACTIVATE_2FA_OTHERS);
	}

	protected function render_embedded_user_2fa_management_interface($stacked = null, $permissionDeniedIfUnavailable = true) {
		$user = wp_get_current_user();
		$stacked = $stacked === null ? Controller_Settings::shared()->should_stack_ui_columns() : $stacked;
		$twoFactorEnabledForUser = Controller_Users::shared()->can_activate_2fa($user);
		$viewerCanManageSettings = Controller_Permissions::shared()->can_manage_settings();
		$showDisabledView = !$twoFactorEnabledForUser && ($viewerCanManageSettings || $this->should_show_disabled_self_authentication_views($user));
		if ($this->can_user_activate_2fa_self($user) || $showDisabledView) {
			$assets = $this->management_assets_enqueued ? array() : $this->get_2fa_management_assets(true);
			$scriptData = $this->management_assets_enqueued ? array() : $this->get_2fa_management_script_data();
			return Model_View::create(
				'page/manage-embedded',
				array(
					'user' => $user,
					'stacked' => $stacked,
					'assets' => $assets,
					'scriptData' => $scriptData,
					'twoFactorEnabledForUser' => $twoFactorEnabledForUser,
					'settingsURL' => is_multisite() ? network_admin_url('admin.php?page=WFLS#top#settings') : admin_url('admin.php?page=WFLS#top#settings'),
					'showSettingsButton' => $viewerCanManageSettings,
				)
			)->render();
		}
		return $permissionDeniedIfUnavailable ? Model_View::create('page/permission-denied')->render() : '';
	}

	private function can_user_activate_passkey_self($user = null) {
		if ($user === null) {
			$user = wp_get_current_user();
		}
		return user_can($user, Controller_Permissions::CAP_MANAGE_PASSKEY_SELF);
	}

	private function can_manage_user_passkey($user) {
		$viewer = wp_get_current_user();
		if (!Controller_Users::shared()->can_manage_passkey($user)) {
			return false;
		}

		if ($viewer->ID === $user->ID) {
			return $this->can_user_activate_passkey_self($viewer);
		}

		return current_user_can(Controller_Permissions::CAP_MANAGE_PASSKEY_OTHERS);
	}

	/**
	 * Returns whether the current viewer may register a new passkey for the given user.
	 *
	 * @param \WP_User $user
	 * @return bool
	 */
	private function can_register_user_passkey($user) {
		return Controller_Passkey::shared()->can_register_passkeys(wp_get_current_user(), $user);
	}

	public function render_embedded_user_passkey_management_interface($user = null, $stacked = null, $permissionDeniedIfUnavailable = true) {
		if ($user === null) {
			$user = wp_get_current_user();
		}
		$stacked = $stacked === null ? Controller_Settings::shared()->should_stack_ui_columns() : $stacked;
		$passkeysEnabledForUser = Controller_Users::shared()->can_manage_passkey($user);
		$viewerCanManageSettings = Controller_Permissions::shared()->can_manage_settings();
		$showDisabledView = !$passkeysEnabledForUser && ($viewerCanManageSettings || $this->should_show_disabled_self_authentication_views($user));
		$viewer = wp_get_current_user();
		$userCanManageOwnPasskeys = $viewer instanceof \WP_User && (int) $viewer->ID === (int) $user->ID && $this->can_user_activate_passkey_self($user);
		if ($this->can_manage_user_passkey($user) || $userCanManageOwnPasskeys || $showDisabledView) {
			$assets = $this->management_assets_enqueued ? array() : $this->get_2fa_management_assets(true);
			$scriptData = $this->management_assets_enqueued || !$passkeysEnabledForUser ? array() : $this->get_2fa_management_script_data();
			$passkeys = $passkeysEnabledForUser ? Controller_Passkey::shared()->get_passkeys($user) : array();
			$canRegisterUserPasskey = $passkeysEnabledForUser && $this->can_register_user_passkey($user);
			$initialAllowedHostnames = $canRegisterUserPasskey ? $this->initial_allowed_passkey_hostnames_for_registration($user) : array();
			return Model_View::create(
				'page/passkey-embedded',
				array(
					'user' => $user,
					'stacked' => $stacked,
					'assets' => $assets,
					'scriptData' => $scriptData,
					'passkeys' => $passkeys,
					'canRegisterPasskeys' => $canRegisterUserPasskey,
					'passkeysEnabledForUser' => $passkeysEnabledForUser,
					'settingsURL' => is_multisite() ? network_admin_url('admin.php?page=WFLS#top#settings') : admin_url('admin.php?page=WFLS#top#settings'),
					'showSettingsButton' => $viewerCanManageSettings,
					'initialAllowedHostnames' => $initialAllowedHostnames,
				)
			)->render();
		}

		return $permissionDeniedIfUnavailable ? Model_View::create('page/permission-denied')->render() : '';
	}

	/**
	 * Renders the Passkey management interface for its WooCommerce account endpoint.
	 *
	 * @return void
	 */
	public function _woocommerce_account_passkey_menu_content() {
		echo $this->render_embedded_user_passkey_management_interface();
	}

	public function _woocommerce_account_menu_content() {
		if ($this->is_2fa_management_hidden_by_passkey_only(wp_get_current_user())) {
			return;
		}
		echo $this->render_embedded_user_2fa_management_interface();
	}

	private function does_current_page_include_shortcode($shortcode) {
		global $post;
		return $post instanceof \WP_Post && has_shortcode($post->post_content, $shortcode);
	}

	public function _woocommerce_account_enqueue_assets() {
		if (!$this->has_woocommerce())
			return;
		if ($this->does_current_page_include_shortcode('woocommerce_my_account')) {
			wp_enqueue_style('wordfence-ls-woocommerce-account-styles', Model_Asset::css('woocommerce-account.css'), array(), WORDFENCE_LS_VERSION);
			$this->enqueue_2fa_management_assets(true);
		}
	}

	public function _handle_user_2fa_management_shortcode($attributes, $content = null, $shortcode = null) {
		$shortcode = $shortcode === null ? self::SHORTCODE_2FA_MANAGEMENT : $shortcode;
		$attributes = shortcode_atts(
			array(
				'stacked' => Controller_Settings::shared()->should_stack_ui_columns() ? 'true' : 'false'
			),
			$attributes,
			$shortcode
		);
		$stacked = filter_var($attributes['stacked'], FILTER_VALIDATE_BOOLEAN);
		return $this->render_embedded_user_2fa_management_interface($stacked);
	}

	public function _handle_user_passkey_management_shortcode($attributes, $content = null, $shortcode = null) {
		$shortcode = $shortcode === null ? self::SHORTCODE_PASSKEY_MANAGEMENT : $shortcode;
		$attributes = shortcode_atts(
			array(
				'stacked' => Controller_Settings::shared()->should_stack_ui_columns() ? 'true' : 'false'
			),
			$attributes,
			$shortcode
		);
		$stacked = filter_var($attributes['stacked'], FILTER_VALIDATE_BOOLEAN);
		return $this->render_embedded_user_passkey_management_interface(null, $stacked);
	}

	public function _handle_shortcode_prerequisites() {
		if ($this->does_current_page_include_shortcode(self::SHORTCODE_2FA_MANAGEMENT) || $this->does_current_page_include_shortcode(self::SHORTCODE_PASSKEY_MANAGEMENT)) {
			if (!is_user_logged_in())
				auth_redirect();
			$this->enqueue_2fa_management_assets(true);
		}
	}

}
