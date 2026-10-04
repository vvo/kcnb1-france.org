<?php
// Copyright 2014-2016 RealFaviconGenerator

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . '..' . DIRECTORY_SEPARATOR .
	'public' . DIRECTORY_SEPARATOR . 'class-favicon-by-realfavicongenerator-common.php';
require_once plugin_dir_path( __FILE__ ) . 'class-favicon-by-realfavicongenerator-api-response.php';

class Favicon_By_RealFaviconGenerator_Admin extends Favicon_By_RealFaviconGenerator_Common {

	const DISMISS_UPDATE_NOTIICATION              = 'fbrfg_dismiss_update_notification';
	const DISMISS_AUTOMATIC_UPDATE_NOTIFICATION   = 'fbrfg_dismiss_autmatic_update_notification';
	const DISMISS_UPDATE_ALL_UPDATE_NOTIFICATIONS = 'fbrfg_dismiss_all_update_notifications';
	const SETTINGS_FORM                           = 'fbrfg_settings_form';
	const NONCE_SETTINGS_UPDATE                   = 'nonce_settings_update';

	const NONCE_ACTION_NAME_FAVICON_GENERATION    = 'favicon_generation';
	const NONCE_ACTION_NAME_SETTINGS_UPDATE       = 'settings_update';

	const MENU_SLUG_APPEARANCE = 'fbrfg_favicon_appearance_menu';
	const MENU_SLUG_SETTINGS   = 'fbrfg_favicon_settings_menu';

	protected static $instance = null;

	private function __construct() {
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'admin_head', array( $this, 'add_favicon_markups' ) );

		// Deactivate Genesis default favicon
		add_filter( 'genesis_pre_load_favicon', array( $this, 'return_empty_favicon_for_genesis' ) );

		// See
		// - https://wordpress.org/support/topic/wp_debug-notice-for-bp_setup_current_user
		// - https://buddypress.org/support/topic/wp_debug-notice-for-bp_setup_current_user
		// The idea: is_super_admin must not be called too soon.
		add_action( 'init', array( $this, 'register_admin_actions' ) );

		// Check for updates
		add_action( Favicon_By_RealFaviconGenerator_Common::ACTION_CHECK_FOR_UPDATE, array( $this, 'check_for_updates' ) );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function register_admin_actions() {
		// Except for the headers, everything is accessible only to the admin
		if ( ! is_super_admin() ) {
			return;
		}

		add_action(
			'admin_menu',
			array( $this, 'create_favicon_settings_menu' )
		);

		add_action(
			'wp_ajax_' . Favicon_By_RealFaviconGenerator_Common::PLUGIN_PREFIX . '_install_new_favicon',
			array( $this, 'install_new_favicon' )
		);
		add_action(
			'wp_ajax_nopriv_' . Favicon_By_RealFaviconGenerator_Common::PLUGIN_PREFIX . '_install_new_favicon',
			array( $this, 'install_new_favicon' )
		);

		// Update notice
		add_action( 'admin_notices', array( $this, 'display_update_notice' ) );
		add_action( 'admin_init', array( $this, 'process_ignored_notice' ) );

		// Schedule update check
		if ( ! wp_next_scheduled( Favicon_By_RealFaviconGenerator_Common::ACTION_CHECK_FOR_UPDATE ) ) {
			wp_schedule_event( time(), 'daily', Favicon_By_RealFaviconGenerator_Common::ACTION_CHECK_FOR_UPDATE );
		}
	}

	public function create_favicon_settings_menu() {
		add_theme_page(
			__( 'Favicon', 'favicon-by-realfavicongenerator' ),
			__( 'Favicon', 'favicon-by-realfavicongenerator' ),
			'manage_options',
			self::MENU_SLUG_APPEARANCE,
			array( $this, 'create_favicon_appearance_page' )
		);

		add_options_page(
			__( 'Favicon Settings', 'favicon-by-realfavicongenerator' ),
			__( 'Favicon', 'favicon-by-realfavicongenerator' ),
			'manage_options',
			self::MENU_SLUG_SETTINGS,
			array( $this, 'create_favicon_settings_page' )
		);
	}

