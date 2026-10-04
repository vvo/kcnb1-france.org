<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Logs Endpoints.
 * 
 * This class provides the endpoints (controllers) for the logs routes.
 */
class ADBC_Logs_Endpoints {

	/**
	 * Get logs content.
	 *
	 * @param WP_REST_Request $log_object Log type to get its content ('debug' or 'wp-debug').
	 * @return WP_REST_Response The logs content.
	 */
	public static function get_logs_content( WP_REST_Request $log_object ) {

		try {

			$log_type = $log_object->get_param( 'log_type' );
			$sanitized_log_type = sanitize_key( $log_type );

			if ( $sanitized_log_type !== 'debug' && $sanitized_log_type !== 'wp-debug' )
				return ADBC_Rest::error( "Invalid log file type.", ADBC_Rest::BAD_REQUEST );

			$log_content = ADBC_Logging::get_log_content( $sanitized_log_type );

			if ( $log_content['success'] === false )
				return ADBC_Rest::error( $log_content['message'], ADBC_Rest::INTERNAL_SERVER_ERROR );

			return ADBC_Rest::success( "", [ 'content' => $log_content['content'] ] );

		} catch (Exception $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Clear log file content.
	 *
	 * @return WP_REST_Response Response.
	 */
	public static function clear_logs_content() {

		try {

			$cleared = ADBC_Logging::clear_log();

			if ( $cleared === false )
				return ADBC_Rest::error(
					__( 'Cannot clear the log file content.', 'advanced-database-cleaner' ), ADBC_Rest::INTERNAL_SERVER_ERROR,
					0,
					[ 
						'message_links' => [ 
							[ 
								'text' => __( 'Check the logs', 'advanced-database-cleaner' ),
								'tab_id' => 'info_and_logs',
								'sub_tab_id' => 'debug',
							],
						],
					]
				);

			return ADBC_Rest::success( "" );

		} catch (Exception $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

}