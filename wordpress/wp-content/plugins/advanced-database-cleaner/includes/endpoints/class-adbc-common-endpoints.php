<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Common Endpoints.
 * 
 * This class provides the endpoints (controllers) for the common routes.
 */
class ADBC_Common_Endpoints {

	private const MAX_VALUE_TO_DEEP_DECODE = 100 * 1024 * 1024; // 100 MB

	/**
	 * Get the value of a column from a table. 
	 * 
	 * @return WP_REST_Response The value of the column with its type and info.
	 */
	public static function get_column_value_from_table( WP_REST_Request $request_data ) {

		try {

			// Get params
			$items_type = $request_data->get_param( 'itemsType' );
			$site_id = $request_data->get_param( 'siteId' );
			$row_id = $request_data->get_param( 'rowId' );
			$transient_found_in = $request_data->get_param( 'transientFoundIn' );

			$answer = ADBC_Common_Validator::validate_get_column_value_endpoint_data( $items_type, $site_id, $row_id, $transient_found_in );

			if ( $answer['success'] === false )
				return ADBC_Rest::error( $answer['message'], ADBC_Rest::BAD_REQUEST );

			$table = '`' . esc_sql( $answer['data']['table_name'] ) . '`';
			$col = '`' . esc_sql( $answer['data']['column_name'] ) . '`';
			$pk = '`' . esc_sql( $answer['data']['column_id'] ) . '`';

			global $wpdb;
			$sql = $wpdb->prepare(
				"SELECT {$col} AS `value`
					FROM {$table}
					WHERE {$pk} = %d
					LIMIT 1
				",
				$row_id
			);
			$value = $wpdb->get_var( $sql );
			$value_type = ADBC_Common_Utils::get_value_type( $value );
			$pretty_json = "";

			// Prepare a pretty JSON representation of the value if it is not too big.
			$too_big = ( $value !== null && strlen( $value ) > self::MAX_VALUE_TO_DEEP_DECODE );

			if ( ! $too_big ) {

				// We take into account only serialized_array for unserialize(). We de not unserialize serialized_object for security reasons.
				if ( $value_type === 'serialized_data' ) {
					$decoded = ADBC_Common_Utils::safe_unserialize_array( $value );
				} elseif ( $value_type === 'json_array' || $value_type === 'json_object' ) {
					$decoded = json_decode( $value, true );
				}

				if ( isset( $decoded ) && $decoded !== false ) {
					$decoded = ADBC_Common_Utils::deep_decode( $decoded ); // Recursively decode the structure to handle nested arrays/objects.
					$pretty_json = json_encode(
						$decoded,
						JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
					);
				}
			}

			return ADBC_Rest::success( "", [ "value" => $value, "type" => $value_type, "pretty_json" => $pretty_json ] );

		} catch (Exception $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Dismiss a notification.
	 * 
	 * @return WP_REST_Response The response indicating success or failure.
	 */
	public static function dismiss_notification( WP_REST_Request $request_data ) {

		try {

			// Get params
			$notification_key = sanitize_key( $request_data->get_param( 'notificationKey' ) );

			// Check if the notification is already dismissed
			if ( ADBC_Notifications::instance()->is_notification_dismissed( $notification_key ) ) {
				return ADBC_Rest::success( "" );
			}

			// Dismiss the notification
			if ( ! ADBC_Notifications::instance()->dismiss_notification( $notification_key ) ) {
				return ADBC_Rest::error( __( 'Failed to dismiss the notification.', 'advanced-database-cleaner' ), ADBC_Rest::BAD_REQUEST );
			}

			// Return success response
			return ADBC_Rest::success( "" );

		} catch (Exception $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Delay the rating notice.
	 * 
	 * @return WP_REST_Response The response indicating success or failure.
	 */
	public static function delay_rating_notice( WP_REST_Request $request_data ) {

		try {

			// get params
			$notification_key = sanitize_key( $request_data->get_param( 'notificationKey' ) );

			// Check if the notification key is valid
			if ( $notification_key !== 'rating_notice' ) {
				return ADBC_Rest::error( 'Invalid notification key.', ADBC_Rest::BAD_REQUEST );
			}

			// Delay the rating notice
			if ( ! ADBC_Notifications::instance()->delay_rating_notice() ) {
				return ADBC_Rest::error( 'Failed to delay the rating notice.', ADBC_Rest::BAD_REQUEST );
			}

			// Return success response
			return ADBC_Rest::success( "" );
		} catch (Exception $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Delay the LTD migration notice by 7 days (remind me in a week).
	 *
	 * @return WP_REST_Response The response indicating success or failure.
	 */
	public static function delay_ltd_migration_notice( WP_REST_Request $request_data ) {

		try {

			// get params
			$notification_key = sanitize_key( $request_data->get_param( 'notificationKey' ) );

			// Check if the notification key is valid
			if ( $notification_key !== 'ltd_migration_notice' ) {
				return ADBC_Rest::error( 'Invalid notification key.', ADBC_Rest::BAD_REQUEST );
			}

			// Delay the LTD migration notice
			if ( ! ADBC_Notifications::instance()->delay_ltd_migration_notice() ) {
				return ADBC_Rest::error( 'Failed to delay the LTD migration notice.', ADBC_Rest::BAD_REQUEST );
			}

			// Return success response
			return ADBC_Rest::success( '' );
		} catch (Exception $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Get all schedule frequencies.
	 * 
	 * @return WP_REST_Response The response.
	 */
	public static function get_all_schedule_frequencies() {

		try {

			return ADBC_Rest::success( "", wp_get_schedules() );

		} catch (Exception $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Get last week database size for free version.
	 * It only returns the current database size for each day of the last week.
	 * 
	 * @return WP_REST_Response The response with the database size for the last week.
	 */
	public static function get_last_week_database_size_for_free_version() {

		try {

			// Get the current database size
			$current_database_size = ADBC_Database::get_database_size_sql( false );

			// Create an array with all days of the last week initialized to 0
			$result = [];
			for ( $i = 6; $i >= 0; $i-- ) {
				$date = date( 'Y-m-d', strtotime( "-$i days" ) );
				$result[ $date ] = $current_database_size;
			}

			return ADBC_Rest::success( '', $result );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}

	}

}