	public function create_favicon_settings_page() {
		global $current_user;

		$user_id = $current_user->ID;

		// Prepare variables
		$favicon_appearance_url       = admin_url( 'themes.php?page=' . self::MENU_SLUG_APPEARANCE );
		$favicon_admin_url            = admin_url( 'options-general.php?page=' . self::MENU_SLUG_SETTINGS );
		$display_update_notifications = ! $this->get_boolean_user_option(
			Favicon_By_RealFaviconGenerator_Common::META_NO_UPDATE_NOTICE
		);

		// Template time!
		include_once plugin_dir_path( __FILE__ ) . 'views' . DIRECTORY_SEPARATOR . 'settings.php';
	}

	public function create_favicon_appearance_page() {
		$result = null;

		// Prepare settings page

		// Option to allow user to not use the Rewrite API: display it only when the Rewrite API is available
		// Due to too many problems with the rewrite API (for example, http://wordpress.org/support/topic/do-not-work-8),
		// it was deciced to turn the feature off once for all
		$can_rewrite = false;

		$pic_path = $this->get_full_picture_path();

		$favicon_configured = $this->is_favicon_configured();
		$favicon_in_root    = $this->is_favicon_in_root();

		$preview_url = $this->is_preview_available() ? $this->get_preview_url() : null;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Callback URL from RealFaviconGenerator; nonce is verified later in install_new_favicon().
		if ( isset( $_REQUEST['json_result_url'] ) ) {
			// New favicon to install:
			// Parameters will be processed with an Ajax call

			$new_favicon_params_url = filter_var(
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Callback URL from RealFaviconGenerator; nonce is verified later in install_new_favicon().
				filter_var( wp_unslash( $_REQUEST['json_result_url'] ), FILTER_SANITIZE_URL ),
				FILTER_SANITIZE_SPECIAL_CHARS
			);
			$ajax_url               = admin_url( 'admin-ajax.php', isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' );
		} else {
			// No new favicon, simply display the settings page
			$new_favicon_params_url = null;
		}

		// Nonce
		$nonce = wp_create_nonce( self::NONCE_ACTION_NAME_FAVICON_GENERATION );

		// External files
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui' );
		wp_enqueue_script( 'jquery-effects-pulsate' );
		wp_enqueue_media();
		wp_enqueue_style(
			'fbrfg_admin_style',
			plugins_url(
				'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'admin.css',
				__FILE__
			),
			array(),
			'1.3.48'
		);

		// Template time!
		include_once plugin_dir_path( __FILE__ ) . 'views' . DIRECTORY_SEPARATOR .
		'appearance.php';
	}

	private function build_json_result_url() {
		// RFG is configured with path_only=true, so json_result_url is a path
		// like /files/<hash>/json.json. We accept that, and also tolerate a
		// full URL provided the host is realfavicongenerator.net. Any other
		// host (including userinfo tricks like http://@attacker/poc.json,
		// where realfavicongenerator.net would otherwise become the userinfo)
		// is rejected.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified by caller (install_new_favicon) before this method is invoked.
		$raw   = isset( $_REQUEST['json_result_url'] ) ? esc_url_raw( wp_unslash( $_REQUEST['json_result_url'] ) ) : '';
		$parts = wp_parse_url( $raw );
		if ( ! is_array( $parts ) ) {
			throw new InvalidArgumentException( 'Invalid favicon result URL' );
		}
		if ( ! empty( $parts['host'] ) &&
			strtolower( $parts['host'] ) !== 'realfavicongenerator.net' ) {
			throw new InvalidArgumentException( 'Invalid favicon result URL' );
		}
		if ( ! empty( $parts['scheme'] ) &&
			! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
			throw new InvalidArgumentException( 'Invalid favicon result URL' );
		}

		$path  = isset( $parts['path'] ) ? $parts['path'] : '/';
		$query = isset( $parts['query'] ) ? '?' . $parts['query'] : '';

		return 'https://realfavicongenerator.net' . $path . $query;
	}

	private function download_result_json( $url ) {
		$resp = wp_remote_get( $url );
		if ( is_wp_error( $resp ) ) {
			throw new InvalidArgumentException( 'Cannot download JSON file at ' . esc_url( $url ) . ': ' . esc_html( $resp->get_error_message() ) );
		}

		$json = wp_remote_retrieve_body( $resp );
		if ( empty( $json ) ) {
			throw new InvalidArgumentException( 'Empty JSON document at ' . esc_url( $url ) );
		}

		return $json;
	}

	public function install_new_favicon() {
		header( 'Content-type: application/json' );

		// Verify the nonce BEFORE doing anything that touches the
		// json_result_url parameter. Otherwise an attacker who tricks
		// an admin into opening a crafted URL could trigger the SSRF
		// path and reflect attacker-controlled error messages.
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION_NAME_FAVICON_GENERATION ) ) {
			echo wp_json_encode( array(
				'status'  => 'error',
				'message' => __( 'Nonce check failed', 'favicon-by-realfavicongenerator' ),
			) );
			die();
		}

