<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC settings endpoints class.
 * 
 * This class provides the endpoints (controllers) for the settings routes.
 */
class ADBC_Settings_Endpoints {

	/**
	 * Update one or multiple ADBC settings in database by keys.
	 *
	 * @param WP_REST_Request $setting Setting keys and their values.
	 * @return WP_REST_Response Response.
	 */
	public static function update_settings( WP_REST_Request $settings ) {

		try {

			$settings = $settings->get_json_params();

			// Iterate through the settings array and validate each key and value.
			foreach ( $settings as $key => $value ) {

				$sanitized_key = sanitize_key( $key );

				// Validate key.
				if ( ! ADBC_Settings_Validator::is_valid_setting_key( $sanitized_key ) )
					return ADBC_Rest::error( "Invalid setting key.", ADBC_Rest::BAD_REQUEST );

				// Validate the value case by case.
				switch ( $sanitized_key ) {
					case 'left_menu':
					case 'tools_menu':
					case 'network_menu':
					case 'send_corrections_to_server':
					case 'reduce_cpu_usage':
					case 'addons_activity_enabled':
					case 'analytics_enabled':
					case 'show_tables_with_invalid_prefix':
					case 'sidebar_is_expanded':
					case 'prevent_taking_action_on_wp_items':
					case 'show_confirmation_on_dangerous_actions':
						if ( ! ADBC_Common_Validator::is_string_equals_0_or_1( "", $value ) )
							return ADBC_Rest::error( "Invalid setting value.", ADBC_Rest::BAD_REQUEST );
						break;
					case 'hidden_tabs':
						if ( ! ADBC_Settings_Validator::are_valid_hiddenable_tabs( "", $value ) )
							return ADBC_Rest::error( "Invalid setting value.", ADBC_Rest::BAD_REQUEST );
						break;
					case 'file_lines_batch':
					case 'file_content_chunks':
					case 'scan_max_execution_time':
						if ( ! ADBC_Settings_Validator::is_scan_setting_valid( $sanitized_key, $value ) )
							return ADBC_Rest::error( "Please ensure that the scan settings respect the indicated min and max acceptable values.", ADBC_Rest::BAD_REQUEST );
						break;
					case 'cpu_work_time_ms':
					case 'cpu_rest_time_ms':
						if ( ! ADBC_Settings_Validator::is_cpu_usage_time_valid( $sanitized_key, $value ) )
							return ADBC_Rest::error( "Please ensure that the CPU usage settings respect the indicated min and max acceptable values.", ADBC_Rest::BAD_REQUEST );
						break;
					case 'database_rows_batch':
					case 'sql_or_native_cleanup_method':
						if ( ! ADBC_Settings_Validator::is_performance_settings_valid( $sanitized_key, $value ) )
							return ADBC_Rest::error( "Please ensure that the performance settings respect the acceptable values.", ADBC_Rest::BAD_REQUEST );
						break;
					default:
						return ADBC_Rest::error( "This setting cannot be changed.", ADBC_Rest::BAD_REQUEST );
				}
			}

			ADBC_Settings::instance()->update_settings( $settings ); // Update setting in database if all keys and values are valid.
			return ADBC_Rest::success( "" );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Get specific ADBC setting by key.
	 *
	 * @param WP_REST_Request $setting_key Setting key.
	 * @return WP_REST_Response Response.
	 */
	public static function get_setting( WP_REST_Request $setting_key ) {

		try {

			$key = $setting_key->get_param( 'key' );
			$sanitized_key = sanitize_key( $key );

			// Validate key.
			if ( ! ADBC_Settings_Validator::is_valid_setting_key( $sanitized_key ) )
				return ADBC_Rest::error( "Invalid setting key.", ADBC_Rest::BAD_REQUEST );

			// Do not return sensitive settings like the security code.
			if ( $sanitized_key === 'security_code' )
				return ADBC_Rest::error( "Cannot get content of this setting!", ADBC_Rest::BAD_REQUEST );

			$value = ADBC_Settings::instance()->get_setting( $sanitized_key );
			return ADBC_Rest::success( "", [ 'value' => $value ] );

		} catch (Exception $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

}