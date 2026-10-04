<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Transients Endpoints.
 * 
 * This class provides the endpoints (controllers) for the transients routes.
 */
class ADBC_Transients_Endpoints {

	/**
	 * Get the transients list.
	 *
	 * @param WP_REST_Request $filters_request The request with the filters.
	 * @return WP_REST_Response The list of transients.
	 */
	public static function get_transients_list( WP_REST_Request $filters_request ) {

		try {

			$filters = ADBC_Common_Validator::sanitize_filters( $filters_request );
			$rest_response = ADBC_Transients::get_transients_list( $filters );
			return $rest_response;

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Edit scan results of transients.
	 *
	 * @param WP_REST_Request $request_data The request with the transients to edit.
	 * @return WP_REST_Response The response.
	 */
	public static function edit_scan_results_transients( WP_REST_Request $request_data ) {

		try {

			return ADBC_Scan_Utils::edit_scan_results( $request_data, 'edit_scan_results_transients', 'transients' );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Set autoload to "yes" for transients.
	 * 
	 * @param WP_REST_Request $request_data The request with the transients to set autoload to "yes".
	 * @return WP_REST_Response The response.
	 */
	public static function set_autoload_to_yes_transients( WP_REST_Request $request_data ) {

		try {

			$validation_answer = ADBC_Common_Validator::validate_endpoint_action_data( "set_autoload_to_yes_transients", "transients", $request_data );

			// If $validation_answer is not an array, it means that the validation failed and we have an error message.
			if ( ! is_array( $validation_answer ) )
				return ADBC_Rest::error( $validation_answer, ADBC_Rest::BAD_REQUEST );

			$cleaned_transients = $validation_answer;

			if ( ADBC_Settings::instance()->get_setting( 'prevent_taking_action_on_wp_items' ) === '1' ) {

				$cleaned_transients = ADBC_Hardcoded_Items::instance()->exclude_hardcoded_items_from_selected_items( $validation_answer, 'transients', "wp" );

				if ( ADBC_VERSION_TYPE === 'PREMIUM' )
					$cleaned_transients = ADBC_Scan_Utils::exclude_r_wp_items_from_selected_items( $cleaned_transients, 'transients' );

				if ( empty( $cleaned_transients ) )
					return ADBC_Rest::error(
						__( 'The selected transients could not have their autoload edited because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
						ADBC_Rest::BAD_REQUEST,
						0,
						[ 
							"message_links" => [ 
								[ 
									"text" => __( 'Check setting', 'advanced-database-cleaner' ),
									"tab_id" => "settings",
									"anchor_id" => "other_settings",
									"setting_id" => "prevent_taking_action_on_wp_items"
								]
							]
						]
					);
			}

			$grouped = ADBC_Selected_Items_Validator::group_selected_items_by_site_id( $cleaned_transients );
			$not_processed = ADBC_Transients::set_autoload_to_yes( $grouped );

			if ( count( $cleaned_transients ) < count( $validation_answer ) )
				return ADBC_Rest::success(
					__( 'Some transients were updated successfully; others were skipped because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
					count( $not_processed ),
					[ 
						"message_links" => [ 
							[ 
								"text" => __( 'Check setting', 'advanced-database-cleaner' ),
								"tab_id" => "settings",
								"anchor_id" => "other_settings",
								"setting_id" => "prevent_taking_action_on_wp_items"
							]
						]
					]
				);

			return ADBC_Rest::success( "", count( $not_processed ) );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Set autoload to "no" for transients.
	 * 
	 * @param WP_REST_Request $request_data The request with the transients to set autoload to "no".
	 * @return WP_REST_Response The response.
	 */
	public static function set_autoload_to_no_transients( WP_REST_Request $request_data ) {

		try {

			$validation_answer = ADBC_Common_Validator::validate_endpoint_action_data( "set_autoload_to_no_transients", "transients", $request_data );

			// If $validation_answer is not an array, it means that the validation failed and we have an error message.
			if ( ! is_array( $validation_answer ) )
				return ADBC_Rest::error( $validation_answer, ADBC_Rest::BAD_REQUEST );

			$cleaned_transients = $validation_answer;

			if ( ADBC_Settings::instance()->get_setting( 'prevent_taking_action_on_wp_items' ) === '1' ) {

				$cleaned_transients = ADBC_Hardcoded_Items::instance()->exclude_hardcoded_items_from_selected_items( $validation_answer, 'transients', "wp" );

				if ( ADBC_VERSION_TYPE === 'PREMIUM' )
					$cleaned_transients = ADBC_Scan_Utils::exclude_r_wp_items_from_selected_items( $cleaned_transients, 'transients' );

				if ( empty( $cleaned_transients ) )
					return ADBC_Rest::error(
						__( 'The selected transients could not have their autoload edited because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
						ADBC_Rest::BAD_REQUEST,
						0,
						[ 
							"message_links" => [ 
								[ 
									"text" => __( 'Check setting', 'advanced-database-cleaner' ),
									"tab_id" => "settings",
									"anchor_id" => "other_settings",
									"setting_id" => "prevent_taking_action_on_wp_items"
								]
							]
						]
					);
			}

			$grouped = ADBC_Selected_Items_Validator::group_selected_items_by_site_id( $cleaned_transients );
			$not_processed = ADBC_Transients::set_autoload_to_no( $grouped );

			if ( count( $cleaned_transients ) < count( $validation_answer ) ) {
				return ADBC_Rest::success(
					__( 'Some transients were updated successfully; others were skipped because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
					count( $not_processed ),
					[ 
						"message_links" => [ 
							[ 
								"text" => __( 'Check setting', 'advanced-database-cleaner' ),
								"tab_id" => "settings",
								"anchor_id" => "other_settings",
								"setting_id" => "prevent_taking_action_on_wp_items"
							]
						]
					]
				);
			}

			return ADBC_Rest::success( "", count( $not_processed ) );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Delete transients.
	 * 
	 * @param WP_REST_Request $request_data The request with the transients to delete.
	 * @return WP_REST_Response The response.
	 */
	public static function delete_transients( WP_REST_Request $request_data ) {

		try {

			// Verify if there is a scan in progress. If there is, return an error to prevent conflicts.
			if ( ADBC_VERSION_TYPE === 'PREMIUM' && ADBC_Scan_Utils::is_scan_exists( 'transients' ) )
				return ADBC_Rest::error( __( 'A scan is in progress. Please wait until it finishes before performing this action.', 'advanced-database-cleaner' ), ADBC_Rest::BAD_REQUEST );

			$validation_answer = ADBC_Common_Validator::validate_endpoint_action_data( "delete_transients", "transients", $request_data );

			// If $validation_answer is not an array, it means that the validation failed and we have an error message.
			if ( ! is_array( $validation_answer ) )
				return ADBC_Rest::error( $validation_answer, ADBC_Rest::BAD_REQUEST );

			$cleaned_transients = $validation_answer;

			if ( ADBC_Settings::instance()->get_setting( 'prevent_taking_action_on_wp_items' ) === '1' ) {

				$cleaned_transients = ADBC_Hardcoded_Items::instance()->exclude_hardcoded_items_from_selected_items( $validation_answer, 'transients', "wp" );

				if ( ADBC_VERSION_TYPE === 'PREMIUM' )
					$cleaned_transients = ADBC_Scan_Utils::exclude_r_wp_items_from_selected_items( $cleaned_transients, 'transients' );

				if ( empty( $cleaned_transients ) )
					return ADBC_Rest::error(
						__( 'The selected transients could not be deleted because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
						ADBC_Rest::BAD_REQUEST,
						0,
						[ 
							"message_links" => [ 
								[ 
									"text" => __( 'Check setting', 'advanced-database-cleaner' ),
									"tab_id" => "settings",
									"anchor_id" => "other_settings",
									"setting_id" => "prevent_taking_action_on_wp_items"
								]
							]
						]
					);
			}

			$grouped = ADBC_Selected_Items_Validator::group_selected_items_by_site_id( $cleaned_transients );
			$not_processed = ADBC_Transients::delete_transients( $grouped );

			// Delete the transients from the scan results
			if ( ADBC_VERSION_TYPE === 'PREMIUM' ) {
				$transients_names = array_column( $cleaned_transients, 'name' ); // Create an array containing only the transients names.
				ADBC_Scan_Utils::update_scan_results_file_after_deletion( 'transients', $transients_names, $not_processed );
			}

			if ( count( $cleaned_transients ) < count( $validation_answer ) ) {
				return ADBC_Rest::success(
					__( 'Some transients were deleted successfully; others were skipped because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
					count( $not_processed ),
					[ 
						"message_links" => [ 
							[ 
								"text" => __( 'Check setting', 'advanced-database-cleaner' ),
								"tab_id" => "settings",
								"anchor_id" => "other_settings",
								"setting_id" => "prevent_taking_action_on_wp_items"
							]
						]
					]
				);
			}

			return ADBC_Rest::success( "", count( $not_processed ) );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Count the total number of big transients.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function count_big_transients() {
		try {
			return ADBC_Rest::success( "", ADBC_Transients::count_big_transients() );
		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}
	}

	/**
	 * Count the total number of transients that are not scanned.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function count_total_not_scanned_transients() {
		try {
			return ADBC_Rest::success( "", ADBC_Transients::count_total_not_scanned_transients() );
		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}
	}

	/**
	 * Count the total number of expired transients.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function count_expired_transients() {
		try {
			return ADBC_Rest::success( "", ADBC_Cleanup_Type_Registry::handler( 'expired_transients' )->count()['count'] );
		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}
	}

}