		try {
			$url = $this->build_json_result_url();

			$result = $this->download_result_json( $url );

			$response = new Favicon_By_RealFaviconGenerator_Api_Response( $result );

			if ( ! wp_verify_nonce( $response->getCustomParameter(), self::NONCE_ACTION_NAME_FAVICON_GENERATION ) ) {
				// Attack in progress?
				?>
{
	"status": "error",
	"message": "<?php esc_html_e( 'Nonce was not recognized. This case is supposed to happen only in case of XSS attack. If you feel like something is wrong, please <a href=\"mailto:contact@realfavicongenerator.net\">contact us</a>.', 'favicon-by-realfavicongenerator' ) ?>"
}
				<?php
			} else {
				$zip_path = Favicon_By_RealFaviconGenerator_Common::get_tmp_dir();
				if ( ! file_exists( $zip_path ) ) {
					if ( ! wp_mkdir_p( $zip_path ) ) {
						// translators: %s is the path to the directory that could not be created
						throw new InvalidArgumentException( sprintf( esc_html__( 'Cannot create directory %s to store the favicon package', 'favicon-by-realfavicongenerator' ), esc_html( $zip_path ) ) );
					}
				}
				$response->downloadAndUnpack( $zip_path );

				$this->store_pictures( $response );

				$this->store_preview( $response->getPreviewPath() );

				Favicon_By_RealFaviconGenerator_Common::remove_directory( $zip_path );

				update_option( Favicon_By_RealFaviconGenerator_Common::OPTION_HTML_CODE, $response->getHtmlCode() );

				$this->set_favicon_configured( true, $response->isFilesInRoot(), $response->getVersion(), $response->getNonInteractiveAPIRequest() );
				?>
{
	"status": "success",
	"preview_url": <?php echo json_encode( $this->get_preview_url() ); ?>,
	"favicon_in_root": <?php echo json_encode( $this->is_favicon_in_root() ); ?>
}
				<?php
			}
		} catch ( Exception $e ) {
			?>
{
	"status": "error",
	"message": <?php echo json_encode( $e->getMessage() ); ?>
}
			<?php
		}

