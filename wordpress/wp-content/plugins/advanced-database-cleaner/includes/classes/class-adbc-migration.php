<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Migration.
 * 
 * This class provides methods for the migration process.
 */
class ADBC_Migration {

	/**
	 * The automation task frequency mapping.
	 * 
	 * @var array Old is key, new is value.
	 */
	private const FREQUENCY_MAPPING = [ 
		'once' => 'adbc_once',
		'hourly' => 'adbc_hourly',
		'twicedaily' => 'adbc_twicedaily',
		'daily' => 'adbc_daily',
		'weekly' => 'adbc_weekly',
		'monthly' => 'adbc_monthly',
		'yearly' => 'adbc_yearly',
	];

	/**
	 * The automation task operations mapping.
	 * 
	 * @var array Old is key, new is value.
	 */
	private const OPERATIONS_MAPPING = [ 
		"revision" => "revisions",
		"auto-draft" => "auto_drafts",
		"trash-posts" => "trashed_posts",
		"moderated-comments" => "unapproved_comments",
		"spam-comments" => "spam_comments",
		"trash-comments" => "trashed_comments",
		"pingbacks" => "pingbacks",
		"trackbacks" => "trackbacks",
		"orphan-postmeta" => "unused_postmeta",
		"orphan-commentmeta" => "unused_commentmeta",
		"orphan-usermeta" => "unused_usermeta",
		"orphan-termmeta" => "unused_termmeta",
		"orphan-relationships" => "unused_relationships",
		"expired-transients" => "expired_transients",
		"optimize" => "tables_to_optimize",
		"repair" => "tables_to_repair",
	];

	/**
	 * Get the available migration data for a given data segment.
	 *
	 * @param string $data_type One of 'all', 'free', or 'pro'. Controls which
	 *                          group of data to check for availability.
	 *
	 * @return array The available migration data keys. Possible values:
	 *               'keep_last', 'automation_tasks', 'manual_corrections',
	 *               'old_free_exists', 'pro_exists'.
	 */
	public static function get_available_migration_data( $data_type = 'all' ) {

		$available_data = [];

		// Get the pro data, always get the data that is not stored in the database
		if ( $data_type === 'all' || $data_type === 'pro' ) {

			// Check if there are manual corrections if we are in premium
			if ( ADBC_VERSION_TYPE === 'PREMIUM' && ADBC_Common_Utils::is_old_pro_exists() ) {
				if ( self::is_old_manual_corrections_exists() ) {
					$available_data[] = 'manual_corrections';
				}
			}

			// Check if there are installed old free version
			if ( ADBC_VERSION_TYPE === 'PREMIUM' && ADBC_Common_Utils::is_old_free_exists() ) {
				$available_data[] = 'old_free_exists';
			}

			// Check if there are installed pro version
			if ( ADBC_VERSION_TYPE === 'PREMIUM' && ADBC_Common_Utils::is_old_pro_exists() ) {
				$available_data[] = 'pro_exists';
			}

		}

		$old_settings = get_option( 'aDBc_settings', [] );

		// If the old settings are not found, return the available non database data
		if ( empty( $old_settings ) || ! is_array( $old_settings ) || ! isset( $old_settings['plugin_version'] ) ) {
			return $available_data;
		}

		// Get the free data
		if ( $data_type === 'all' || $data_type === 'free' ) {

			// Check if there are keep last settings
			if ( isset( $old_settings['keep_last'] ) && is_array( $old_settings['keep_last'] ) && count( $old_settings['keep_last'] ) > 0 ) {
				$available_data[] = 'keep_last';
			}

			// Check if there are cleaning tasks
			$has_automation_tasks = false;
			$cleaning_tasks = get_option( 'aDBc_clean_schedule', [] );
			if ( ! empty( $cleaning_tasks ) && is_array( $cleaning_tasks ) && count( $cleaning_tasks ) > 0 ) {
				$has_automation_tasks = true;
			}

			// Check if there are optimization tasks
			$optimization_tasks = get_option( 'aDBc_optimize_schedule', [] );
			if ( ! empty( $optimization_tasks ) && is_array( $optimization_tasks ) && count( $optimization_tasks ) > 0 ) {
				$has_automation_tasks = true;
			}

			if ( $has_automation_tasks ) {
				$available_data[] = 'automation_tasks';
			}

		}

		return $available_data;

	}

