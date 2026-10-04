<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC admin init class.
 *
 * This class adds the menu and submenu pages, and enqueuing the scripts and styles.
 */
class ADBC_Admin_Init extends ADBC_Singleton {

	/**
	 * Holds the menu hook for the main left sidebar admin menu item.
	 */
	private $left_menu;

	/**
	 * Holds the menu hook for the submenu item under Tools.
	 */
	private $tools_submenu;

	/**
	 * Holds the menu hook for the network admin menu item.
	 */
	private $network_menu;

	/**
	 * Holds the svg icon for the menu item.
	 */
	private $icon_svg = "";

	/**
	 * Option key used to persist conflict notices across redirects/requests.
	 *
	 * @var string
	 */
	private static $conflict_notice_option_key = 'adbc_conflict_notice';

	/**
	 * Store original plugin links before other plugins modify them
	 */
	private $original_plugin_meta_links = [];

	/**
	 * Constructor.
	 */
	protected function __construct() {

		parent::__construct();

		$this->icon_svg = 'data:image/svg+xml;base64,' . base64_encode( '<svg width="20" height="20" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path fill="#a0a5aa" d="M896 768q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0 768q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-384q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-1152q208 0 385 34.5t280 93.5 103 128v128q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-128q0-69 103-128t280-93.5 385-34.5z"/></svg>' );
	}

	/**
	 * Add the menu and submenu pages.
	 * 
	 * @return void
	 */
	public function add_admin_menu() {

		$left_menu = ADBC_Settings::instance()->get_setting( 'left_menu' );
		$tools_menu = ADBC_Settings::instance()->get_setting( 'tools_menu' );

		if ( $left_menu === '1' ) {
			$this->left_menu = add_menu_page( 'Advanced DB Cleaner', 'DB Cleaner', 'manage_options', 'advanced_db_cleaner', [ $this, 'main_page_callback' ], $this->icon_svg, '80.01123' );
		}

		if ( $tools_menu === '1' ) {
			$this->tools_submenu = add_submenu_page( 'tools.php', 'Advanced DB Cleaner', 'DB Cleaner', 'manage_options', 'advanced_db_cleaner', [ $this, 'main_page_callback' ], '80.01123' );
		}
	}

	/**
	 * Add the network admin menu page.
	 * 
	 * @return void
	 */
	public function add_network_admin_menu() {

		$network_menu = ADBC_Settings::instance()->get_setting( 'network_menu' );

		if ( $network_menu === '1' ) {
			$this->network_menu = add_menu_page( 'Advanced DB Cleaner', 'DB Cleaner', 'manage_network_options', 'advanced_db_cleaner_network', [ $this, 'main_page_callback' ], $this->icon_svg, '80.01123' );
		}

	}

	/**
	 * Callback for the main page.
	 * 
	 * @return void
	 */
	public function main_page_callback() {

		echo "<div id='adbc-plugin-root'></div>";
	}

