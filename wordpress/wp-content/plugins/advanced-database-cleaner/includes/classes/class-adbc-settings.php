<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC settings class.
 * 
 * This class provides the plugin settings functions.
 */
class ADBC_Settings extends ADBC_Singleton {


	// Holds the default settings for the plugin that are accepted to be updated in the database with their validators.
	private $default_settings = array();

	/**
	 * Stores the plugin settings locally to avoid multiple database calls.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct();
		$this->prepare_default_settings(); // Initialize the default settings and their validators.
		$this->load_settings(); // Load settings from the database into the local attribute $settings and validate them.
	}

	/**
	 * Initialize the default settings and their validators.
	 *
	 * @return void
	 */
	private function prepare_default_settings() {

		$this->default_settings = [ 
			'installed_on' => [ 
				'default' => date( "d/m/Y" ),
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_valid_date'
			],
			'left_menu' => [ 
				'default' => '1',
				'validator_class' => 'ADBC_Common_Validator',
				'validator_method' => 'is_string_equals_0_or_1'
			],
			'tools_menu' => [ 
				'default' => '0',
				'validator_class' => 'ADBC_Common_Validator',
				'validator_method' => 'is_string_equals_0_or_1'
			],
			'network_menu' => [ 
				'default' => is_multisite() ? '1' : '0',
				'validator_class' => 'ADBC_Common_Validator',
				'validator_method' => 'is_string_equals_0_or_1'
			],
			'hidden_tabs' => [ 
				'default' => [],
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'are_valid_hiddenable_tabs'
			],
			'send_corrections_to_server' => [ 
				'default' => '0',
				'validator_class' => 'ADBC_Common_Validator',
				'validator_method' => 'is_string_equals_0_or_1'
			],
			'analytics_enabled' => [ 
				'default' => '1',
				'validator_class' => 'ADBC_Common_Validator',
				'validator_method' => 'is_string_equals_0_or_1'
			],
			'analytics_execution' => [ 
				'default' => [ "success" => 0, "fail" => 0 ],
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_execution_data_valid'
			],
			'addons_activity_enabled' => [ 
				'default' => '1',
				'validator_class' => 'ADBC_Common_Validator',
				'validator_method' => 'is_string_equals_0_or_1'
			],
			'addons_activity_execution' => [ 
				'default' => [ "success" => 0, "fail" => 0 ],
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_execution_data_valid'
			],
			'show_tables_with_invalid_prefix' => [ 
				'default' => '1',
				'validator_class' => 'ADBC_Common_Validator',
				'validator_method' => 'is_string_equals_0_or_1'
			],
			'sidebar_is_expanded' => [ 
				'default' => '1',
				'validator_class' => 'ADBC_Common_Validator',
				'validator_method' => 'is_string_equals_0_or_1'
			],
			'file_lines_batch' => [ 
				'default' => 10000,
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_scan_setting_valid'
			],
			'database_rows_batch' => [ 
				'default' => 100000,
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_performance_settings_valid'
			],
			'file_content_chunks' => [ 
				'default' => 1024 * 1, // (for 1024 KB)
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_scan_setting_valid'
			],
			'scan_max_execution_time' => [ 
				'default' => 0,
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_scan_setting_valid'
			],
			'reduce_cpu_usage' => [ 
				'default' => '0',
				'validator_class' => 'ADBC_Common_Validator',
				'validator_method' => 'is_string_equals_0_or_1'
			],
			'cpu_work_time_ms' => [ 
				'default' => 1000,
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_cpu_usage_time_valid'
			],
			'cpu_rest_time_ms' => [ 
				'default' => 10,
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_cpu_usage_time_valid'
			],
			'api_scan_balance' => [ 
				'default' => [],
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_api_scan_balance_valid'
			],
			'security_code' => [ 
				'default' => $this->generate_security_code(),
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_security_code_valid'
			],
			'notifications' => [ 
				'default' => [],
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'are_valid_notifications_keys'
			],
			'keep_last' => [ 
				'default' => self::default_keep_last(),
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_keep_last_valid'
			],
			'rating_notice_date' => [ 
				'default' => date( "d/m/Y" ),
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_valid_date'
			],
			'ltd_migration_reminder_date' => [ 
				'default' => '01/01/2000', // Past date so notice shows until user dismisses or delays
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_valid_date'
			],
			'free_migration_done' => [ 
				'default' => '0',
				'validator_class' => 'ADBC_Common_Validator',
				'validator_method' => 'is_string_equals_0_or_1'
			],
			'sql_or_native_cleanup_method' => [ 
				'default' => 'sql',
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_performance_settings_valid'
			],
			'prevent_taking_action_on_wp_items' => [ 
				'default' => '1',
				'validator_class' => 'ADBC_Common_Validator',
				'validator_method' => 'is_string_equals_0_or_1'
			],
			'show_confirmation_on_dangerous_actions' => [ 
				'default' => '1',
				'validator_class' => 'ADBC_Common_Validator',
				'validator_method' => 'is_string_equals_0_or_1'
			],
			'general_cleanup_auto_count' => [ 
				'default' => [],
				'validator_class' => 'ADBC_Settings_Validator',
				'validator_method' => 'is_general_cleanup_auto_count_valid'
			],
		];
	}

