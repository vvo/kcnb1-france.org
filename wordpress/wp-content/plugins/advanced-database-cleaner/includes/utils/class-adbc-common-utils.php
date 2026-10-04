<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC common utils class.
 * 
 * This class provides common utils functions.
 */
class ADBC_Common_Utils {

	/**
	 * Format bytes.
	 *
	 * @param int $bytes The number of bytes.
	 * @param int $precision The number of decimal places to include in the formatted string.
	 * 
	 * @return string Formatted bytes.
	 */
	public static function format_bytes( $bytes, $precision = 2 ) {

		$absolute_bytes = abs( $bytes );
		$formatted_value = $bytes;
		$size_unit = "B";

		if ( $absolute_bytes >= 1024 ** 3 ) {
			$formatted_value = $bytes / 1024 ** 3;
			$size_unit = "GB";
		} else if ( $absolute_bytes >= 1024 ** 2 ) {
			$formatted_value = $bytes / 1024 ** 2;
			$size_unit = "MB";
		} else if ( $absolute_bytes >= 1024 ) {
			$formatted_value = $bytes / 1024;
			$size_unit = "KB";
		}

		// Truncate to specified decimals without rounding up
		$multiplier = pow( 10, $precision );
		$formatted_value = floor( $formatted_value * $multiplier ) / $multiplier;

		return "$formatted_value $size_unit";

	}

	/**
	 * Convert size to bytes.
	 *
	 * @param string $size The size to convert.
	 * @param string $unit The unit of the size (e.g., KB, MB, GB).
	 * 
	 * @return int Size in bytes.
	 */
	public static function convert_size_to_bytes( $size, $unit ) {

		$size = intval( $size );

		switch ( strtoupper( $unit ) ) {
			case 'KB':
				return $size * 1024;
			case 'MB':
				return $size * 1024 * 1024;
			case 'GB':
				return $size * 1024 * 1024 * 1024;
			default:
				return $size;
		}

	}

	/**
	 * Convert the post_max_size php setting size format to bytes.
	 * 
	 * @param string $from The post_max_size value (e.g., 8M, 16G).
	 * 
	 * @return int The post_max_size value in bytes.
	 */
	public static function convert_post_max_size_to_bytes( $from ) {

		if ( ! is_string( $from ) || $from === '' ) {
			return 0;
		}

		$trimmed = trim( $from );
		$last = strtoupper( substr( $trimmed, -1 ) );

		// If last char is a unit, remove it and parse the number
		if ( in_array( $last, array( 'K', 'M', 'G' ), true ) ) {
			$number = (float) substr( $trimmed, 0, -1 );
		} else {
			// No unit, treat the whole string as a number
			return (float) $trimmed;
		}

		switch ( $last ) {
			case 'K':
				return $number * 1024;
			case 'M':
				return $number * 1024 * 1024;
			case 'G':
				return $number * 1024 * 1024 * 1024;
		}

		return 0;
	}


	/**
	 * Truncate a string to a specific length.
	 *
	 * @param string $string The string to truncate.
	 * @param int $max_length The maximum length of the string.
	 * @param string $append The string to append to the truncated string.
	 * 
	 * @return string The truncated string.
	 */
	public static function truncate_string( $string, $max_length = 100, $append = '...' ) {

		$truncated_string = $string;

		if ( strlen( $string ) > $max_length ) {
			$truncated_string = substr( $string, 0, $max_length ) . $append;
		}

		return $truncated_string;

	}

	/**
	 * Format a date string to a friendly, localized format.
	 *
	 * @param string $date_string The date string to format.
	 * @param string $date_format The format of the input date string (PHP DateTime format).
	 *
	 * @return string The formatted date string.
	 */
	public static function format_date_friendly( $date_string, $date_format = 'd/m/Y' ) {

		$date = DateTime::createFromFormat( $date_format, $date_string );

		// Invalid date: keep original behavior.
		if ( ! $date ) {
			return $date_string;
		}

		$timestamp = $date->getTimestamp();

		// Translators can adjust the display format.
		// Example output: "December 10, 2025".
		$friendly_format = _x(
			'F j, Y',
			'Friendly date format (e.g. December 10, 2025)',
			'advanced-database-cleaner'
		);

		if ( function_exists( 'wp_date' ) ) {
			return wp_date( $friendly_format, $timestamp );
		}

		// Fallback for very old WordPress versions.
		return date_i18n( $friendly_format, $timestamp );
	}