	/**
	 * Run the migration.
	 *
	 * @param array  $items_to_migrate        Array of strings indicating which items to migrate.
	 *                                        Supported values: 'automation_tasks', 'keep_last', 'manual_corrections'.
	 * @param bool   $uninstall_old_versions  Whether to uninstall the old plugin versions after migration (premium only).
	 * @param string $data_type               One of 'all', 'free', or 'pro'. Controls which data groups are processed.
	 *
	 * @return array Result map; keys present depend on requested items. Possible keys:
	 *               'automation_tasks_success' (0|1|2), 'keep_last_success' (0|1|2),
	 *               'manual_corrections_success' (0|1|2), 'uninstall_old_versions_success' (0|1|2).
	 */
	public static function run( $items_to_migrate, $uninstall_old_versions = false, $data_type = 'all' ) {

		$results = [];

		$old_settings = get_option( 'aDBc_settings', false );

		// Migrate free data
		if ( $old_settings !== false ) { // Try to migrate free data for requested data type only if the old settings exist

			if ( in_array( $data_type, [ 'all', 'free' ], true ) ) {

				// Migrate automation tasks if any
				if ( array_search( 'automation_tasks', $items_to_migrate ) !== false ) {

					$automation_cleaning_tasks_success = 0;
					$automation_optimization_tasks_success = 0;

					// Migrate cleaning tasks if any
					$cleaning_tasks = get_option( 'aDBc_clean_schedule' );
					if ( is_array( $cleaning_tasks ) && count( $cleaning_tasks ) > 0 )
						$automation_cleaning_tasks_success = self::migrate_cleaning_tasks( $cleaning_tasks );
					elseif ( $cleaning_tasks === false )
						$automation_cleaning_tasks_success = null;

					// Migrate optimization tasks if any
					$optimization_tasks = get_option( 'aDBc_optimize_schedule' );
					if ( is_array( $optimization_tasks ) && count( $optimization_tasks ) > 0 )
						$automation_optimization_tasks_success = self::migrate_optimization_tasks( $optimization_tasks );
					elseif ( $optimization_tasks === false )
						$automation_optimization_tasks_success = null;

					// Return the success result of the automation tasks combined
					if ( $automation_cleaning_tasks_success === 0 && $automation_optimization_tasks_success === 0 )
						$results['automation_tasks_success'] = 0; // Both Tasks types exists but no tasks were migrated
					elseif ( $automation_cleaning_tasks_success === 1 && $automation_optimization_tasks_success === 1 )
						$results['automation_tasks_success'] = 1; // Both Tasks types exists, and all tasks were migrated
					elseif ( $automation_cleaning_tasks_success === null && $automation_optimization_tasks_success === null )
						$results['automation_tasks_success'] = 0; // No tasks exist
					elseif ( $automation_cleaning_tasks_success === null && $automation_optimization_tasks_success !== null )
						$results['automation_tasks_success'] = $automation_optimization_tasks_success; // Only optimization tasks exists, return the success result of the optimization tasks
					elseif ( $automation_cleaning_tasks_success !== null && $automation_optimization_tasks_success === null )
						$results['automation_tasks_success'] = $automation_cleaning_tasks_success; // Only cleaning tasks exists, return the success result of the cleaning tasks
					else
						$results['automation_tasks_success'] = 2; // Both Tasks types exists, and some tasks were not migrated

					// Add the imported_tasks_deactivated_notice if some tasks were migrated
					if ( in_array( $automation_cleaning_tasks_success, [ 1, 2 ], true ) || in_array( $automation_optimization_tasks_success, [ 1, 2 ], true ) )
						ADBC_Notifications::instance()->add_notification( 'imported_tasks_deactivated_notice' );

				}

				// Migrate keep last settings if any
				if ( array_search( 'keep_last', $items_to_migrate ) !== false ) {

					if ( is_array( $old_settings ) && isset( $old_settings['keep_last'] ) && is_array( $old_settings['keep_last'] ) && count( $old_settings['keep_last'] ) > 0 )
						$results['keep_last_success'] = self::migrate_keep_last_settings( $old_settings['keep_last'] );
					else
						$results['keep_last_success'] = 0;

				}

			}

		} else { // If the old settings do not exist, return 0 for both automation tasks and keep last if requested

			if ( array_search( 'automation_tasks', $items_to_migrate ) !== false )
				$results['automation_tasks_success'] = 0;

			if ( array_search( 'keep_last', $items_to_migrate ) !== false )
				$results['keep_last_success'] = 0;

		}

		// Migrate pro data
		if ( in_array( $data_type, [ 'all', 'pro' ], true ) ) {

			// Migrate manual corrections if any and if we are in premium
			if ( ADBC_VERSION_TYPE === 'PREMIUM' && array_search( 'manual_corrections', $items_to_migrate ) !== false ) {
				$results['manual_corrections_success'] = ADBC_Common_Utils::is_old_pro_data_exists() ? self::migrate_manual_corrections() : 0;
			}

			// Uninstall old versions if requested
			if ( ADBC_VERSION_TYPE === 'PREMIUM' && $uninstall_old_versions ) {
				$results['uninstall_old_versions_success'] = self::uninstall_old_versions();
			}

		}

		// In premium, dismiss the migration available notification; and delete the old versions data depending on their existence
		if ( ADBC_VERSION_TYPE === 'PREMIUM' && ADBC_IS_PRO_VERSION === false ) {

			ADBC_Notifications::instance()->dismiss_notification( 'migration_available' );

			$old_free_exists = ADBC_Common_Utils::is_old_free_exists();
			$old_pro_exists = ADBC_Common_Utils::is_old_pro_exists();

			if ( $old_free_exists && ! $old_pro_exists )
				self::delete_old_pro_data();
			elseif ( ! $old_free_exists && ! $old_pro_exists )
				self::delete_all_old_data();

		}

		return $results;

	}

