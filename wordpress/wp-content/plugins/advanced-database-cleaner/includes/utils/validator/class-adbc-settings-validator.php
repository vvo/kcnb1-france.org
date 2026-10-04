<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Settings validator class.
 * 
 * This class provides functions to validate and sanitize the plugin settings.
 */
class ADBC_Settings_Validator {

	private const MIN_FILE_AND_DATABASE_LINES_BATCHES = 100; // 100 lines of rows to process at once
	private const MAX_FILE_AND_DATABASE_LINES_BATCHES = 1000000; // 1 million lines or rows to process at once
	private const MIN_FILE_CONTENT_CHUNKS = 50; // 50KB
	private const MAX_FILE_CONTENT_CHUNKS = 10240; // 10MB
	private const MIN_MAX_EXECUTION_TIME = 30;
	private const MAX_MAX_EXECUTION_TIME = 300; // 5 minutes
	private const MIN_CPU_WORK_TIME_MS = 100; // 100 milliseconds
	private const MAX_CPU_WORK_TIME_MS = 1000; // 1 second
	private const MIN_CPU_REST_TIME_MS = 1; // 1 millisecond
	private const MAX_CPU_REST_TIME_MS = 100; // 100 milliseconds

	// Holds all valid settings keys.
	private static $default_settings_keys = array();

	// Holds the names of the tabs that can be hidden in the settings page.
	private static $hiddenable_tabs = array(
		'tables',
		'options',
		'posts_meta',
		'users_meta',
		'transients',
		'cron_jobs',
		'woocommerce',
		'automation',
		'analytics',
		'addons_activity',
		'info_and_logs',
		'post_types',
	);

	/**
	 * Checks if the given key is a valid setting key.
	 *
	 * @param string $key The setting key to check.
	 * @return bool True if the key is valid, false otherwise.
	 */
	public static function is_valid_setting_key( $key ) {
		if ( empty( self::$default_settings_keys ) ) {
			self::$default_settings_keys = ADBC_Settings::instance()->get_settings_keys();
		}
		return in_array( $key, self::$default_settings_keys, true );
	}

	/**
	 * Checks if the given value is a valid installed date string.
	 *
	 * @param string $key The setting key. (not used in this function, but kept for consistency)
	 * @param string $date_string The date string to check.
	 * @return bool True if the date string is valid, false otherwise.
	 */
	public static function is_valid_date( $key, $date_string ) {
		$date = DateTime::createFromFormat( "d/m/Y", $date_string );
		$date_errors = DateTime::getLastErrors();
		return $date !== false
			&& empty( $date_errors['warning_count'] )
			&& $date->format( 'd/m/Y' ) === $date_string;
	}