	/**
	 * Format a timestamp to a friendly, localized format.
	 *
	 * @param int|string|null $timestamp The timestamp to format.
	 *
	 * @return string The formatted date string, or empty string if timestamp is invalid.
	 */
	public static function format_timestamp_friendly( $timestamp ) {

		// Handle empty or invalid timestamps.
		if ( empty( $timestamp ) || ! is_numeric( $timestamp ) ) {
			return '';
		}

		$timestamp = (int) $timestamp;

		// Validate timestamp range (reasonable Unix timestamp range).
		if ( $timestamp < 0 || $timestamp > 2147483647 ) {
			return '';
		}

		// Translators can adjust the display format.
		// Example output: "December 10, 2025 3:45 PM".
		$friendly_format = _x(
			'F j, Y g:i A',
			'Friendly date and time format (e.g. December 10, 2025 3:45 PM)',
			'advanced-database-cleaner'
		);

		if ( function_exists( 'wp_date' ) ) {
			return wp_date( $friendly_format, $timestamp );
		}

		// Fallback for very old WordPress versions.
		return date_i18n( $friendly_format, $timestamp );
	}

	/**
	 * Detect the type of a value.
	 *
	 * @param mixed $value The value to detect.
	 * 
	 * @return string The detected type.
	 */
	public static function get_value_type( $value ) {

		/* 1. Empty string after CAST or manual entry */
		if ( $value === null || $value === '' ) {
			return 'empty_string';
		}

		/* 2. Boolean-flavoured strings */
		$lc = strtolower( trim( $value ) );
		if ( in_array( $lc, [ 'true', 'false' ], true ) ) {
			return 'boolean_string';
		}

		/* 3. Numeric strings */
		if ( is_numeric( $value ) ) {
			return strpos( $value, '.' ) !== false ? 'float_string' : 'integer_string';
		}

		/* 4. Serialized PHP payloads */
		if ( is_serialized( $value ) ) {
			return 'serialized_data';
		}

		/* 5. JSON blobs */
		$t = trim( $value );
		if (
			( $t[0] === '{' && substr( $t, -1 ) === '}' ) ||
			( $t[0] === '[' && substr( $t, -1 ) === ']' )
		) {
			if ( mb_check_encoding( $t, 'UTF-8' ) ) {
				$decoded = json_decode( $t );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					return is_array( $decoded ) ? 'json_array' : 'json_object';
				}
			}
		}

