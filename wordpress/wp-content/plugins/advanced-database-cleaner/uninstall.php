<?php

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

class ADBC_Uninstall {

	// List of all ADBC options to delete on uninstall
	private static $adbc_options = [ 
		'adbc_plugin_settings' => 'all',
		'adbc_plugin_automation' => 'all',
		'adbc_plugin_conflict_notice' => 'all',
		'adbc_plugin_scan_info_options' => 'premium_pro',
		'adbc_plugin_scan_info_tables' => 'premium_pro',
		'adbc_plugin_scan_info_cron_jobs' => 'premium_pro',
		'adbc_plugin_scan_info_users_meta' => 'premium_pro',
		'adbc_plugin_scan_info_posts_meta' => 'premium_pro',
		'adbc_plugin_scan_info_transients' => 'premium_pro',
		'adbc_plugin_should_stop_scan_options' => 'premium_pro',
		'adbc_plugin_should_stop_scan_tables' => 'premium_pro',
		'adbc_plugin_should_stop_scan_cron_jobs' => 'premium_pro',
		'adbc_plugin_should_stop_scan_users_meta' => 'premium_pro',
		'adbc_plugin_should_stop_scan_posts_meta' => 'premium_pro',
		'adbc_plugin_should_stop_scan_transients' => 'premium_pro',
		'adbc_plugin_license_key' => 'premium',
		'adbc_plugin_license_key_license' => 'premium',
		'adbc_plugin_license_key_pro' => 'pro',
		'adbc_plugin_license_key_pro_license' => 'pro',
		'adbc_plugin_pro_api_scan_balance' => 'pro',
	];

	// List of all ADBC transients to delete on uninstall
	private static $adbc_transients = [ 
		'adbc_plugin_tables_to_repair' => 'all',
		'adbc_plugin_innodb_conversion_lock' => 'all',
		'adbc_plugin_post_types_dict_updated' => 'premium_pro',
	];

	// List of all ADBC cron jobs to unschedule on uninstall
	private static $adbc_cron_jobs = [ 
		'adbc_cron_analytics' => 'premium_pro',
		'adbc_cron_automation' => 'all',
		'edd_sl_sdk_weekly_license_check_advanced-database-cleaner-premium' => 'premium',
		'edd_sl_sdk_weekly_license_check_advanced-database-cleaner-pro' => 'pro',
	];

	/**
	 * Run the uninstall process.
	 * 
	 * @return void
	 */
	public static function run() {

		$is_premium_version_exists = self::is_premium_version_exists();
		$is_new_free_version_exists = self::is_new_free_version_exists();
		$is_new_pro_version_exists = self::is_new_pro_version_exists();

		$is_premium_version_active = is_plugin_active( 'advanced-database-cleaner-premium/advanced-db-cleaner.php' );
		$is_free_version_active = is_plugin_active( 'advanced-database-cleaner/advanced-db-cleaner.php' );
		$is_pro_version_active = is_plugin_active( 'advanced-database-cleaner-pro/advanced-db-cleaner.php' );

		$current_plugin_slug = basename( __DIR__ );

		self::add_edd_cache_option_to_options_list( $current_plugin_slug );

		if ( $current_plugin_slug === 'advanced-database-cleaner-premium' ) { // we are in the premium version

			if ( $is_new_free_version_exists && $is_new_pro_version_exists ) { // free exists + pro exist

				self::clean_premium_data_only();

				// unschedule automation crons if free is deactivated and pro is deactivated
				if ( ! $is_free_version_active && ! $is_pro_version_active ) {
					wp_unschedule_hook( 'adbc_cron_automation' );
				}

			} elseif ( $is_new_free_version_exists && ! $is_new_pro_version_exists ) { // free exists + pro doesn't exists

				self::clean_premium_and_pro_data_only();

				// unschedule automation crons if free is deactivated
				if ( ! $is_free_version_active ) {
					wp_unschedule_hook( 'adbc_cron_automation' );
				}

			} elseif ( ! $is_new_free_version_exists && $is_new_pro_version_exists ) { // free doesn't exist + pro exists

				self::clean_premium_data_only();

				// unschedule automation crons if pro is deactivated
				if ( ! $is_pro_version_active ) {
					wp_unschedule_hook( 'adbc_cron_automation' );
				}

			} else { // free doesn't exist + pro doesn't exist

				self::clean_all_data();

			}

		} elseif ( $current_plugin_slug === 'advanced-database-cleaner-pro' ) { // we are in the pro version

			if ( $is_new_free_version_exists && $is_premium_version_exists ) { // free exists + premium exists

				self::clean_pro_data_only();

				// unschedule automation crons if free is deactivated and premium is deactivated
				if ( ! $is_free_version_active && ! $is_premium_version_active ) {
					wp_unschedule_hook( 'adbc_cron_automation' );
				}

			} elseif ( $is_new_free_version_exists && ! $is_premium_version_exists ) { // free exists + premium doesn't exist

				self::clean_premium_and_pro_data_only();

				// unschedule automation crons if free is deactivated
				if ( ! $is_free_version_active ) {
					wp_unschedule_hook( 'adbc_cron_automation' );
				}

			} elseif ( ! $is_new_free_version_exists && $is_premium_version_exists ) { // free doesn't exist + premium exists

				self::clean_pro_data_only();

				// unschedule automation crons if pro is deactivated
				if ( ! $is_pro_version_active ) {
					wp_unschedule_hook( 'adbc_cron_automation' );
				}

			} else { // free doesn't exist + premium doesn't exist

				self::clean_all_data();

			}

		} else { // we are in the free version

			if ( $is_premium_version_exists && $is_new_pro_version_exists ) { // premium exists + pro exists

				// unschedule automation crons if premium is deactivated and pro is deactivated
				if ( ! $is_premium_version_active && ! $is_pro_version_active ) {
					wp_unschedule_hook( 'adbc_cron_automation' );
				}

			} elseif ( $is_premium_version_exists && ! $is_new_pro_version_exists ) { // premium exists + pro doesn't exist

				self::clean_pro_data_only();

				// unschedule automation crons if premium is deactivated
				if ( ! $is_premium_version_active ) {
					wp_unschedule_hook( 'adbc_cron_automation' );
				}

			} elseif ( ! $is_premium_version_exists && $is_new_pro_version_exists ) { // premium doesn't exist + pro exists

				self::clean_premium_data_only();

				// unschedule automation crons if pro is deactivated
				if ( ! $is_pro_version_active ) {
					wp_unschedule_hook( 'adbc_cron_automation' );
				}

			} else { // premium doesn't exist + pro doesn't exist

				self::clean_all_data();

			}

		}

	}