	/**
	 * Checks if the given array of tab names are all valid hiddenable tabs.
	 *
	 * @param string $key The setting key.
	 * @param array $tab_names The array of tab names to check.
	 * @return bool True if all tab names are valid, false otherwise.
	 */
	public static function are_valid_hiddenable_tabs( $key, $tab_names ) {
		if ( ! is_array( $tab_names ) )
			return false;

		foreach ( $tab_names as $tab_name ) {
			if ( ! in_array( $tab_name, self::$hiddenable_tabs, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if the given value is a valid scan setting value. Used mainly for file_lines_batch, database_rows_batch, file_content_chunks, and scan_max_execution_time.
	 *
	 * @param string $key The scan setting key.
	 * @param mixed $value The value to check.
	 * @return bool True if the value is valid, false otherwise.
	 */
	public static function is_scan_setting_valid( $key, $value ) {

		switch ( $key ) {
			case 'file_lines_batch':
				return ADBC_Common_Validator::is_number_between_min_and_max( $value, self::MIN_FILE_AND_DATABASE_LINES_BATCHES, self::MAX_FILE_AND_DATABASE_LINES_BATCHES );
			case 'file_content_chunks':
				return ADBC_Common_Validator::is_number_between_min_and_max( $value, self::MIN_FILE_CONTENT_CHUNKS, self::MAX_FILE_CONTENT_CHUNKS );
			case 'scan_max_execution_time':
				// For the max execution time, we allow 0 as a special case to indicate that the user wants the default value.
				// If not 0, we check if the number set it is between the min and max values.
				if ( $value === 0 )
					return true;
				return ADBC_Common_Validator::is_number_between_min_and_max( $value, self::MIN_MAX_EXECUTION_TIME, self::MAX_MAX_EXECUTION_TIME );
			default:
				return false;
		}
	}

	/**
	 * Checks if the given CPU throttle value is valid (milliseconds).
	 * 
	 * @param string $key The setting key.
	 * @param int $value The value to check.
	 * @return bool True if the value is valid, false otherwise.
	 */
	public static function is_cpu_usage_time_valid( $key, $value ) {

		switch ( $key ) {
			case 'cpu_work_time_ms':
				return ADBC_Common_Validator::is_number_between_min_and_max( $value, self::MIN_CPU_WORK_TIME_MS, self::MAX_CPU_WORK_TIME_MS );
			case 'cpu_rest_time_ms':
				return ADBC_Common_Validator::is_number_between_min_and_max( $value, self::MIN_CPU_REST_TIME_MS, self::MAX_CPU_REST_TIME_MS );
			default:
				return false;
		}
	}

	/**
	 * Checks if the given array of notifications keys are all valid notifications keys.
	 *
	 * @param string $key The setting key (not used in this function, but kept for consistency).
	 * @param array $notifications_keys The array of notifications keys to check.
	 * @return bool True if all notification keys are valid, false otherwise.
	 */
	public static function are_valid_notifications_keys( $key, $notifications_keys ) {
		if ( ! is_array( $notifications_keys ) )
			return false;

		$valid_notifications_keys = ADBC_Notifications::get_notifications_keys(); // Call the static method to get the valid notifications keys.

		foreach ( $notifications_keys as $notification_key => $dismissed ) {

			// If the notification key is not valid, return false.
			if ( ! in_array( $notification_key, $valid_notifications_keys, true ) ) {

				return false;
			}

			// If the dismissed format is not valid, return false.
			if ( ! is_array( $dismissed ) || count( $dismissed ) !== 1 || ! array_key_exists( 'dismissed', $dismissed ) || ! is_bool( $dismissed['dismissed'] ) ) {
				return false;
			}

		}

		return true;
	}

	/**
	 * Checks if the given value is a valid api scan balance value.
	 *
	 * @param string $key The setting key. (not used in this function, but kept for consistency)
	 * @param string $value The value to check.
	 * @return bool True if the value is valid, false otherwise.
	 */
	public static function is_api_scan_balance_valid( $key, $api_scan_balance ) {
		if ( ! is_array( $api_scan_balance ) )
			return false;
		foreach ( $api_scan_balance as $scan_key => $value ) {
			if ( ! in_array( $scan_key, array( 'quota', 'usage', 'ttl', 'total_quota', 'total_consumed', 'updated_at' ), true ) )
				return false;
			if ( ! is_int( $value ) && $value < 0 )
				return false;
		}
		return true;
	}

	/**
	 * Checks if the given value is a valid security code.
	 *
	 * @param string $key The setting key. (not used in this function, but kept for consistency)
	 * @param string $security_code The security code to check.
	 * @return bool True if the security code is valid, false otherwise.
	 */
	public static function is_security_code_valid( $key, $security_code ) {
		if ( ! is_string( $security_code ) )
			return false;
		if ( strlen( $security_code ) !== ADBC_SECURITY_CODE_LENGTH )
			return false;
		if ( ! preg_match( '/^[0-9a-z_]+$/', $security_code ) )
			return false;
		return true;
	}

	/**
	 * Checks if the given value is a valid execution data array. Used in analytics and addons activity validation settings.
	 *
	 * @param string $key The setting key. (not used in this function, but kept for consistency)
	 * @param array $execution_data The execution data array to check.
	 * @return bool True if the execution data is valid, false otherwise.
	 */
	public static function is_execution_data_valid( $key, $execution_data ) {

		if ( ! is_array( $execution_data ) )
			return false;

		if ( ! array_key_exists( 'success', $execution_data ) || ! array_key_exists( 'fail', $execution_data ) )
			return false;

		// If there are any other keys in the array, we don't want them.
		if ( count( $execution_data ) > 2 )
			return false;

		$success_value = $execution_data['success'];
		$fail_value = $execution_data['fail'];

		// Allowed values are 0 or a timestamp (integer) of 10 digits.
		if ( $success_value !== 0 && ( strlen( $success_value ) !== 10 || ! ctype_digit( (string) $success_value ) ) )
			return false;

		if ( $fail_value !== 0 && ( strlen( $fail_value ) !== 10 || ! ctype_digit( (string) $fail_value ) ) )
			return false;

		return true;

	}

	/**
	 * Checks if the given value is a valid keep last setting.
	 *
	 * @param string $key The setting key. (not used in this function, but kept for consistency)
	 * @param array $keep_last The keep last setting to check.
	 * @return bool True if the keep last setting is valid, false otherwise.
	 */
	public static function is_keep_last_valid( $key, $keep_last ) {

		if ( ! is_array( $keep_last ) )
			return false;

		foreach ( $keep_last as $items_type => $value ) {
			if ( ! ADBC_Cleanup_Type_Registry::is_valid_items_type( $items_type ) )
				return false;
			if ( ! is_array( $value ) || count( $value ) !== 2 )
				return false;
			if ( ! array_key_exists( 'type', $value ) || ! array_key_exists( 'value', $value ) )
				return false;
			if ( ! in_array( $value['type'], array( 'days', 'items' ), true ) )
				return false;
			if ( ! is_int( $value['value'] ) || $value['value'] <= 0 || $value['value'] > 10000 )
				return false;
		}

		return true;

	}

	/**
	 * Checks if the given value is a valid performance settings.
	 *
	 * @param string $key The setting key. (not used in this function, but kept for consistency)
	 * @param array $value The value to check.
	 * @return bool True if the value is valid, false otherwise.
	 */
	public static function is_performance_settings_valid( $key, $value ) {
		switch ( $key ) {
			case 'database_rows_batch':
				return ADBC_Common_Validator::is_number_between_min_and_max( $value, self::MIN_FILE_AND_DATABASE_LINES_BATCHES, self::MAX_FILE_AND_DATABASE_LINES_BATCHES );
			case 'sql_or_native_cleanup_method':
				return in_array( $value, [ 'sql', 'native' ], true );
			default:
				return false;
		}
	}

	/**
	 * Checks if the given value is a valid general cleanup auto count setting.
	 *
	 * @param string $key The setting key. (not used in this function, but kept for consistency)
	 * @param array $value The value to check.
	 * 
	 * @return bool True if the value is valid, false otherwise.
	 */
	public static function is_general_cleanup_auto_count_valid( $key, $value ) {

		if ( ! is_array( $value ) )
			return false;

		foreach ( $value as $items_type )
			if ( ! ADBC_Cleanup_Type_Registry::is_valid_items_type( $items_type ) )
				return false;

		return true;

	}

}