		/* 6. Fallback */
		return 'string';
	}

	/**
	 * Safely unserialize a string that is known to contain a serialized *array*.
	 * – Rejects objects and references by passing `allowed_classes => false`.
	 * – Returns false on failure or when the payload is not a serialized array.
	 *
	 * @param string $str  The serialized value.
	 * @return array|false Decoded array, or false on error.
	 */
	public static function safe_unserialize_array( $str ) {

		// Quick sanity check
		if ( ! is_serialized( $str ) )
			return false;

		// Use PHP's allowed_classes flag (>=7.0) to block objects
		$decoded = @unserialize( $str, [ 'allowed_classes' => false ] );

		return is_array( $decoded ) ? $decoded : false;
	}

	/**
	 * Recursively decode any JSON-or-serialized blobs found inside a value.
	 *
	 * Rules applied depth-first:
	 *  1. If the value is a JSON string → json_decode() (assoc array).
	 *  2. Else if the value is a PHP-serialized string → unserialize().
	 *  3. Arrays / objects are walked recursively.
	 *  4. All other scalars are returned untouched.
	 *
	 * @param mixed $data The value to examine.
	 * @return mixed Fully decoded structure.
	 */
	public static function deep_decode( $data ) {

		// 1. Handle scalar strings: try JSON, then serialized.
		if ( is_string( $data ) ) {

			$trim = trim( $data );

			/* JSON test: quick wrapper check before json_decode() */
			if (
				$trim !== '' &&
				(
					( $trim[0] === '{' && substr( $trim, -1 ) === '}' ) ||
					( $trim[0] === '[' && substr( $trim, -1 ) === ']' )
				)
			) {
				$decoded = json_decode( $trim, true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					return self::deep_decode( $decoded );
				}
			}

			/* Try PHP-serialized (arrays only, objects disabled) */
			if ( is_serialized( $data ) ) {

				$decoded = @unserialize( $data, [ 'allowed_classes' => false ] );

				// Accept only arrays; everything else is left as-is
				if ( is_array( $decoded ) ) {
					return self::deep_decode( $decoded );
				}
			}

			return $data; // plain string
		}

		// 2. Recurse through arrays
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				$data[ $k ] = self::deep_decode( $v );
			}
			return $data;
		}

		// 4. Objects, Integers, floats, booleans, null → return as-is
		return $data;
	}

	/**
	 * Parse a date string into a DateTime object based on a specified format.
	 * This function attempts to create a DateTime object from the given date string
	 * using the specified format. If the date string is not valid according to the format,
	 * it returns null.
	 * 
	 * @param string $date
	 * @param string $format
	 * 
	 * @return bool|DateTime|null
	 */
	public static function parse_date( $date, string $format ): ?DateTime {
		$obj = DateTime::createFromFormat( $format, $date );
		$err = DateTime::getLastErrors() ?: [ 'warning_count' => 0, 'error_count' => 0 ];

		return ( $obj && 0 === $err['warning_count'] && 0 === $err['error_count'] )
			? $obj
			: null;
	}

	/**
	 * Get the added custom ADBC schedule frequencies.
	 * 
	 * @return array<string>
	 */
	public static function get_adbc_schedule_frequencies() {

		$all_frequencies = wp_get_schedules();
		$adbc_frequencies = [];
		foreach ( $all_frequencies as $key => $value ) {
			if ( strpos( $key, 'adbc_' ) === 0 ) {
				$adbc_frequencies[ $key ] = $value;
			}
		}

		return array_keys( $adbc_frequencies );

	}

	/**
	 * Check if a new free version (>= 4.0.0) exists.
	 * 
	 * @return bool True if a new free version exists, false otherwise.
	 */
	public static function is_new_free_version_installed() {

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
	 * Get the last n days ending today.
	 * 
	 * @param int $n The number of days to get.
	 * 
	 * @return array An array of date strings in 'Y-m-d' format.
	 */
	public static function last_n_days_ending_today( $n ) {

		$n = max( 1, (int) $n );
		$today = new DateTime( 'today' );
		$start = clone $today;
		$start->modify( '-' . ( $n - 1 ) . ' days' );
		$out = [];
		$cursor = clone $start;

		while ( $cursor <= $today ) {
			$out[] = $cursor->format( 'Y-m-d' );
			$cursor->modify( '+1 day' );
		}

		return $out;

	}

	/**
	 * Check if the old free version exists.
	 * 
	 * @return bool True if the old free version exists, false otherwise.
	 */
	public static function is_old_free_exists() {

		// Ensure plugin functions are loaded
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		$free_slug = 'advanced-database-cleaner/advanced-db-cleaner.php';

		if ( isset( $plugins[ $free_slug ] ) ) {
			$free_version = $plugins[ $free_slug ]['Version'];

			// Compare version
			if ( version_compare( $free_version, '4.0.0', '<' ) ) {
				return true;
			}

		}

		return false;

	}

	/**
	 * Check if a new free version (>= 4.0.0) exists.
	 * 
	 * @return bool True if a new free version exists, false otherwise.
	 */
	public static function is_new_free_exists() {

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
	 * Check if the pro version is installed.
	 * 
	 * @return bool True if the pro version is installed, false otherwise.
	 */
	public static function is_old_pro_exists() {

		// Ensure plugin functions are loaded
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		$pro_slug = 'advanced-database-cleaner-pro/advanced-db-cleaner.php';
		if ( isset( $plugins[ $pro_slug ] ) ) {
			$pro_version = $plugins[ $pro_slug ]['Version'];

			// Compare version
			if ( version_compare( $pro_version, '4.0.0', '<' ) ) {
				return true;
			}

		}

		return false;

	}

	public static function is_old_pro_data_exists() {

		// We consider the old pro data exists if both the security folder and the settings options are not empty, as these are the main components of the old pro version data.
		$security_folder = get_option( 'aDBc_security_folder_code' );
		$settings = get_option( 'aDBc_settings' );
		return ! empty( $security_folder ) && ! empty( $settings );

	}

	/**
	 * Check if the premium version is installed.
	 * 
	 * @return bool True if the premium version is installed, false otherwise.
	 */
	public static function is_premium_exists() {

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
	 * Executes the old plugin version deactivation cleaning.
	 * 
	 * @return void
	 */
	public static function old_plugin_version_deactivation_cleaning() {

		// Unschedule the optimization and cleaning schedules
		wp_unschedule_hook( 'aDBc_optimize_scheduler' );
		wp_unschedule_hook( 'aDBc_clean_scheduler' );

		// Deactivate all cleaning tasks at once
		$cleaning_tasks = get_option( 'aDBc_clean_schedule' );
		if ( is_array( $cleaning_tasks ) && ! empty( $cleaning_tasks ) ) {
			foreach ( $cleaning_tasks as $task_name => $task_info ) {
				$cleaning_tasks[ $task_name ]['active'] = 0;
			}
			update_option( 'aDBc_clean_schedule', $cleaning_tasks, false );
		}

		// Deactivate all optimization tasks at once
		$optimize_schedules = get_option( 'aDBc_optimize_schedule' );
		if ( is_array( $optimize_schedules ) && ! empty( $optimize_schedules ) ) {
			foreach ( $optimize_schedules as $task_name => $task_info ) {
				$optimize_schedules[ $task_name ]['active'] = 0;
			}
			update_option( 'aDBc_optimize_schedule', $optimize_schedules, false );
		}

	}

	/**
	 * Mask a license key for display (e.g., show first and last 4 characters only)
	 *
	 * @param string|null $key The license key.
	 * @return string Masked license key.
	 */
	public static function mask_license_key( $key ) {

		if ( empty( $key ) )
			return '';

		$key = (string) $key;
		$length = strlen( $key );

		// Fully mask very short keys
		if ( $length <= 8 ) {
			return str_repeat( '*', $length );
		}

		$first = substr( $key, 0, 4 );
		$last = substr( $key, -4 );
		$middle_length = max( 0, $length - 8 );
		$middle = str_repeat( '*', $middle_length );

		return $first . $middle . $last;
	}

	/**
	 * Strip the WordPress transient prefix from a transient name.
	 * 
	 * Remove the `_site_transient_` or `_transient_` prefix from the given
	 * transient key and return the normalized key. If no prefix is found,
	 * return the key as-is.
	 * 
	 * @param string $transient_name Raw transient name.
	 * @return string Transient name without its prefix.
	 */
	public static function strip_transient_prefix( $transient_name ) {

		// Check `_site_transient_` first (longest and contains "_transient_")
		if ( strpos( $transient_name, '_site_transient_' ) === 0 )
			return substr( $transient_name, strlen( '_site_transient_' ) );

		// Check `_transient_`
		if ( strpos( $transient_name, '_transient_' ) === 0 )
			return substr( $transient_name, strlen( '_transient_' ) );

		// No prefix found, return unchanged
		return $transient_name;

	}

}