	private static function clean_all_data() {
		self::delete_all_plugin_data();
	}

	private static function clean_pro_data_only() {

		// delete pro only options
		foreach ( self::$adbc_options as $option_name => $plugin_type ) {
			if ( $plugin_type === 'pro' )
				delete_option( $option_name );
		}

		// delete pro only transients
		foreach ( self::$adbc_transients as $transient_name => $plugin_type ) {
			if ( $plugin_type === 'pro' )
				delete_transient( $transient_name );
		}

		// Unschedule pro only crons
		foreach ( self::$adbc_cron_jobs as $cron_job => $plugin_type ) {
			if ( $plugin_type === 'pro' )
				wp_unschedule_hook( $cron_job );
		}

	}

	private static function clean_premium_data_only() {

		// delete premium only options
		foreach ( self::$adbc_options as $option_name => $plugin_type ) {
			if ( $plugin_type === 'premium' )
				delete_option( $option_name );
		}

		// delete premium only transients
		foreach ( self::$adbc_transients as $transient_name => $plugin_type ) {
			if ( $plugin_type === 'premium' )
				delete_transient( $transient_name );
		}

		// Unschedule premium only crons
		foreach ( self::$adbc_cron_jobs as $cron_job => $plugin_type ) {
			if ( $plugin_type === 'premium' )
				wp_unschedule_hook( $cron_job );
		}

	}

	private static function clean_premium_and_pro_data_only() {

		$data_types_to_delete = [ 'premium', 'pro', 'premium_pro' ];

		// delete premium and pro only options
		foreach ( self::$adbc_options as $option_name => $plugin_type ) {
			if ( in_array( $plugin_type, $data_types_to_delete ) )
				delete_option( $option_name );
		}

		// delete premium and pro only transients
		foreach ( self::$adbc_transients as $transient_name => $plugin_type ) {
			if ( in_array( $plugin_type, $data_types_to_delete ) )
				delete_transient( $transient_name );
		}

		// Unschedule premium and pro only crons
		foreach ( self::$adbc_cron_jobs as $cron_job => $plugin_type ) {
			if ( in_array( $plugin_type, $data_types_to_delete ) )
				wp_unschedule_hook( $cron_job );
		}

		// delete the scan and the analytics folders with their contents
		$adbc_upload_folder = self::get_adbc_upload_folder_path();
		$scan_folder = $adbc_upload_folder . '/scan';
		$analytics_folder = $adbc_upload_folder . '/analytics';
		$automation_folder = $adbc_upload_folder . '/automation_events';
		$addons_activity_file = $adbc_upload_folder . '/addons_activity.log';
		$addons_activity_dictionary_file = $adbc_upload_folder . '/addons_activity_dictionary.log';
		$registered_post_types_dictionary_file = $adbc_upload_folder . '/registered_post_types_dictionary.txt';

		self::delete_folder( $scan_folder );
		self::delete_folder( $analytics_folder );
		self::delete_folder( $automation_folder );

		if ( file_exists( $addons_activity_file ) )
			wp_delete_file( $addons_activity_file );

		if ( file_exists( $addons_activity_dictionary_file ) )
			wp_delete_file( $addons_activity_dictionary_file );

		if ( file_exists( $registered_post_types_dictionary_file ) )
			wp_delete_file( $registered_post_types_dictionary_file );

	}