		die();
	}

	public function get_picture_dir() {
		return Favicon_By_RealFaviconGenerator_Common::get_files_dir();
	}

	/**
	 * Returns http//somesite.com/blog/wp-content/upload/fbrfg/
	 */
	public function get_picture_url() {
		return Favicon_By_RealFaviconGenerator_Common::get_files_url();
	}

	/**
	 * Returns /blog/wp-content/upload/fbrfg/
	 */
	public function get_full_picture_path() {
		return parse_url( $this->get_picture_url(), PHP_URL_PATH );
	}

	/**
	 * Returns wp-content/upload/fbrfg/
	 */
	public function get_picture_path() {
		return substr( $this->get_picture_url(), strlen( home_url() ) );
	}

	public function get_preview_path( $preview_file_name = null ) {
		if ( ! $preview_file_name ) {
			$preview_file_name = $this->get_preview_file_name();
		}
		return $this->get_picture_dir() . 'preview' . DIRECTORY_SEPARATOR .
			$preview_file_name;
	}

	public function get_preview_url( $preview_file_name = null ) {
		if ( ! $preview_file_name ) {
			$preview_file_name = $this->get_preview_file_name();
		}
		return $this->get_picture_url() . '/preview/' . $preview_file_name;
	}

	public function store_preview( $preview_path ) {
		// Remove previous preview, if any
		$previous_preview = $this->get_preview_file_name();
		if ( $previous_preview != null && ( file_exists( $this->get_preview_path( $previous_preview ) ) ) ) {
			unlink( $this->get_preview_path( $previous_preview ) );
		}

		if ( $preview_path == null ) {
			// "Unregister" previous preview, if any
			$this->set_preview_file_name( null );
			return null;
		} else {
			$preview_file_name = 'preview_' . hash( 'sha256', 'RFB stuff here ' . wp_rand() . microtime() ) . '.png';
		}

		if ( ! file_exists( dirname( $this->get_preview_path( $preview_file_name ) ) ) ) {
			wp_mkdir_p( dirname( $this->get_preview_path( $preview_file_name ) ) );
		}

		$this->portable_rename( $preview_path, $this->get_preview_path( $preview_file_name ) );

		$this->set_preview_file_name( $preview_file_name );
	}

	public function store_pictures( $rfg_response ) {
		$working_dir = $this->get_picture_dir();

		// Move pictures to production directory
		$files = glob( $working_dir . '*' );
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
		$files = glob( $rfg_response->getProductionPackagePath() . '/*' );
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				$this->portable_rename( $file, $working_dir . basename( $file ) );
			}
		}

		// Even if the package was not supposed to be put in root, make the files (also) appear at the root of the site
		// So /favicon.ico works, for example.
		// See https://wordpress.org/support/topic/choose-between-rewrite-api-and-dedicated-directory
		if ( $this->can_access_pics_with_url_rewrite() ) {
			$this->rewrite_pictures_url( $working_dir );
			flush_rewrite_rules();
		}
	}

	public function rewrite_pictures_url( $pic_dir ) {
		foreach ( scandir( $pic_dir ) as $file ) {
			if ( ! is_dir( $pic_dir . DIRECTORY_SEPARATOR . $file ) ) {
				add_rewrite_rule(
					str_replace( '.', '\.', $file ),
					trim( $this->get_picture_path(), '/' ) . '/' . $file
				);
			}
		}
	}

	/**
	 * Indicate if it is possible to create URLs such as /favicon.ico
	 */
	public function can_access_pics_with_url_rewrite() {
		global $wp_rewrite;

		// If blog is in root AND rewriting is available (http://wordpress.stackexchange.com/questions/142273/checking-that-the-rewrite-api-is-available),
		// we can produce URLs such as /favicon.ico
		$rewrite = ( $this->wp_in_root() && $wp_rewrite->using_permalinks() );
		if ( ! $rewrite ) {
			return false;
		}

		// See http://wordpress.org/support/topic/fbrfg-not-updating-htaccess-rewrite-rules
		$htaccess = get_home_path() . DIRECTORY_SEPARATOR . '.htaccess';
		// Two cases:
		// - There is no .htaccess. Either we are not using Apache (so the Rewrite API is supposed to handle
		// the rewriting differently) or there is a problem with Apache/WordPress config, but this is not our job.
		// - .htaccess is present. If so, it should be writable.
		return ( ( ! file_exists( $htaccess ) ) || wp_is_writable( $htaccess ) );
	}

	/**
	 * Indicate if WP is installed in the root of the web site (eg. http://mysite.com) or not (eg. http://mysite.com/blog).
	 */
	public function wp_in_root() {
		$path = parse_url( home_url(), PHP_URL_PATH );
		return ( ( $path == null ) || ( strlen( $path ) == 0 ) );
	}

	public function set_boolean_user_option( $option_name, $option_value ) {
		global $current_user;
		$user_id = $current_user->ID;

		update_user_option( $user_id, $option_name, $option_value );
	}

	public function get_boolean_user_option( $option_name ) {
		global $current_user;
		$user_id = $current_user->ID;

		return get_user_option( $option_name );
	}

	public function is_manual_update_notice_to_be_displayed() {
		$this->log_info( 'Check if manual update notice should be displayed' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display check.
		if ( isset( $_REQUEST['json_result_url'] ) ) {
			$this->log_info( 'Favicon installation in progress, disable notice' );
			return false;
		}

		// Did the user prevent all notices?
		if ( $this->get_boolean_user_option( Favicon_By_RealFaviconGenerator_Common::META_NO_UPDATE_NOTICE ) ) {
			$this->log_info( 'User disabled all update notices' );
			return false;
		}

		// No update
		if ( $this->get_latest_manual_available_update() == null ) {
			$this->log_info( 'There is no pending manual update' );
			return false;
		}

		// Did the user prevent the notice for this particular version?
		if ( $this->get_boolean_user_option( Favicon_By_RealFaviconGenerator_Common::META_NO_UPDATE_NOTICE_FOR_VERSION . $this->get_latest_manual_available_update() ) ) {
			$this->log_info( 'User disabled update notices for ' . $this->get_latest_manual_available_update() );
			return false;
		}

		// If, for some reasons, the current version matches the "latest update",
		// then the updated was already done.
		$result = ( $this->get_latest_manual_available_update() != $this->get_favicon_current_version() );
		$this->log_info(
			'Compare current version (' . $this->get_favicon_current_version() . ') and latest update (' .
			$this->get_latest_manual_available_update() . '): ' . ( $result ? 'true' : 'false' )
		);

		return $result;
	}

	public function is_automatic_update_notice_to_be_displayed() {
		$this->log_info( 'Check if automatic update notice should be displayed' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display check.
		if ( isset( $_REQUEST['json_result_url'] ) ) {
			$this->log_info( 'Favicon installation in progress, disable notice' );
			return false;
		}

		// Did the user prevent all notices?
		if ( $this->get_boolean_user_option( Favicon_By_RealFaviconGenerator_Common::META_NO_UPDATE_NOTICE ) ) {
			$this->log_info( 'User disabled all update notices' );
			return false;
		}

		$versions = $this->get_most_recent_automatic_update();
		// Was there an update?
		if ( $versions == null ) {
			$this->log_info( 'There was no automatic update' );
			return false;
		}

		// Did the user prevent the notice for this particular version?
		if ( $this->get_boolean_user_option( Favicon_By_RealFaviconGenerator_Common::META_NO_AUTOMATIC_UPDATE_NOTICE_FOR_VERSION . $versions[0] . '_' . $versions[1] ) ) {
			$this->log_info( 'User disabled update notice for update from ' . $versions[0] . ' to ' . $versions[1] );
			return false;
		}

		$this->log_info( 'Automatic update notice should be displayed' );
		return true;
	}

	public function display_update_notice() {
		if ( $this->is_manual_update_notice_to_be_displayed() ) {
			$this->log_info( 'Display manual update notice' );

			$description = $this->get_updates_description( $this->get_favicon_current_version(), $this->get_latest_manual_available_update() );
			?>
<div class="update-nag">
	<p>
		<strong><?php esc_html_e( 'An update is available on RealFaviconGenerator:', 'favicon-by-realfavicongenerator' ) ?></strong>
	</p>

			<?php echo esc_html( $description ) ?>

	<p>
			<?php
			echo wp_kses(
				sprintf(
					// translators: %s is the URL to the favicon settings page
					__(
						'You might want to <a href="%s">generate your favicon again</a>',
						'favicon-by-realfavicongenerator'
					),
					esc_url( admin_url( 'themes.php?page=' . self::MENU_SLUG_APPEARANCE ) )
				),
				array( 'a' => array( 'href' => array() ) )
			);
			?>
	</p>

	<p>
		<a href="<?php echo esc_html( $this->add_parameter_to_current_url( self::DISMISS_UPDATE_NOTIICATION . '=0', self::NONCE_SETTINGS_UPDATE, self::NONCE_ACTION_NAME_SETTINGS_UPDATE ) ) ?>">
			<?php esc_html_e( 'Hide this notice', 'favicon-by-realfavicongenerator' ); ?>
		</a>
		|
		<a href="<?php echo esc_html( $this->add_parameter_to_current_url( self::DISMISS_UPDATE_ALL_UPDATE_NOTIFICATIONS . '=0', self::NONCE_SETTINGS_UPDATE, self::NONCE_ACTION_NAME_SETTINGS_UPDATE ) ) ?>">
			<?php esc_html_e( 'Do not warn me again in case of update', 'favicon-by-realfavicongenerator' ); ?>
		</a>
	</p>
</div>
			<?php
		} elseif ( $this->is_automatic_update_notice_to_be_displayed() ) {
			$this->log_info( 'Display automatic update notice' );

			$auto_versions = $this->get_most_recent_automatic_update();
			$description   = $this->get_updates_description( $auto_versions[0], $auto_versions[1] );
			?>
<div class="update-nag">
	<p><strong><?php esc_html_e( 'Your favicon was updated automatically:', 'favicon-by-realfavicongenerator' ); ?></strong></p>

			<?php echo esc_html( $description ) ?>

	<p>
		<a href="<?php echo esc_html( $this->add_parameter_to_current_url( self::DISMISS_AUTOMATIC_UPDATE_NOTIFICATION . '=0', self::NONCE_SETTINGS_UPDATE, self::NONCE_ACTION_NAME_SETTINGS_UPDATE ) ) ?>">
			<?php esc_html_e( 'Hide this notice', 'favicon-by-realfavicongenerator' ) ?>
		</a>
		|
		<a href="<?php echo esc_html( $this->add_parameter_to_current_url( self::DISMISS_UPDATE_ALL_UPDATE_NOTIFICATIONS . '=0', self::NONCE_SETTINGS_UPDATE, self::NONCE_ACTION_NAME_SETTINGS_UPDATE ) ) ?>">
			<?php esc_html_e( 'Do not warn me again in case of update', 'favicon-by-realfavicongenerator' ) ?>
		</a>
	</p>
</div>
			<?php
		}
	}

	public function process_ignored_notice() {
		global $current_user;
		$user_id = $current_user->ID;

		if ( ! isset( $_REQUEST[ self::NONCE_SETTINGS_UPDATE ] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST[ self::NONCE_SETTINGS_UPDATE ] ) ), self::NONCE_ACTION_NAME_SETTINGS_UPDATE ) ) {
			$this->log_info( 'No nonce or invalid nonce on settings update' );
			return;
		}

		if ( isset( $_REQUEST[ self::DISMISS_UPDATE_NOTIICATION ] ) &&
				'0' == $_REQUEST[ self::DISMISS_UPDATE_NOTIICATION ] ) {
			$this->log_info( 'Disable manual update notice for ' . $this->get_latest_manual_available_update() );
			$this->set_boolean_user_option( Favicon_By_RealFaviconGenerator_Common::META_NO_UPDATE_NOTICE_FOR_VERSION . $this->get_latest_manual_available_update(), true );
		}

		if ( isset( $_REQUEST[ self::DISMISS_AUTOMATIC_UPDATE_NOTIFICATION ] ) &&
				'0' == $_REQUEST[ self::DISMISS_AUTOMATIC_UPDATE_NOTIFICATION ] ) {
			$versions = $this->get_most_recent_automatic_update();
			$this->log_info( 'Disable automatic update notice for ' . $versions[0] . '-' . $versions[1] );
			$this->set_boolean_user_option(
				Favicon_By_RealFaviconGenerator_Common::META_NO_AUTOMATIC_UPDATE_NOTICE_FOR_VERSION .
				$versions[0] . '_' . $versions[1],
				true
			);
		}

		$no_notices = null;
		if ( ( isset( $_REQUEST[ self::DISMISS_UPDATE_ALL_UPDATE_NOTIFICATIONS ] ) &&
				'0' == $_REQUEST[ self::DISMISS_UPDATE_ALL_UPDATE_NOTIFICATIONS ] ) ) {
			// The "no more notifications" link was clicked in the notification itself
			$no_notices = true;
		}
		if ( isset( $_REQUEST[ self::SETTINGS_FORM ] ) &&
				'1' == $_REQUEST[ self::SETTINGS_FORM ] ) {
			// The settings form was validated
			$no_notices = ( ! isset( $_REQUEST[ self::DISMISS_UPDATE_ALL_UPDATE_NOTIFICATIONS ] ) ||
				( '0' == $_REQUEST[ self::DISMISS_UPDATE_ALL_UPDATE_NOTIFICATIONS ] ) );
		}
		if ( $no_notices !== null ) {
			$this->set_boolean_user_option( Favicon_By_RealFaviconGenerator_Common::META_NO_UPDATE_NOTICE, $no_notices );
		}
	}
}