	/**
	 * Enqueue the scripts and styles.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {

		// Enqueue global scripts for global notifications such as the rating notice.
		$this->enqueue_global_scripts();

		// Load plugins scripts only in the plugin page.
		if ( $hook != $this->left_menu && $hook != $this->tools_submenu && $hook != $this->network_menu )
			return;

		// Enqueue the scripts and styles.
		wp_enqueue_style( 'adbc-app-style', ADBC_PLUGIN_ABSOLUTE_URL . '/assets/css/app.css', [], ADBC_PLUGIN_VERSION );
		wp_enqueue_script( 'adbc-vendors-script', ADBC_PLUGIN_ABSOLUTE_URL . '/assets/js/vendors.js', [], ADBC_PLUGIN_VERSION, true );
		wp_enqueue_script( 'adbc-app-script', ADBC_PLUGIN_ABSOLUTE_URL . '/assets/js/app.js', [ 'adbc-vendors-script', 'wp-i18n', 'wp-date' ], ADBC_PLUGIN_VERSION, true );

		// Load the translations for the app script.
		wp_set_script_translations( 'adbc-app-script', 'advanced-database-cleaner', ADBC_PLUGIN_DIR_PATH . 'languages' );

		// Localize right after enqueuing.
		wp_localize_script(
			'adbc-app-script',
			'adbc_app_settings',
			array(
				'rest_url' => esc_url_raw( rest_url( ADBC_REST_API_NAMESPACE ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'version' => ADBC_PLUGIN_VERSION,
				'version_type' => ADBC_VERSION_TYPE,
				'is_pro_version' => ADBC_IS_PRO_VERSION ? '1' : '0',
				'settings' => ADBC_Settings::instance()->get_settings( false ), // Send none sensitive settings.
				'license_data' => ADBC_VERSION_TYPE === "PREMIUM" ? ADBC_License_Manager::get_license_data() : [], // Send masked license key.
				'warnings' => ADBC_Notifications::instance()->get_warnings(),
				'notifications' => ADBC_Notifications::instance()->get_local_notifications(),
				'is_multisite' => is_multisite() ? '1' : '0',
				'sites_list' => ADBC_Sites::instance()->get_sites_list(),
				'actionscheduler_actions_exists' => ADBC_Tables::is_actionscheduler_table_exists( 'actions' ) ? '1' : '0',
				'actionscheduler_logs_exists' => ADBC_Tables::is_actionscheduler_table_exists( 'logs' ) ? '1' : '0',
				'php_max_execution_time' => ( $value = (int) ini_get( 'max_execution_time' ) ) > 0 ? $value : 120,
				'is_woptimize_installed' => self::is_woptimize_installed() ? '1' : '0',
			)
		);

	}

	/**
	 * Enqueue global scripts for ADBC notifications.
	 * This runs on all admin pages, not just the plugin pages.
	 *
	 * @return void
	 */
	private function enqueue_global_scripts() {

		// Only load on admin pages and for users with manage_options capability.
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) )
			return;

		// Check if we have any global notifications to show
		$global_notifications = ADBC_Notifications::instance()->get_global_notifications();

		if ( empty( $global_notifications ) )
			return;

		$has_rating = isset( $global_notifications['rating_notice'] );
		$has_ltd = isset( $global_notifications['ltd_migration_notice'] );

		// Only enqueue shared assets if at least one of our custom global notices is active.
		if ( ! $has_rating && ! $has_ltd )
			return;

		// Single combined CSS/JS for all global notices (rating + LTD migration).
		wp_enqueue_style(
			'adbc-global-style',
			ADBC_PLUGIN_ABSOLUTE_URL . '/assets/css/global-style.css',
			array(),
			ADBC_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'adbc-global-script',
			ADBC_PLUGIN_ABSOLUTE_URL . '/assets/js/global-js.js',
			array( 'jquery' ),
			ADBC_PLUGIN_VERSION,
			true
		);

		// Single localized object shared by all notice JS.
		wp_localize_script(
			'adbc-global-script',
			'adbc_global_settings',
			array(
				'rest_url' => esc_url_raw( rest_url( ADBC_REST_API_NAMESPACE ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Prioritize plugin's translation files over global ones.
	 * 
	 * This ensures that translations in the plugin's languages folder
	 * take precedence over those in wp-content/languages/plugins/
	 * 
	 * @param string $mofile Path to the MO file.
	 * @param string $domain Text domain.
	 * @return string Modified path to the MO file.
	 */
	public static function prioritize_plugin_translations( $mofile, $domain ) {

		// Only apply to our plugin's text domain
		if ( 'advanced-database-cleaner' !== $domain ) {
			return $mofile;
		}

		// Get the current locale
		$locale = apply_filters( 'plugin_locale', determine_locale(), $domain );

		// Build the path to our plugin's translation file
		$plugin_mofile = ADBC_PLUGIN_DIR_PATH . 'languages/' . $domain . '-' . $locale . '.mo';

		// If our plugin's translation file exists, use it instead
		if ( file_exists( $plugin_mofile ) ) {
			return $plugin_mofile;
		}

		// Fallback to the original file
		return $mofile;

	}

	/**
	 * Change the script translation file name to load the correct file.
	 * 
	 * This is necessary because WordPress generates the MD5 hash based on the script file name,
	 * and we want to ensure that our translations are loaded from the correct file.
	 * The MD5 of the translation file built by WP will use assets/js/translations.js instead of assets/js/app.js
	 * 
	 * @param string $file The path to the translation file.
	 * @param string $handle The script handle.
	 * @param string $domain The text domain.
	 * @return string The modified path to the translation file.
	 */
	public static function change_script_translation_file_name( $file, $handle, $domain ) {

		if ( $domain !== 'advanced-database-cleaner' ) {
			return $file;
		}

		$file = str_replace( md5( 'assets/js/app.js' ), md5( 'assets/js/translations.js' ), $file );

		return $file;

	}

	/**
	 * What happens upon plugin activation.
	 * 
	 * @return void
	 */
	public static function activate() {
		// Nothing to do here for now
	}

	/**
	 * What happens upon plugin deactivation.
	 * 
	 * @return void
	 */
	public static function deactivate() {

		// unschedule analytics cron
		wp_clear_scheduled_hook( 'adbc_cron_analytics' );

		// Deactivate all automation tasks
		ADBC_Automation::instance()->deactivate_all_tasks();

	}

	/**
	 * Generate ADBC uploads folder if missing.
	 *
	 * @return void
	 */
	public static function create_adbc_uploads_folder_with_its_content() {

		// Check if the WP filesystem is initialized. If it fails, no need to continue.
		if ( ! ADBC_Files::instance()->is_wp_fs_initialized() )
			return;

		// Try to create the ADBC uploads folder. If it fails, no need to continue.
		if ( ! self::create_adbc_uploads_folder() )
			return;

		// Create files and folders inside the ADBC uploads folder.
		ADBC_Files::instance()->create_file( ADBC_Logging::DEBUG_LOG_FILE_PATH );

		// Create the scan folder.
		if ( ADBC_VERSION_TYPE === "PREMIUM" ) {
			ADBC_Files::instance()->create_folder( ADBC_Scan_Paths::SCAN_FOLDER_PATH, true );
			ADBC_Files::instance()->create_folder( ADBC_Analytics::ADBC_ANALYTICS_DIR, true );
			ADBC_Files::instance()->create_folder( ADBC_Analytics::ADBC_ANALYTICS_DATABASE_DIR, true );
			ADBC_Files::instance()->create_folder( ADBC_Analytics::ADBC_ANALYTICS_TABLES_DIR, true );
			ADBC_Files::instance()->create_folder( ADBC_Automation::AUTOMATION_EVENT_DIR, true );
			ADBC_Files::instance()->create_file( ADBC_Addons_Activity::ADDONS_ACTIVITY_LOG_FILE_PATH );
			ADBC_Files::instance()->create_file( ADBC_Addons_Activity::ADDONS_ACTIVITY_DICTIONARY );
			ADBC_Files::instance()->create_file( ADBC_Registered_Post_Types_Dict_Tracker::DICT_FILE_PATH );
			ADBC_Files::instance()->create_file( ADBC_Scan_Paths::get_custom_addons_dictionary_file_path() );
		}

	}

	/**
	 * Setup the ADBC uploads folder (with its content).
	 * 
	 * @return boolean True if successful, false otherwise.
	 */
	private static function create_adbc_uploads_folder() {

		// If the ADBC uploads folder doesn't exist, delete all folders starting with the ADBC uploads folder prefix before creating a new one.
		if ( ! ADBC_Files::instance()->is_dir( ADBC_UPLOADS_DIR_PATH ) ) {

			$files = glob( ADBC_WP_UPLOADS_DIR_PATH . '/' . ADBC_UPLOADS_DIR_PREFIX . '*' );
			$adbc_folder_length = strlen( ADBC_UPLOADS_DIR_PREFIX ) + ADBC_SECURITY_CODE_LENGTH;

			foreach ( $files as $file ) {
				$folder_name = basename( $file );
				// Delete if the folder name = $adbc_folder_length and composed only of alphanumeric, underscores and F.
				if ( strlen( $folder_name ) === $adbc_folder_length && preg_match( '/^[a-z0-9_F]+$/', $folder_name ) )
					ADBC_Files::instance()->delete_folder( $file );
			}
		}

		// Create the new ADBC uploads folder if it doesn't exist.
		ADBC_Files::instance()->create_folder( ADBC_UPLOADS_DIR_PATH, true );

		// At this point the ADBC uploads folder should exist. Verify if it's readable and writable.
		if ( ADBC_Files::instance()->is_readable_and_writable( ADBC_UPLOADS_DIR_PATH ) ) {
			ADBC_Notifications::instance()->delete_notification( "uploads_folder" ); // Clear the warning form the DB if it exists.
		} else {
			ADBC_Notifications::instance()->add_notification( 'uploads_folder' );
			return false;
		}

		return true;
	}

	/**
	 * Ensure the integrity of automation tasks.
	 * 
	 * This method checks all automation tasks, validates their structure, and ensures they are scheduled or unscheduled as needed.
	 * It also cleans up any orphaned cron hooks that no longer have a corresponding task with their events files.
	 */
	public static function ensure_automation_integrity() {

		$tasks = ADBC_Automation::instance()->tasks();
		$cron_ids = ADBC_Automation::get_scheduled_automation_crons_ids();

		foreach ( $tasks as $id => $task ) {

			$valid = ADBC_Automation_Validator::validate_task_structure( $task );

			// task invalid → remove unschedule & cleanup
			if ( $valid === false ) {
				ADBC_Automation::instance()->delete( $id );
				continue;
			}

			$is_active = (bool) $task['active'];
			$is_scheduled = in_array( $id, $cron_ids, true );

			// valid & inactive → ensure NOT scheduled
			if ( ! $is_active && $is_scheduled ) {
				ADBC_Automation::instance()->unschedule( $id ); // unschedule the task
				// keep events file (user may re-enable later)
			}

			// valid & active → ensure scheduled
			if ( $is_active && ! $is_scheduled ) {
				ADBC_Automation::instance()->schedule( $task ); // schedule the task
			}

		}

		// orphan cron hooks (hook exists but task record gone)
		foreach ( $cron_ids as $id ) {
			if ( ! isset( $tasks[ $id ] ) ) {
				// The task is not found in the tasks array, so we can unschedule it and delete its events file.
				ADBC_Automation::instance()->unschedule( $id );
				ADBC_Automation::delete_events_file( $id );
			}
		}

	}

	/**
	 * Register the cron_schedules filter to add ADBC custom schedules.
	 *
	 * @return void
	 */
	public static function register_cron_schedules_filter() {
		add_filter( 'cron_schedules', [ self::class, 'add_adbc_schedules_frequencies' ] );
	}

	/**
	 * Add custom schedules for ADBC cron jobs.
	 * 
	 * @param array $schedules The existing schedules.
	 * 
	 * @return array The modified schedules with ADBC custom frequencies added.
	 */
	public static function add_adbc_schedules_frequencies( $schedules ) {

		// Add adbc_hourly schedule (1 hour)
		$schedules['adbc_hourly'] = array(
			'interval' => HOUR_IN_SECONDS,
			'display' => __( 'Once hourly', 'advanced-database-cleaner' )
		);

		// Add adbc_twicedaily schedule (12 hours)
		$schedules['adbc_twicedaily'] = array(
			'interval' => 12 * HOUR_IN_SECONDS,
			'display' => __( 'Twice daily', 'advanced-database-cleaner' )
		);

		// Add adbc_daily schedule (1 day)
		$schedules['adbc_daily'] = array(
			'interval' => DAY_IN_SECONDS,
			'display' => __( 'Once daily', 'advanced-database-cleaner' )
		);

		// Add adbc_weekly schedule (1 week)
		$schedules['adbc_weekly'] = array(
			'interval' => WEEK_IN_SECONDS,
			'display' => __( 'Once weekly', 'advanced-database-cleaner' )
		);

		// Add adbc_monthly schedule (approx. 1 month)
		$schedules['adbc_monthly'] = array(
			'interval' => 30 * DAY_IN_SECONDS, // 30 days as a rough monthly interval
			'display' => __( 'Once monthly', 'advanced-database-cleaner' )
		);

		return $schedules;

	}

	/**
	 * Initialize settings with defaults if not set.
	 *
	 * @return void
	 */
	public static function maybe_show_global_notifications() {

		// Only load on admin pages and for users with manage_options capability.
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) )
			return;

		$notifications = ADBC_Notifications::instance()->get_global_notifications();

		foreach ( $notifications as $key => $notification ) {

			// Special handling for rating_notice notification
			if ( $key === 'rating_notice' ) {

				$rating_url = 'https://wordpress.org/support/plugin/advanced-database-cleaner/reviews/?filter=5';

				$text = sprintf(
					/* translators: 1: Plugin name with link */
					esc_html__( 'You have been using %s for more than 1 week. Would you mind taking a few seconds to give it a 5-star rating on WordPress? Thank you in advance :)', 'advanced-database-cleaner' ),
					'<a href="admin.php?page=advanced_db_cleaner">Advanced DB Cleaner</a>'
				);

				if ( is_multisite() )
					$text = esc_html__( 'You have been using the "Advanced DB Cleaner" for more than 1 week. Would you mind taking a few seconds to give it a 5-star rating on WordPress? Thank you in advance :)', 'advanced-database-cleaner' );

				printf(
					'<div id="adbc-rating-notice" class="adbc-notice">
						<div class="adbc-content">
							<div class="adbc-header">
								<div class="adbc-star">
									<span>⭐</span>
								</div>
								<span class="adbc-title">%s</span>
							</div>
							
							<p class="adbc-text">%s</p>
							
							<div class="adbc-buttons">
								<div class="adbc-buttons-left">
									<a href="%s" target="_blank" class="adbc-btn adbc-btn-primary">
										<span>⭐</span>%s
									</a>
									<a class="adbc-btn adbc-btn-secondary adbc-rating-dismiss">
										%s
									</a>
									<a class="adbc-btn adbc-btn-secondary adbc-rating-dismiss">
										%s
									</a>
								</div>
								
								<a id="adbc-rating-btn-remind-me" class="adbc-btn adbc-btn-secondary">
									<span class="dashicons dashicons-clock"></span>%s
								</a>
							</div>
						</div>
						
						<div>
							<a title="%s" class="adbc-btn-dismiss adbc-rating-dismiss">
								<span class="dashicons dashicons-dismiss"></span>
							</a>
						</div>
					</div>',
					esc_html__( 'Awesome!', 'advanced-database-cleaner' ),
					$text,
					$rating_url,
					esc_html__( 'Ok, you deserved it', 'advanced-database-cleaner' ),
					esc_html__( 'I already did', 'advanced-database-cleaner' ),
					esc_html__( 'No, not good enough', 'advanced-database-cleaner' ),
					esc_html__( 'Remind me later', 'advanced-database-cleaner' ),
					esc_html__( 'Dismiss', 'advanced-database-cleaner' )
				);

			} elseif ( $key === 'ltd_migration_notice' ) {

				$upgrade_url = 'https://sigmaplugin.com/upgrade-to-full-v4?utm_source=pro-v4-notification&utm_medium=adbc-pro&utm_campaign=plugins&utm_content=upgrade-to-v4';

				$message_intro = sprintf(
					/* translators: 1: opening <a> tag, 2: closing </a> tag */
					__( 'Your %1$sAdvanced DB Cleaner Pro%2$s has been upgraded to version 4.x, a major update featuring a new architecture, a redesigned interface, and many new features.', 'advanced-database-cleaner' ),
					'<a href="admin.php?page=advanced_db_cleaner" class="adbc-ltd-text-link">',
					'</a>'
				);

				if ( is_multisite() )
					$message_intro = esc_html__( 'Your "Advanced DB Cleaner Pro" has been upgraded to version 4.x, a major update featuring a new architecture, a redesigned interface, and many new features.', 'advanced-database-cleaner' );

				$phrase1 = sprintf(
					/* translators: 1: opening <strong> tag, 2: closing </strong> tag */
					esc_html__(
						'We have introduced a new Remote Scan feature, designed to detect orphaned database items with much higher accuracy. Because this feature requires ongoing infrastructure and maintenance costs, %1$sit is not included in the lifetime plan%2$s.',
						'advanced-database-cleaner'
					),
					'<strong>',
					'</strong>'
				);

				$phrase2 = esc_html__( 'To access it, we created a separate plugin version under an annual pricing model, bundled with the Remote Scan feature and upcoming AI and cloud capabilities. As a lifetime customer, you can benefit from a 50% discount on the first purchase and all renewals (discount available for a limited time).', 'advanced-database-cleaner' );

				echo '<div id="adbc-ltd-migration-notice" class="adbc-ltd-migration-notice">';
				echo '<div class="adbc-ltd-icon">';
				echo '<svg id="fi_8606859" enable-background="new 0 0 500 500" height="80" viewBox="0 0 500 500" width="80" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="m350.8 245.46c2.85 6.65 4.75 12.35 7.6 19 1.9 5.7 3.8 13.3 5.7 19.95 3.8 8.55 11.4 34.2 10.45 42.75-.95 7.6 1.9 19 0 21.85-1.9 5.7-6.65 12.35-12.35 13.3 0-2.85 1.9-5.7 1.9-9.5.95-12.35 0-19.95-2.85-31.35-3.8-13.3-19.95-56.05-24.7-70.31l-21.85-49.4c-7.6-16.15-16.15-30.4-23.75-47.5-6.65-18.05-23.75-45.6-38.95-55.1 0-2.85 12.35-10.45 17.1-8.55s10.45 9.5 12.35 13.3c3.8 6.65 6.65 9.5 10.45 16.15 3.8 5.7 6.65 11.4 9.5 17.1 6.65 10.45 13.3 22.8 19 34.2 4.75 12.35 9.5 24.7 15.2 37.05s10.45 23.75 15.2 37.06zm-7.6-61.76c.95 4.75 7.6 19.95 10.45 24.7 1.9 3.8 3.8 8.55 5.7 12.35s2.85 9.5 5.7 12.35c14.26-9.5 12.36-51.3-21.85-49.4zm-246.07 57.95c0 3.8 14.25 42.75 16.15 48.45 6.65 16.15 12.35 33.25 19 48.45 35.15-1.9 41.8-.95 79.81 0 12.35.95 75.06 11.4 86.46 13.3 13.3 1.9 27.55 7.6 40.85 9.5.95 0 6.65-5.7 6.65-5.7 3.8-3.8.95-23.75-.95-29.45-3.8-13.3-7.6-22.8-11.4-34.2-1.9-5.7-3.8-10.45-5.7-17.1-4.75-14.25-15.2-35.15-20.9-48.45-6.65-16.15-5.7-15.2-14.25-31.35-5.7-10.45-10.45-19-15.2-29.45-4.75-12.35-6.65-18.05-15.2-30.4-4.75-6.65-16.15-21.85-22.8-23.75-5.7 4.75-17.1 24.7-30.4 38-6.65 5.7-12.35 11.4-17.1 17.1-6.65 6.65-11.4 9.5-19 15.2-6.65 3.8-12.35 9.5-18.05 15.2l-47.5 37.05c-3.82 2.85-7.62 4.75-10.47 7.6zm17.1 98.81c-1.9 1.9-13.3 2.85-17.1 3.8-5.7.95-10.45 2.85-16.15 4.75-10.45 2.85-21.85 5.7-32.3 8.55-4.75.95-11.4 2.85-18.05 2.85-3.8-.95-3.8-1.9-5.7-4.75-1.9-1.9-3.8-2.85-4.75-4.75-11.4-14.25-21.85-43.7-19.95-61.76 1.9-12.35 6.65-18.05 17.1-22.8 24.7-10.45 37.05-8.55 48.45-12.35 4.75-.95 11.4-3.8 15.2-4.75l32.3 87.41c1.9 3.8 1.9 2.85.95 3.8zm114.96-186.21c5.7-1.9 10.45 2.85 10.45 7.6-.95 3.8-5.7 9.5-8.55 11.4-10.45 11.4-7.6 7.6-19 21.85-9.5 10.45-13.3 11.4-21.85 19.95l-21.85 17.1c-5.7 2.85-12.35-.95-11.4-7.6 0-4.75 5.7-8.55 8.55-11.4l22.8-18.05c7.6-6.65 11.4-13.3 19.95-21.85 2.85-2.85 6.65-5.7 9.5-9.5 3.8-4.75 6.65-7.6 11.4-9.5zm120.67-102.61c-4.75 1.9-4.75 4.75-5.7 12.35-.95 6.65-4.75 27.55-2.85 33.25.95 4.75 6.65 7.6 11.4 5.7 5.7-1.9 4.75-6.65 4.75-13.3.95-7.6 4.75-27.55 3.8-32.3-1.9-4.75-5.7-7.6-11.4-5.7zm110.2 271.72-14.25-15.2c-3.8-2.85-11.4-11.4-15.2-13.3-8.55-.95-14.25 6.65-9.5 13.3l13.3 13.3c4.75 4.75 15.2 20.9 23.75 12.35 3.8-3.8 1.9-5.7 1.9-10.45zm23.76-159.61c-5.7 1.9-7.6 4.75-12.35 6.65s-11.4 2.85-16.15 3.8c-17.1 3.8-7.6 19.95 1.9 18.05 2.85-.95 2.85-1.9 6.65-2.85 2.85-.95 4.75-.95 7.6-1.9 5.7-1.9 9.5-2.85 14.25-5.7 3.8-3.8 9.5-2.85 9.5-10.45 0-5.7-5.7-9.5-11.4-7.6zm-43.71-80.76c-5.7 2.85-5.7 5.7-9.5 11.4-2.85 3.8-5.7 7.6-8.55 11.4-1.9 2.85-6.65 9.5-6.65 13.3 0 6.65 5.7 10.45 11.4 8.55 3.8-.95 5.7-7.6 8.55-11.4 3.8-5.7 15.2-19.95 15.2-24.7 0-5.7-4.75-11.4-10.45-8.55zm18.05 157.71c-4.75.95-8.55 5.7-5.7 11.4s33.25 16.15 40.85 14.25c4.75-.95 8.55-6.65 5.7-11.4-1.9-5.7-10.45-2.85-26.6-10.45-4.75-1.9-8.54-5.7-14.25-3.8zm-410.43 36.11c-9.5 3.8-17.1 5.7-23.75 9.5-10.45 6.65-4.75 19.95 6.65 15.2 3.8-1.9 7.6-2.85 11.4-4.75s7.6-2.85 12.35-4.75c9.5-3.8 5.7-19-6.65-15.2zm14.25 95.01c.95-.95 0 0 1.9-.95l15.2-4.75c4.75-.95 12.35-3.8 17.1-4.75.95.95 2.85 6.65 5.7 11.4 1.9 3.8 4.75 7.6 6.65 11.4 8.55 15.2 5.7 9.5 14.25 21.85 3.8 4.75 13.3 17.1 15.2 20.9 1.9 5.7 2.85 8.55-1.9 12.35-6.65 5.7-16.15 7.6-25.65 9.5-6.65.95-9.5-1.9-12.35-5.7-4.75-6.65-9.5-15.2-13.3-21.85-3.8-8.55-8.55-15.2-12.35-23.75-1.9-4.75-9.5-20.9-10.45-25.65z" fill="#5EA500" fill-rule="evenodd"></path></svg>';
				echo '</div>';
				echo '<div class="adbc-ltd-content">';
				echo '<span class="adbc-ltd-title">' . esc_html__( 'Advanced DB Cleaner Pro V4 is finally here!', 'advanced-database-cleaner' ) . '</span>';
				echo '<p class="adbc-ltd-text">' . wp_kses_post( $message_intro ) . '</p>';
				echo '<div style="background-color:#EFF6FF;padding:16px 20px;margin:10px 0px;border-radius:4px;border:1.5px solid #3b82f6;box-shadow:0 4px 4px rgba(0,0,0,0.06);">';
				echo '<p style="margin:0 0 8px 0;font-weight:700;font-size:14px;color:#FF6900;letter-spacing:0.05em;">ⓘ IMPORTANT!</p>';
				echo '<ul class="adbc-ltd-list"><li>' . $phrase1 . '</li><li>' . $phrase2 . '</li></ul>';
				echo '</div>';
				echo '<div class="adbc-ltd-buttons">';
				echo '<a href="' . esc_url( $upgrade_url ) . '" target="_blank" class="button button-primary adbc-ltd-btn-primary">';
				echo '<span class="dashicons dashicons-arrow-right-alt" style="margin-top:5px;margin-right:5px;"></span>';
				echo '<b>' . esc_html__( 'How to get to the full version - 50% OFF forever', 'advanced-database-cleaner' ) . '</b>';
				echo '</a>';
				echo '<a href="#" id="adbc-ltd-btn-remind-me" class="button adbc-ltd-btn-secondary">';
				echo '<span class="dashicons dashicons-clock" style="margin-top:5px;margin-right:5px;"></span>';
				echo esc_html__( 'Remind me in a week', 'advanced-database-cleaner' );
				echo '</a>';
				echo '</div>';
				echo '</div>';
				echo '<div class="adbc-ltd-dismiss">';
				echo '<a href="#" title="' . esc_attr__( 'Dismiss', 'advanced-database-cleaner' ) . '"><span class="dashicons dashicons-dismiss"></span></a>';
				echo '</div>';
				echo '</div>';

			} else {
				// Standard notification rendering. For now, we are not rendering these notifications in the admin area.
				// $type = esc_attr( $notification['type'] );
				// $message = wp_kses_post( $notification['message'] );
				// $dismissible = ! empty( $notification['dismissible'] ) ? ' is-dismissible' : '';
				// printf(
				// 	'<div class="notice notice-%1$s%2$s adbc-notice" data-adbc-notice-key="%3$s"><p>%4$s</p></div>',
				// 	$type,
				// 	$dismissible,
				// 	esc_attr( $key ),
				// 	$message
				// );
			}
		}
	}

	/**
	 * Static proxy for add_admin_menu to be used in the hooks.
	 * 
	 * @return void
	 */
	public static function _add_admin_menu() {
		self::instance()->add_admin_menu();
	}

	/**
	 * Static proxy for add_network_admin_menu to be used in the hooks.
	 * 
	 * @return void
	 */
	public static function _add_network_admin_menu() {
		self::instance()->add_network_admin_menu();
	}

	/**
	 * Static proxy for enqueue_scripts to be used in the hooks.
	 * 
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public static function _enqueue_scripts( $hook ) {
		self::instance()->enqueue_scripts( $hook );
	}

	/**
	 * Load all cleanup type handlers.
	 * 
	 * This method includes all PHP files in the type-handlers directories,
	 * allowing each handler to self-register with the General Cleanup system.
	 * It supports both free and premium handlers based on the plugin version.
	 * 
	 * @return void
	 */
	public static function load_general_cleanup_handlers() {

		$base_dir = ADBC_PLUGIN_DIR_PATH . '/includes/classes/general-cleanup/type-handlers';
		$premium_dir = ADBC_PLUGIN_DIR_PATH . 'includes/premium/classes/general-cleanup/type-handlers';

		$dirs = [ $base_dir ];

		// Only add premium if this is the premium build
		if ( ADBC_VERSION_TYPE === "PREMIUM" ) {
			$dirs[] = $premium_dir;
		}

		foreach ( $dirs as $dir ) {

			if ( ! is_dir( $dir ) ) {
				continue;
			}

			foreach ( glob( $dir . '/*.php' ) as $file ) {
				include_once $file; // triggers the self-registration logic
			}

		}

	}

	/**
	 * Detect and handle conflicts between free, pro and premium versions.
	 *
	 * @return bool True if a conflict was detected and handled, false otherwise.
	 */
	public static function has_conflict() {

		if ( ! function_exists( 'deactivate_plugins' ) )
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$premium_active = is_plugin_active( 'advanced-database-cleaner-premium/advanced-db-cleaner.php' );
		$pro_active = is_plugin_active( 'advanced-database-cleaner-pro/advanced-db-cleaner.php' );
		$free_active = is_plugin_active( 'advanced-database-cleaner/advanced-db-cleaner.php' );

		if ( $premium_active && $free_active ) {

			$network_wide = function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( 'advanced-database-cleaner/advanced-db-cleaner.php' );
			deactivate_plugins( 'advanced-database-cleaner/advanced-db-cleaner.php', true, $network_wide );
			ADBC_Common_Utils::old_plugin_version_deactivation_cleaning();
			self::schedule_conflict_notice( 'free_premium' );

			return true;

		}

		if ( $premium_active && $pro_active ) {

			$network_wide = function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( 'advanced-database-cleaner-pro/advanced-db-cleaner.php' );
			deactivate_plugins( 'advanced-database-cleaner-pro/advanced-db-cleaner.php', true, $network_wide );
			ADBC_Common_Utils::old_plugin_version_deactivation_cleaning();
			self::schedule_conflict_notice( 'pro_premium' );

			return true;

		}

		if ( $pro_active && $free_active ) {

			$network_wide = function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( 'advanced-database-cleaner/advanced-db-cleaner.php' );
			deactivate_plugins( 'advanced-database-cleaner/advanced-db-cleaner.php', true, $network_wide );
			ADBC_Common_Utils::old_plugin_version_deactivation_cleaning();
			self::schedule_conflict_notice( 'free_pro' );

			return true;

		}

		return false;

	}

	/**
	 * Check whether the WOptimize pro is installed on the site.
	 *
	 * @return bool True if WOptimize Pro plugin is installed, false otherwise.
	 */
	public static function is_woptimize_installed() {

		if ( ! function_exists( 'get_plugins' ) )
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		foreach ( array_keys( get_plugins() ) as $plugin_file ) {

			if ( strpos( $plugin_file, 'woptimize-pro' ) === 0 )
				return true;

		}

		return false;

	}

	/**
	 * Maybe schedule a conflict notice.
	 *
	 * @return void
	 */
	public static function maybe_schedule_conflict_notice() {

		global $pagenow;

		if ( $pagenow === 'plugins.php' && get_option( self::$conflict_notice_option_key, '' ) !== '' ) {
			add_action( 'all_admin_notices', [ self::class, 'render_scheduled_conflict_notice' ] );
		}

	}

	/**
	 * Persist a conflict notice so it is reliably displayed even after redirects.
	 *
	 * @param string $type Conflict type identifier.
	 * @return void
	 */
	private static function schedule_conflict_notice( $type ) {

		// Store the last conflict type; it will be rendered (and cleared) on the next admin page load.
		update_option( self::$conflict_notice_option_key, $type, false );

		// Also attempt to render it in the current request when possible.
		add_action( 'all_admin_notices', [ self::class, 'render_scheduled_conflict_notice' ] );
	}

	/**
	 * Render the stored conflict notice (if any) and clear it.
	 *
	 * @return void
	 */
	public static function render_scheduled_conflict_notice() {

		$type = get_option( self::$conflict_notice_option_key, '' );

		if ( $type === '' ) {
			return;
		}

		// Clear immediately so the notice is shown only once.
		delete_option( self::$conflict_notice_option_key );

		switch ( $type ) {
			case 'free_premium':
				self::free_premium_conflict_notice();
				break;
			case 'pro_premium':
				self::pro_premium_conflict_notice();
				break;
			case 'free_pro':
				self::free_pro_conflict_notice();
				break;
		}
	}

	/**
	 * Display a notice if the free version was deactivated due to the premium version activation.
	 * 
	 * @return void
	 */
	public static function free_premium_conflict_notice() {
		echo '<div class="notice notice-warning"><p>';
		esc_html_e( 'The free version of Advanced DB Cleaner has been de-activated since the premium version is active.', 'advanced-database-cleaner' );
		echo "</p></div>";
	}

	/**
	 * Display a notice if the free version was deactivated due to the pro version activation.
	 * 
	 * @return void
	 */
	public static function free_pro_conflict_notice() {
		echo '<div class="notice notice-warning"><p>';
		esc_html_e( 'The free version of Advanced DB Cleaner has been de-activated since the pro version is active.', 'advanced-database-cleaner' );
		echo "</p></div>";
	}

	/**
	 * Display a notice if the pro version was deactivated due to the premium version activation.
	 * 
	 * @return void
	 */
	public static function pro_premium_conflict_notice() {
		echo '<div class="notice notice-warning"><p>';
		esc_html_e( 'The pro version of Advanced DB Cleaner has been de-activated since the newest premium version is active.', 'advanced-database-cleaner' );
		echo "</p></div>";
	}

	/**
	 * Static proxy for capture_original_plugin_meta_links to be used in the hooks.
	 * 
	 * @param array $links The current plugin meta links.
	 * @param string $file The plugin file.
	 * @return array The modified plugin meta links.
	 */
	public static function _capture_original_plugin_meta_links( $links, $file ) {
		return self::instance()->capture_original_plugin_meta_links( $links, $file );
	}

	/**
	 * Static proxy for restore_plugin_meta_links to be used in the hooks.
	 * 
	 * @param array $links The current plugin meta links.
	 * @param string $file The plugin file.
	 * @return array The modified plugin meta links.
	 */
	public static function _restore_plugin_meta_links( $links, $file ) {
		return self::instance()->restore_plugin_meta_links( $links, $file );
	}

	/**
	 * Capture the original WordPress default plugin meta links before any plugin modifies them
	 */
	public function capture_original_plugin_meta_links( $links, $file ) {

		$plugin_file = plugin_basename( ADBC_MAIN_PLUGIN_FILE_PATH );

		if ( $file === $plugin_file && empty( $this->original_plugin_meta_links ) ) {
			$this->original_plugin_meta_links = $links;
		}

		return $links;

	}

	/**
	 * Remove all third-party links
	 */
	public function restore_plugin_meta_links( $links, $file ) {

		$plugin_file = plugin_basename( ADBC_MAIN_PLUGIN_FILE_PATH );

		if ( $file !== $plugin_file ) {
			return $links;
		}

		return $this->original_plugin_meta_links;

	}

	/**
	 * Load the plugin text domain for translations.
	 * 
	 * @return void
	 */
	public static function load_text_domain() {
		load_plugin_textdomain( 'advanced-database-cleaner', false, ADBC_PLUGIN_DIR_NAME . '/languages/' );
	}

}