	/**
	 * Recursively delete a folder and its contents.
	 * 
	 * @param string $folder The folder path to delete.
	 * 
	 * @return bool True on success, false on failure.
	 */
	private static function delete_folder( $folder ) {

		if ( ! is_dir( $folder ) ) {
			return false;
		}

		$files = array_diff( scandir( $folder ), [ '.', '..' ] ); // get all files/folders

		foreach ( $files as $file ) {
			$path = $folder . DIRECTORY_SEPARATOR . $file;

			if ( is_dir( $path ) ) {
				self::delete_folder( $path ); // recursion for subfolders
			} else {
				wp_delete_file( $path ); // delete file
			}
		}

		return rmdir( $folder ); // delete the folder itself

	}

	/**
	 * Get the ADBC upload folder path.
	 * 
	 * @return string The ADBC upload folder path.
	 */
	private static function get_adbc_upload_folder_path() {

		// Get upload folder security code to delete the folder
		$settings = get_option( 'adbc_plugin_settings', [] );
		$security_code = isset( $settings['security_code'] ) ? $settings['security_code'] : '';
		$upload_folder = wp_upload_dir()['basedir'] . '/adbc_uploads_F_' . $security_code;

		return $upload_folder;

	}

	/**
	 * Delete the ADBC upload folder.
	 * 
	 * @return void
	 */
	private static function delete_adbc_upload_folder() {
		$upload_folder = self::get_adbc_upload_folder_path();
		self::delete_folder( $upload_folder );
	}

	/**
	 * Delete all ADBC options.
	 * 
	 * @return void
	 */
	private static function delete_all_adbc_options() {
		foreach ( self::$adbc_options as $option_name => $_ ) {
			delete_option( $option_name );
		}
	}

	/**
	 * Delete all ADBC transients.
	 * 
	 * @return void
	 */
	private static function delete_all_adbc_transients() {
		foreach ( self::$adbc_transients as $transient_name => $_ ) {
			delete_transient( $transient_name );
		}
	}

	/**
	 * Unschedule all ADBC cron jobs.
	 * 
	 * @return void
	 */
	private static function unschedule_all_cron_jobs() {
		foreach ( self::$adbc_cron_jobs as $cron_job => $_ ) {
			wp_unschedule_hook( $cron_job );
		}
	}

	/**
	 * Delete all plugin data: options, transients, upload folder.
	 * 
	 * @return void
	 */
	private static function delete_all_plugin_data() {
		self::delete_adbc_upload_folder();
		self::delete_all_adbc_transients();
		self::delete_all_adbc_options();
		self::unschedule_all_cron_jobs();
	}

	/**
	 * Check if a new free version (>= 4.0.0) exists.
	 * 
	 * @return bool True if a new free version exists, false otherwise.
	 */
	private static function is_new_free_version_exists() {

		// Ensure plugin functions are loaded
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		$free_slug = 'advanced-database-cleaner/advanced-db-cleaner.php';

		if ( isset( $plugins[ $free_slug ] ) ) {
			$free_version = $plugins[ $free_slug ]['Version'];

			// Compare version
			if ( version_compare( $free_version, '4.0.0', '>=' ) ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Check if the premium version exists.
	 * 
	 * @return bool True if the premium version exists, false otherwise.
	 */
	private static function is_premium_version_exists() {

		// Ensure plugin functions are loaded
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		$premium_slug = 'advanced-database-cleaner-premium/advanced-db-cleaner.php';

		if ( isset( $plugins[ $premium_slug ] ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Check if the pro version exists.
	 * 
	 * @return bool True if the pro version exists, false otherwise.
	 */
	private static function is_new_pro_version_exists() {

		// Ensure plugin functions are loaded
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		$pro_slug = 'advanced-database-cleaner-pro/advanced-db-cleaner.php';

		if ( isset( $plugins[ $pro_slug ] ) ) {
			$pro_version = $plugins[ $pro_slug ]['Version'];

			// Compare version
			if ( version_compare( $pro_version, '4.0.0', '>=' ) ) {
				return true;
			}

		}

		return false;

	}

	/**
	 * Add the EDD updater cache option to the options list.
	 *
	 * Added for ADBC integration so we can compute the cache key
	 * without instantiating the updater and adding listeners.
	 *
	 * @param string $slug    Plugin slug.
	 * 
	 * @return void
	 */
	private static function add_edd_cache_option_to_options_list( $slug ) {

		$plugin_version_type = $slug === 'advanced-database-cleaner-pro' ? 'pro' : 'premium';
		$license_option_name = $plugin_version_type === 'pro' ? 'adbc_plugin_license_key_pro' : 'adbc_plugin_license_key';
		$license = get_option( $license_option_name, '' );

		if ( empty( $license ) )
			return;

		$key = md5(
			wp_json_encode(
				array(
					(string) $slug,
					(string) $license,
					0,
				)
			)
		);

		self::$adbc_options[ "edd_sl_{$key}" ] = $plugin_version_type;

	}

}

// Run the uninstall process
ADBC_Uninstall::run();