	/**
	 * Run the free migration.
	 *
	 * Invoked on 'init' in the free version to migrate automation tasks and
	 * "keep last" settings from older versions if needed.
	 *
	 * @return void
	 */
	public static function run_free_migration() {

		try {

			// Migrate free data if the free migration is not done
			if ( ADBC_Settings::instance()->get_setting( 'free_migration_done' ) === '0' ) {

				// Don't migrate if the premium already migrated
				if ( ADBC_Common_Utils::is_premium_exists() && adbc_notifications::instance()->is_notification_dismissed( 'migration_available' ) ) {
					if ( ! ADBC_Common_Utils::is_old_pro_exists() )
						self::delete_all_old_data();
					return;
				}

				self::run( [ 'automation_tasks', 'keep_last' ], false, 'free' );

				ADBC_Settings::instance()->update_settings( [ 'free_migration_done' => '1' ] );

				if ( ! ADBC_Common_Utils::is_old_pro_exists() )
					self::delete_all_old_data();

			}

		} catch (Exception $e) {
			return ADBC_Logging::log_error( 'ADBC Migration: Failed to migrate free data - ' . $e->getMessage() );
		}

	}

	/**
	 * Run the pro migration.
	 *
	 * @return void
	 */
	public static function run_pro_migration() {

		try {

			// Migrate and activate the license key if the old license key exists and it's not already activated in the current new pro version
			if ( ! isset( ADBC_License_Manager::get_license_data( false )['key'] ) && self::is_old_license_exists() ) {

				$old_license_key = get_option( 'aDBc_edd_license_key' );

				// If the old license key is empty, don't migrate
				if ( empty( $old_license_key ) ) {
					return;
				}

				// Send activation request to EDD to activate this old license in the current version
				ADBC_License_Manager::activate_license( $old_license_key );

				// delete the old license key
				delete_option( 'aDBc_edd_license_key' );

			}

			// Premium exists or migration available notification is dismissed, don't migrate
			if ( ADBC_Notifications::instance()->is_notification_dismissed( 'migration_available' ) || ADBC_Common_Utils::is_premium_exists() )
				return;

			// old pro data doesn't exist, add and dismiss the migration available notification explicitly to avoid further checks
			if ( ! ADBC_Common_Utils::is_old_pro_data_exists() ) {
				ADBC_Notifications::instance()->add_notification( 'migration_available' );
				ADBC_Notifications::instance()->dismiss_notification( 'migration_available' );
				return;
			}

			// run the migration for all data
			self::run( [ 'manual_corrections', 'automation_tasks', 'keep_last' ] );

			// Mark the free migration as done
			ADBC_Settings::instance()->update_settings( [ 'free_migration_done' => '1' ] );

			// Dismiss the migration available notification
			ADBC_Notifications::instance()->dismiss_notification( 'migration_available' );

			// delete old pro data if old free exists or delete all old data if old free doesn't exist
			if ( ADBC_Common_Utils::is_old_free_exists() )
				self::delete_old_pro_data();
			else
				self::delete_all_old_data();

		} catch (Exception $e) {
			return ADBC_Logging::log_error( 'ADBC Migration: Failed to migrate pro data - ' . $e->getMessage() );
		}

	}

	/**
	 * Migrate the cleaning tasks.
	 * 
	 * @param array $cleaning_tasks The cleaning tasks.
	 * 
	 * @return int 0 if failed, 1 if success, 2 if some of the cleaning tasks were not migrated
	 */
	private static function migrate_cleaning_tasks( $cleaning_tasks ) {

		$existing_tasks = ADBC_Automation::instance()->tasks();

		$total_migrated_cleaning_tasks = 0;

		foreach ( $cleaning_tasks as $old_task_name => $old_task_details ) {

			if ( self::validate_old_automation_task_structure( $old_task_name, $old_task_details ) === false ) {
				continue;
			}

			$new_task = [ 
				'type' => 'general_cleanup',
				'name' => $old_task_name,
				'frequency' => self::FREQUENCY_MAPPING[ $old_task_details['repeat'] ],
				'start_datetime' => self::get_timestamp_from_old_date_and_time( $old_task_details['start_date'], $old_task_details['start_time'] ),
				'operations' => self::migrate_old_operations( $old_task_details['elements_to_clean'] ),
				'active' => false, // Always deactivate the task
			];

			if ( self::is_task_already_exists( $new_task, $existing_tasks ) ) {
				continue;
			}

			if ( ADBC_Automation::instance()->create( $new_task ) !== null )
				$total_migrated_cleaning_tasks++;

		}

		// If no cleaning tasks were migrated, return 0
		if ( $total_migrated_cleaning_tasks === 0 )
			return 0;

		// If all cleaning tasks were migrated, return 1, otherwise if it's mixed, return 2
		return $total_migrated_cleaning_tasks === count( $cleaning_tasks ) ? 1 : 2;

	}