	/**
	 * Retrieve settings from the database and validate them.
	 *
	 * @return void
	 */
	private function load_settings() {

		global $wpdb;

		// Acquire a MySQL advisory lock to prevent concurrent settings initialization.
		// When the settings option is missing (e.g. after deletion), multiple parallel requests
		// would each generate a different security_code, causing multiple upload folders to be created.
		// The lock serializes initialization so only the first process generates and saves the settings;
		// subsequent processes will read the already-persisted values.
		$lock_name = 'adbc_settings_init';
		$lock_timeout = 2; // 2 seconds
		$got_lock = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT GET_LOCK(%s, %d)", $lock_name, $lock_timeout ) );

		$stored_settings = get_option( 'adbc_plugin_settings', [] );
		$stored_settings = is_array( $stored_settings ) ? $stored_settings : [];

		$db_needs_update = false;

		// Loop through all default settings and check if they exist in the database.
		foreach ( $this->default_settings as $key => $config ) {

			if ( ! array_key_exists( $key, $stored_settings ) ) { // If the setting does not exist in the database, add it with its default value.
				if ( $key === 'general_cleanup_auto_count' ) {
					$value = ADBC_Cleanup_Type_Registry::get_default_auto_count_items_types();
				} else {
					$value = $config['default'];
				}
				$db_needs_update = true;
			} else { // If the setting exists, validate it.
				$value = $stored_settings[ $key ];
				$validator_class = $config['validator_class'];
				$validator_method = $config['validator_method'];
				if ( ! call_user_func( [ $validator_class, $validator_method ], $key, $value ) ) {
					// Reset to default if validation fails.
					if ( $key === 'general_cleanup_auto_count' ) {
						$value = ADBC_Cleanup_Type_Registry::get_default_auto_count_items_types();
					} else {
						$value = $config['default'];
					}
					$db_needs_update = true;
				}
			}

			$this->settings[ $key ] = $value;
		}

		// Check if there is at least a setting in the DB that is not in the default settings. In this case, we need to update the DB to remove it.
		foreach ( $stored_settings as $key => $value ) {
			if ( ! array_key_exists( $key, $this->default_settings ) ) {
				$db_needs_update = true;
				break;
			}
		}

		// Make sure at least one menu is shown in the admin menu.
		if ( is_multisite() ) {
			if ( $this->settings['left_menu'] === '0' && $this->settings['tools_menu'] === '0' && $this->settings['network_menu'] === '0' ) {
				$this->settings['network_menu'] = '1';
				$db_needs_update = true;
			}
		} else {
			if ( $this->settings['left_menu'] === '0' && $this->settings['tools_menu'] === '0' ) {
				$this->settings['left_menu'] = '1';
				$db_needs_update = true;
			}
		}

		// Save if any settings were initialized with defaults
		if ( $db_needs_update )
			$this->update_settings_in_db();

		// Release the advisory lock now that settings are persisted.
		if ( $got_lock )
			$wpdb->query( $wpdb->prepare( "SELECT RELEASE_LOCK(%s)", $lock_name ) );

	}

	/**
	 * Update settings in the database by keys. We assume all settings with their values have been validated before calling this function.
	 *
	 * @param array $new_settings Array of new settings to update.
	 * @return bool True if the update was successful, false otherwise.
	 */
	public function update_settings( $new_settings ) {
		foreach ( $new_settings as $key => $value ) {
			$this->settings[ $key ] = $value;
		}
		return $this->update_settings_in_db();
	}

	/**
	 * Update settings in the database
	 * 
	 * @return bool True if the update was successful, false otherwise.
	 */
	private function update_settings_in_db() {
		return update_option( 'adbc_plugin_settings', $this->settings, false );
	}

	/**
	 * Returns the default settings keys for the plugin from the variable $default_settings (not the database).
	 *
	 * @return array The default settings keys.
	 */
	public function get_settings_keys() {
		return array_keys( $this->default_settings );
	}

	/**
	 * Get all ADBC settings that are stored in the database.
	 *
	 * @return array Settings array or empty array if not found.
	 */
	public function get_settings( $return_sensitive_settings = true ) {

		$settings = $this->settings;

		// Return all settings except the security code if the parameter is set to false.
		if ( $return_sensitive_settings === false ) {

			// Unset security code
			unset( $settings['security_code'] );
			return $settings;
		}

		return $settings;
	}

	/**
	 * Get a specific ADBC setting by key.
	 *
	 * @param string $key Setting key.
	 * @return mixed Setting value or null if not found.
	 */
	public function get_setting( $key ) {
		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : null;
	}

	/**
	 * Generate a new ADBC uploads folder security code.
	 *
	 * @return string The generated security code.
	 */
	private function generate_security_code() {
		$permitted_chars = '00112233445566778899abcdefghijklmnopqrstuvwxyz___';
		$security_code = substr( str_shuffle( $permitted_chars ), 0, ADBC_SECURITY_CODE_LENGTH );
		return $security_code;
	}

	/**
	 * Returns the recommended default keep-last rules for Action Scheduler data.
	 *
	 * These rules are seeded on new installations. Existing installations receive
	 * missing rules once through the 4.2.0 migration in load_settings().
	 *
	 * @return array<string, array{type: string, value: int}>
	 */
	private static function default_keep_last() {
		return [ 
			'actionscheduler_completed_actions' => [ 'type' => 'days', 'value' => 30 ],
			'actionscheduler_failed_actions' => [ 'type' => 'days', 'value' => 30 ],
			'actionscheduler_canceled_actions' => [ 'type' => 'days', 'value' => 14 ],
			'actionscheduler_completed_logs' => [ 'type' => 'days', 'value' => 14 ],
			'actionscheduler_failed_logs' => [ 'type' => 'days', 'value' => 30 ],
			'actionscheduler_canceled_logs' => [ 'type' => 'days', 'value' => 14 ],
			'actionscheduler_orphan_logs' => [ 'type' => 'days', 'value' => 14 ],
		];
	}

}
