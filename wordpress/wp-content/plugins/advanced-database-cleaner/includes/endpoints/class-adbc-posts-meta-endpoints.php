<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Posts Meta Endpoints.
 * 
 * This class provides the endpoints (controllers) for the posts meta routes.
 */
class ADBC_Posts_Meta_Endpoints {

	/**
	 * Get the posts meta list.
	 *
	 * @param WP_REST_Request $filters_request The request with the filters.
	 * @return WP_REST_Response The list of posts meta.
	 */
	public static function get_posts_meta_list( WP_REST_Request $filters_request ) {

		try {

			$filters = ADBC_Common_Validator::sanitize_filters( $filters_request );
			$rest_response = ADBC_Posts_Meta::get_posts_meta_list( $filters );
			return $rest_response;

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Edit scan results of posts meta.
	 *
	 * @param WP_REST_Request $request_data The request with the posts meta to edit.
	 * @return WP_REST_Response The response.
	 */
	public static function edit_scan_results_posts_meta( WP_REST_Request $request_data ) {

		try {

			return ADBC_Scan_Utils::edit_scan_results( $request_data, 'edit_scan_results_posts_meta', 'posts_meta' );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Delete posts meta.
	 * 
	 * @param WP_REST_Request $request_data The request with the posts meta to delete.
	 * @return WP_REST_Response The response.
	 */
	public static function delete_posts_meta( WP_REST_Request $request_data ) {

		try {

			// Verify if there is a scan in progress. If there is, return an error to prevent conflicts.
			if ( ADBC_VERSION_TYPE === 'PREMIUM' && ADBC_Scan_Utils::is_scan_exists( 'posts_meta' ) )
				return ADBC_Rest::error( __( 'A scan is in progress. Please wait until it finishes before performing this action.', 'advanced-database-cleaner' ), ADBC_Rest::BAD_REQUEST );

			$validation_answer = ADBC_Common_Validator::validate_endpoint_action_data( "delete_posts_meta", "posts_meta", $request_data );

			// If $validation_answer is not an array, it means that the validation failed and we have an error message.
			if ( ! is_array( $validation_answer ) )
				return ADBC_Rest::error( $validation_answer, ADBC_Rest::BAD_REQUEST );

			$cleaned_posts_meta = $validation_answer;

			if ( ADBC_Settings::instance()->get_setting( 'prevent_taking_action_on_wp_items' ) === '1' ) {

				$cleaned_posts_meta = ADBC_Hardcoded_Items::instance()->exclude_hardcoded_items_from_selected_items( $validation_answer, 'posts_meta', "wp" );

				if ( ADBC_VERSION_TYPE === 'PREMIUM' )
					$cleaned_posts_meta = ADBC_Scan_Utils::exclude_r_wp_items_from_selected_items( $cleaned_posts_meta, 'posts_meta' );

				if ( empty( $cleaned_posts_meta ) )
					return ADBC_Rest::error(
						__( 'The selected post meta could not be deleted because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
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

			$grouped = ADBC_Selected_Items_Validator::group_selected_items_by_site_id( $cleaned_posts_meta );
			$not_processed = ADBC_Posts_Meta::delete_posts_meta( $grouped );

			// Delete the posts meta from the scan results
			if ( ADBC_VERSION_TYPE === 'PREMIUM' ) {
				$posts_meta_names = array_column( $cleaned_posts_meta, 'name' ); // Create an array containing only the posts meta names.
				ADBC_Scan_Utils::update_scan_results_file_after_deletion( 'posts_meta', $posts_meta_names, $not_processed );
			}

			if ( count( $cleaned_posts_meta ) < count( $validation_answer ) ) {
				return ADBC_Rest::success(
					__( 'Some post meta was deleted successfully; others were skipped because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
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
	 * Count the total number of big posts meta in all sites.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function count_big_posts_meta() {

		try {
			return ADBC_Rest::success( "", ADBC_Posts_Meta::count_big_posts_meta() );
		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Count the total number of posts meta that are not scanned.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function count_total_not_scanned_posts_meta() {

		try {
			return ADBC_Rest::success( "", ADBC_Posts_Meta::count_total_not_scanned_posts_meta() );
		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Count the total number of duplicated posts meta.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function count_duplicated_posts_meta() {

		try {
			return ADBC_Rest::success( "", ADBC_Posts_Meta::count_duplicated_posts_meta() );
		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Count the total number of unused posts meta.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function count_unused_posts_meta() {

		try {
			return ADBC_Rest::success( "", ADBC_Posts_Meta::count_unused_posts_meta() );
		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

}