	/**
	 * Migrate the optimization tasks.
	 * 
	 * @param array $optimization_tasks The optimization tasks.
	 * 
	 * @return int 0 if failed, 1 if success, 2 if some of the optimization tasks were not migrated
	 */
	private static function migrate_optimization_tasks( $optimization_tasks ) {

		$existing_tasks = ADBC_Automation::instance()->tasks();

		$total_migrated_optimization_tasks = 0;

		foreach ( $optimization_tasks as $old_task_name => $old_task_details ) {

			if ( self::validate_old_automation_task_structure( $old_task_name, $old_task_details ) === false ) {
				continue;
			}

			$new_task = [ 
				'type' => 'general_cleanup',
				'name' => $old_task_name,
				'frequency' => self::FREQUENCY_MAPPING[ $old_task_details['repeat'] ],
				'start_datetime' => self::get_timestamp_from_old_date_and_time( $old_task_details['start_date'], $old_task_details['start_time'] ),
				'operations' => self::migrate_old_operations( $old_task_details['operations'] ),
				'active' => false, // Always deactivate the task
			];

			if ( self::is_task_already_exists( $new_task, $existing_tasks ) ) {
				continue;
			}

			if ( ADBC_Automation::instance()->create( $new_task ) !== null )
				$total_migrated_optimization_tasks++;

		}

		// If no optimization tasks were migrated, return 0
		if ( $total_migrated_optimization_tasks === 0 )
			return 0;

		// If all optimization tasks were migrated, return 1, otherwise if it's mixed, return 2
		return $total_migrated_optimization_tasks === count( $optimization_tasks ) ? 1 : 2;

	}

	/**
	 * Check if a task with the same name, type, frequency, start datetime and operations already exists in the existing tasks.
	 * 
	 * @param array $new_task The new task to check.
	 * @param array $existing_tasks The existing tasks to check against.
	 * 
	 * @return bool True if a task with the same name, type, frequency, start datetime and operations already exists, false if it doesn't
	 */
	private static function is_task_already_exists( $new_task, $existing_tasks ) {

		foreach ( $existing_tasks as $existing_task ) {
			if ( $existing_task['name'] === $new_task['name'] && $existing_task['type'] === $new_task['type'] && $existing_task['frequency'] === $new_task['frequency'] && $existing_task['start_datetime'] === $new_task['start_datetime'] && $existing_task['operations'] == $new_task['operations'] ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Get the old keep last value for item type.
	 * 
	 * @param string $item_type The item type.
	 * 
	 * @return int The old keep last value for item type.
	 */
	private static function get_old_keep_last_for_item_type( $item_type ) {

		$old_settings = get_option( 'aDBc_settings', [] );

		if ( isset( $old_settings['keep_last'] ) && is_array( $old_settings['keep_last'] ) ) {
			return isset( $old_settings['keep_last'][ $item_type ] ) ? intval( $old_settings['keep_last'][ $item_type ] ) : 0;
		}

		return 0;

	}

	/**
	 * Migrate the keep last settings.
	 * 
	 * @param array $old_keep_last_settings The old keep last settings.
	 * 
	 * @return int 0 if failed, 1 if success, 2 if some of the keep last settings were not migrated
	 */
	private static function migrate_keep_last_settings( $old_keep_last_settings ) {

		$new_keep_last_settings = [];
		$total_old_keep_last_settings_with_non_zero_value = 0;
		$total_old_keep_last_settings_with_value_of_0 = 0;

		foreach ( $old_keep_last_settings as $old_item_type => $old_value ) {

			// Skip 0 value, in the new structure we just don't store keep last settings for items types with a value of 0
			if ( intval( $old_value ) <= 0 ) {
				$total_old_keep_last_settings_with_value_of_0++;
				continue;
			}

			$total_old_keep_last_settings_with_non_zero_value++;

			if ( isset( self::OPERATIONS_MAPPING[ $old_item_type ] ) ) {

				$new_item_type = self::OPERATIONS_MAPPING[ $old_item_type ];
				$new_keep_last_settings[ $new_item_type ] = [ 
					'type' => 'days',
					'value' => intval( $old_value ),
				];

			}

		}

		// If all old keep last were skipped because they had a value of 0, return 1
		if ( count( $old_keep_last_settings ) === $total_old_keep_last_settings_with_value_of_0 )
			return 1;

		// if no new keep last is validated, return 0
		if ( count( $new_keep_last_settings ) === 0 )
			return 0;

		ADBC_General_Cleanup::set_keep_last( $new_keep_last_settings );

		// If some non zero value keep last were not migrated, return 2
		if ( $total_old_keep_last_settings_with_non_zero_value !== count( $new_keep_last_settings ) )
			return 2;

		// If all non zero value keep last were migrated, return 1
		return 1;

	}

	/**
	 * Migrate the manual corrections.
	 * 
	 * @return int 0 if failed, 1 if success, 2 if some of the manual corrections were not valid
	 */
	private static function migrate_manual_corrections() {

		$security_code = get_option( 'aDBc_security_folder_code' );
		$uploads = wp_upload_dir();
		$old_adbc_upload_dir = $uploads['basedir'] . '/adbc_uploads_' . $security_code;

		if ( ! ADBC_Files::instance()->exists( $old_adbc_upload_dir ) ) {
			return 0;
		}

		$old_cron_jobs_file_path = $old_adbc_upload_dir . "/tasks_corrected_manually.txt";
		$old_options_file_path = $old_adbc_upload_dir . "/options_corrected_manually.txt";
		$old_tables_file_path = $old_adbc_upload_dir . "/tables_corrected_manually.txt";

		$results = [];

		if ( ADBC_Files::instance()->exists( $old_cron_jobs_file_path ) ) {
			$manual_corrections = ADBC_Files::instance()->get_contents( $old_cron_jobs_file_path );
			$manual_corrections = json_decode( $manual_corrections, true );
			if ( is_array( $manual_corrections ) && ! empty( $manual_corrections ) ) {
				$results[] = self::write_old_version_manual_corrections_to_file( 'cron_jobs', $manual_corrections );
			}
		}

		if ( ADBC_Files::instance()->exists( $old_options_file_path ) ) {
			$manual_corrections = ADBC_Files::instance()->get_contents( $old_options_file_path );
			$manual_corrections = json_decode( $manual_corrections, true );
			if ( is_array( $manual_corrections ) && ! empty( $manual_corrections ) ) {
				$results[] = self::write_old_version_manual_corrections_to_file( 'options', $manual_corrections );
			}
		}

		if ( ADBC_Files::instance()->exists( $old_tables_file_path ) ) {
			$manual_corrections = ADBC_Files::instance()->get_contents( $old_tables_file_path );
			$manual_corrections = json_decode( $manual_corrections, true );
			if ( is_array( $manual_corrections ) && ! empty( $manual_corrections ) ) {
				$results[] = self::write_old_version_manual_corrections_to_file( 'tables', $manual_corrections );
			}
		}

		$number_of_successes = count( array_filter( $results, function ($result) {
			return $result === 1;
		} ) );
		$number_of_failures = count( array_filter( $results, function ($result) {
			return $result === 0;
		} ) );

		// If there are no results or all results are failures, return 0
		if ( empty( $results ) || ( $number_of_failures === count( $results ) ) )
			return 0;

		// If all results are successes, return 1
		if ( $number_of_successes === count( $results ) )
			return 1;

		// If the results are mixed, return 2
		return 2;

	}

	/**
	 * Uninstall the old versions.
	 * 
	 * @return int 0 if failed, 1 if success, 2 if some of the old versions were not uninstalled
	 */
	private static function uninstall_old_versions() {

		// Ensure current user has permission to remove plugins
		if ( ! current_user_can( 'delete_plugins' ) ) {
			return 0;
		}

		// Respect environments where file modifications are disabled
		if ( defined( 'DISALLOW_FILE_MODS' ) && constant( 'DISALLOW_FILE_MODS' ) ) {
			return 0;
		}

		// Load WordPress plugin management functions once
		if (
			! function_exists( 'delete_plugins' ) ||
			! function_exists( 'deactivate_plugins' ) ||
			! function_exists( 'is_plugin_active' ) ||
			! function_exists( 'is_plugin_active_for_network' )
		) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ADBC_Common_Utils::is_old_free_exists() )
			$plugins_files[] = 'advanced-database-cleaner/advanced-db-cleaner.php';
		if ( ADBC_Common_Utils::is_old_pro_exists() )
			$plugins_files[] = 'advanced-database-cleaner-pro/advanced-db-cleaner.php';

		$final_success = [];

		foreach ( $plugins_files as $plugin_file ) {

			// Deactivate if active (site or network) before deletion; silence hooks
			$network_wide = function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( $plugin_file );
			if ( function_exists( 'deactivate_plugins' ) ) {
				deactivate_plugins( [ $plugin_file ], false, $network_wide );
			}

			// Attempt deletion and log any failures
			if ( function_exists( 'delete_plugins' ) ) {
				$result = delete_plugins( [ $plugin_file ] );
				if ( is_wp_error( $result ) || $result === false || $result === null ) {
					$final_success[] = 0;
				} else {
					$final_success[] = 1;
				}
			}

		}

		// Remove the old version data from the database.
		self::delete_all_old_data();

		$number_of_successes = count( array_filter( $final_success, function ($result) {
			return $result === 1;
		} ) );
		$number_of_failures = count( array_filter( $final_success, function ($result) {
			return $result === 0;
		} ) );

		// If there are no results or all results are failures, return 0
		if ( empty( $final_success ) || ( $number_of_failures === count( $plugins_files ) ) )
			return 0;

		// If all results are successes, return 1, otherwise if it's mixed, return 2
		return $number_of_successes === count( $plugins_files ) ? 1 : 2;

	}

	/**
	 * Validate the old automation task structure.
	 * 
	 * @param string $old_task_name The old task name.
	 * @param array $old_task_details The old task details.
	 * 
	 * @return bool True if the old automation task structure is valid, false if it's not
	 */
	private static function validate_old_automation_task_structure( $old_task_name, $old_task_details ) {

		// Validate name against regex
		if ( ! is_string( $old_task_name ) || ! preg_match( '/^[a-zA-Z0-9_]+$/', $old_task_name ) ) {
			return false;
		}

		// Basic shape
		if ( ! is_array( $old_task_details ) || empty( $old_task_details ) ) {
			return false;
		}

		// Validate repeat/frequency
		$valid_repeats = array_keys( self::FREQUENCY_MAPPING );
		$repeat = $old_task_details['repeat'] ?? null;
		if ( ! is_string( $repeat ) || ! in_array( $repeat, $valid_repeats, true ) ) {
			return false;
		}

		// Validate date and time
		$start_date = $old_task_details['start_date'] ?? null;
		$start_time = $old_task_details['start_time'] ?? null;
		if ( ! is_string( $start_date ) || ! is_string( $start_time ) ) {
			return false;
		}
		$dt = DateTime::createFromFormat( 'Y-m-d H:i', trim( $start_date . ' ' . $start_time ), new DateTimeZone( 'UTC' ) );
		if ( ! ( $dt instanceof DateTime ) ) {
			return false;
		}

		// Validate active flag (0|1)
		if ( ! array_key_exists( 'active', $old_task_details ) ) {
			return false;
		}
		$active = (int) $old_task_details['active'];
		if ( $active !== 0 && $active !== 1 ) {
			return false;
		}

		// Validate operations/elements depending on task type
		$has_elements_to_clean = isset( $old_task_details['elements_to_clean'] ) && is_array( $old_task_details['elements_to_clean'] );
		$has_operations = isset( $old_task_details['operations'] ) && is_array( $old_task_details['operations'] );

		// Must have exactly one of them
		if ( ( $has_elements_to_clean ? 1 : 0 ) + ( $has_operations ? 1 : 0 ) !== 1 ) {
			return false;
		}

		$all_old_keys = array_keys( self::OPERATIONS_MAPPING );
		$cleaning_allowed = array_diff( $all_old_keys, [ 'optimize', 'repair' ] );

		if ( $has_elements_to_clean ) {
			if ( empty( $old_task_details['elements_to_clean'] ) ) {
				return false;
			}
			foreach ( $old_task_details['elements_to_clean'] as $item_key ) {
				if ( ! is_string( $item_key ) || ! in_array( $item_key, $cleaning_allowed, true ) ) {
					return false;
				}
			}
		}

		if ( $has_operations ) {
			if ( empty( $old_task_details['operations'] ) ) {
				return false;
			}
			foreach ( $old_task_details['operations'] as $op ) {
				if ( ! is_string( $op ) || ! in_array( $op, [ 'optimize', 'repair' ], true ) ) {
					return false;
				}
			}
		}

		return true;

	}

	/**
	 * Get the timestamp from old date and time.
	 *
	 * @param string $date_str The old date in format 'Y-m-d'.
	 * @param string $time_str The old time in format 'H:i'.
	 *
	 * @return int The UNIX timestamp (UTC). Falls back to current time on parse failure.
	 */
	private static function get_timestamp_from_old_date_and_time( $date_str, $time_str ) {

		$datetime_str = trim( $date_str . ' ' . $time_str );

		// Create a DateTime object in UTC
		$dt = DateTime::createFromFormat( 'Y-m-d H:i', $datetime_str, new DateTimeZone( 'UTC' ) );

		if ( $dt instanceof DateTime ) {
			return $dt->getTimestamp();
		}

		return time();

	}

	/**
	 * Migrate the old operations.
	 *
	 * Maps legacy operation keys to the new keys and attaches "keep last" when available.
	 *
	 * @param array $old_operations The old operations.
	 *
	 * @return array The new operations array keyed by new operation name; values are
	 *               either 'no_keep_last' or an array like ['type' => 'days', 'value' => int].
	 */
	private static function migrate_old_operations( $old_operations ) {

		$new_operations = [];

		foreach ( $old_operations as $old_operation ) {

			if ( isset( self::OPERATIONS_MAPPING[ $old_operation ] ) ) {
				$keep_last_value = self::get_old_keep_last_for_item_type( $old_operation );
				$keep_last = $keep_last_value !== 0 ? [ 'type' => 'days', 'value' => $keep_last_value ] : 'no_keep_last';
				$new_operations[ self::OPERATIONS_MAPPING[ $old_operation ] ] = $keep_last;
			}

		}

		return $new_operations;

	}

	/**
	 * Check if the old manual corrections exist.
	 * 
	 * @return bool True if the old manual corrections exist, false if they don't
	 */
	private static function is_old_manual_corrections_exists() {

		$security_code = get_option( 'aDBc_security_folder_code' );
		$uploads = wp_upload_dir();
		$old_adbc_upload_dir = $uploads['basedir'] . '/adbc_uploads_' . $security_code;

		if ( file_exists( $old_adbc_upload_dir ) ) {
			$files = [ 
				$old_adbc_upload_dir . '/tasks_corrected_manually.txt',
				$old_adbc_upload_dir . '/options_corrected_manually.txt',
				$old_adbc_upload_dir . '/tables_corrected_manually.txt',
			];

			foreach ( $files as $file_path ) {
				if ( file_exists( $file_path ) ) {
					$handle = fopen( $file_path, 'r' );
					if ( $handle ) {
						$line = fgets( $handle );
						fclose( $handle );
						if ( $line !== false ) {
							return true;
						}
					}
				}
			}
		}

		return false;

	}

	/**
	 * Write the old version manual corrections to the file.
	 * 
	 * @param string $items_type The type of items to migrate. "tables", "options", "cron_jobs", etc.
	 * @param array $manual_corrections The manual corrections.
	 * 
	 * @return int 0 if failed, 1 if success, 2 if some of the manual corrections were not valid
	 */
	public static function write_old_version_manual_corrections_to_file( $items_type, $manual_corrections ) {

		// Verify if there is a scan in progress. If there is, return an error.
		if ( ADBC_Scan_Utils::is_scan_exists( $items_type ) ) {
			return 0;
		}

		$scan_results_file_path = ADBC_Scan_Paths::get_scan_results_path( $items_type );
		$temp_results_file_path = ADBC_Scan_Paths::get_manual_categorization_results_temp_file_path( $items_type );

		// Create the scan results file if it doesn't exist.
		ADBC_Files::instance()->create_file( $scan_results_file_path );
		$scan_results_file_handle = ADBC_Files::instance()->get_file_handle( $scan_results_file_path, 'r' );
		if ( $scan_results_file_handle === false ) {
			return 0;
		}

		// Create the temp file to store the new manual corrections.
		$temp_result_file_handle = ADBC_Files::instance()->get_file_handle( $temp_results_file_path, 'w' );
		if ( $temp_result_file_handle === false ) {
			return 0;
		}

		// Remove the hardcoded items from the manual corrections.
		ADBC_Hardcoded_Items::instance()->remove_hardcoded_items_from_list( $manual_corrections, $items_type );

		$count_old_manual_corrections = count( $manual_corrections );

		// Convert the old manual corrections to the new format.
		$new_manual_corrections = [];
		foreach ( $manual_corrections as $item_name => $slug ) {

			if ( ! self::is_old_manual_correction_valid( $item_name, $slug ) )
				continue;

			[ $slug_name, $slug_type ] = explode( ':', $slug, 2 );
			$relation = [ 'm' => [ $slug_type . ':' . $slug_name ] ];
			$new_manual_correction_line = $item_name . '|' . json_encode( $relation );
			$new_manual_corrections[ $item_name ] = $new_manual_correction_line;

		}

		$items_count = 0;

		// Read the scan results file, edit or add the new manual corrections to the temp file.
		while ( ( $line = fgets( $scan_results_file_handle ) ) !== false ) {

			list( $item_name, $belong_to_json ) = ADBC_Scan_Utils::split_result_file_line( $line );
			if ( $item_name === false || $belong_to_json === false )
				continue;

			// Update the line with the new manual correction if it exists.
			if ( isset( $new_manual_corrections[ $item_name ] ) ) {
				fwrite( $temp_result_file_handle, $new_manual_corrections[ $item_name ] . "\n" );
				unset( $new_manual_corrections[ $item_name ] );
				$items_count++;
			} else {
				fwrite( $temp_result_file_handle, $line );
			}

		}

		// Add the remaining manual corrections to the scan results file.
		foreach ( $new_manual_corrections as $item_name => $new_manual_correction_line ) {
			fwrite( $temp_result_file_handle, $new_manual_correction_line . "\n" );
			$items_count++;
		}

		fclose( $scan_results_file_handle );
		fclose( $temp_result_file_handle );

		// Rename the temp file to the scan results file.
		if ( ADBC_Files::instance()->exists( $temp_results_file_path ) && ! rename( $temp_results_file_path, $scan_results_file_path ) ) {
			return 0;
		}

		// If the number of old manual corrections is greater than the number of new manual corrections, return 2.
		if ( $count_old_manual_corrections > $items_count ) {
			return 2;
		}

		return 1;

	}

	/**
	 * Validate the old manual correction format (e.g., {"item":"plugin-slug:p"}).
	 *
	 * Rules:
	 * - Value must be a string containing exactly one colon separating name and type
	 * - Type must be one of "p", "t", or "w"
	 * - For type "w", value must be exactly "w:w"
	 *
	 * @param string $item_name The item name (any non-empty string)
	 * @param mixed  $slug      The old-format slug (e.g., "plugin-slug:p")
	 *
	 * @return bool True when valid; false otherwise
	 */
	private static function is_old_manual_correction_valid( $item_name, $slug ) {

		if ( ! is_string( $item_name ) || $item_name === '' )
			return false;

		if ( ! is_string( $slug ) )
			return false;

		$slug = trim( $slug );
		$parts = explode( ':', $slug, 2 );
		if ( count( $parts ) !== 2 )
			return false;

		list( $slug_name, $slug_type ) = $parts;
		$slug_name = trim( $slug_name );
		$slug_type = trim( $slug_type );

		if ( $slug_type !== 'p' && $slug_type !== 't' && $slug_type !== 'w' )
			return false;

		// For WordPress core, expect exactly "w:w"
		if ( $slug_type === 'w' )
			return $slug_name === 'w';

		// The slug name should not contain any spaces
		return strpos( $slug_name, ' ' ) === false;

	}

	/**
	 * Delete the old free version data.
	 * 
	 * @return void
	 */
	private static function delete_old_free_version_data() {

		// Delete the old free version options
		delete_option( 'aDBc_settings' );
		delete_option( 'aDBc_clean_schedule' );
		delete_option( 'aDBc_optimize_schedule' );

		// Unschedule the old free version cron jobs
		wp_unschedule_hook( 'aDBc_clean_scheduler' );
		wp_unschedule_hook( 'aDBc_optimize_scheduler' );

	}

	/**
	 * Delete the old pro version data.
	 * 
	 * @return void
	 */
	private static function delete_old_pro_data() {

		// Delete folder containing scan results
		$aDBc_security_code = get_option( 'aDBc_security_folder_code' );
		$aDBc_upload_dir = wp_upload_dir();
		$aDBc_upload_dir = str_replace( '\\', '/', $aDBc_upload_dir['basedir'] ) . '/adbc_uploads_' . $aDBc_security_code;

		if ( file_exists( $aDBc_upload_dir ) ) {
			$dir = opendir( $aDBc_upload_dir );
			while ( ( $file = readdir( $dir ) ) !== false ) {
				if ( $file != '.' && $file != '..' ) {
					$entry_path = $aDBc_upload_dir . '/' . $file;
					if ( is_file( $entry_path ) ) {
						@unlink( $entry_path );
					}
				}
			}
			closedir( $dir );
			rmdir( $aDBc_upload_dir );
		}

		// Delete the old pro version options
		$array_items = array( 'options', 'tables', 'tasks' );

		foreach ( $array_items as $item ) {

			delete_option( 'aDBc_temp_last_iteration_' . $item );
			delete_option( 'aDBc_temp_still_searching_' . $item );
			delete_option( 'aDBc_temp_last_item_line_' . $item );
			delete_option( 'aDBc_temp_last_file_line_' . $item );
			delete_option( 'aDBc_last_search_ok_' . $item );
			delete_option( 'aDBc_temp_total_files_' . $item );
			delete_option( 'aDBc_temp_maybe_scores_' . $item );
			delete_option( 'aDBc_temp_currently_scanning_' . $item );
			delete_option( 'aDBc_temp_progress_scan_' . $item );
			delete_option( 'aDBc_temp_progress_files_preparation_' . $item );
			delete_option( 'aDBc_temp_last_collected_file_path_' . $item );
			delete_option( 'aDBc_temp_items_to_scan_' . $item );
			delete_option( 'aDBc_temp_scan_type_' . $item );
			delete_option( 'aDBc_temp_current_scan_step_' . $item );

		}

		delete_option( 'aDBc_security_folder_code' );
		delete_option( 'aDBc_edd_license_key' );
		delete_option( 'aDBc_edd_license_status' );

	}

	/**
	 * Delete all old versions data.
	 * 
	 * @return void
	 */
	private static function delete_all_old_data() {
		self::delete_old_pro_data();
		self::delete_old_free_version_data();
	}

	/**
	 * Check if the old license exists.
	 * 
	 * @return bool True if the old license exists, false if it doesn't
	 */
	private static function is_old_license_exists() {
		$old_edd_license_key_exists = get_option( 'aDBc_edd_license_key' );
		return $old_edd_license_key_exists !== false && $old_edd_license_key_exists !== '' && get_option( 'aDBc_settings' ) !== false;
	}

}
