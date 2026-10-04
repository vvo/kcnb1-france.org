<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Post Types Endpoints.
 * 
 * This class provides the endpoints (controllers) for the post types routes.
 */
class ADBC_Post_Types_Endpoints {

	/**
	 * Get the post types list.
	 *
	 * @param WP_REST_Request $filters_request The request with the filters.
	 * @return WP_REST_Response The list of post types.
	 */
	public static function get_post_types_list( WP_REST_Request $filters_request ) {

		try {

			$filters = ADBC_Common_Validator::sanitize_filters( $filters_request );
			$rest_response = ADBC_Post_Types::get_post_types_list( $filters );

			return $rest_response;

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * List the posts of a given post type in a given site (paginated).
	 *
	 * @param WP_REST_Request $request_data The request with the post type name, site ID, and pagination filters.
	 * @return WP_REST_Response The paginated list of posts.
	 */
	public static function list_posts_by_post_type( WP_REST_Request $request_data ) {

		try {

			$filters = ADBC_Common_Validator::sanitize_filters( $request_data );

			$post_type = sanitize_text_field( $request_data->get_param( 'postTypeName' ) );
			$site_id = absint( $request_data->get_param( 'siteId' ) );
			$site_prefix = ADBC_Sites::instance()->get_prefix_from_site_id( $site_id );

			if ( empty( $post_type ) || $site_prefix === null )
				return ADBC_Rest::error( 'Invalid params.', ADBC_Rest::BAD_REQUEST );

			$data = ADBC_Post_Types::get_posts_rows( $site_id, $post_type, $filters );

			return ADBC_Rest::success( "", $data );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Delete all posts of the selected post types.
	 *
	 * @param WP_REST_Request $request_data The request with the post types to purge.
	 * @return WP_REST_Response The response.
	 */
	public static function delete_posts_by_post_type( WP_REST_Request $request_data ) {

		try {

			if ( ADBC_VERSION_TYPE === 'PREMIUM' && ADBC_Scan_Utils::is_scan_exists( 'post_types' ) )
				return ADBC_Rest::error( __( 'A scan is in progress. Please wait until it finishes before performing this action.', 'advanced-database-cleaner' ), ADBC_Rest::BAD_REQUEST );

			$validation_answer = ADBC_Common_Validator::validate_endpoint_action_data( "delete_post_types", "post_types", $request_data );

			if ( ! is_array( $validation_answer ) )
				return ADBC_Rest::error( $validation_answer, ADBC_Rest::BAD_REQUEST );

			$cleaned_post_types = $validation_answer;

			if ( ADBC_Settings::instance()->get_setting( 'prevent_taking_action_on_wp_items' ) === '1' ) {

				$cleaned_post_types = ADBC_Hardcoded_Items::instance()->exclude_hardcoded_items_from_selected_items( $validation_answer, 'post_types', "wp" );

				if ( ADBC_VERSION_TYPE === 'PREMIUM' )
					$cleaned_post_types = ADBC_Scan_Utils::exclude_r_wp_items_from_selected_items( $cleaned_post_types, 'post_types' );

				if ( empty( $cleaned_post_types ) )
					return ADBC_Rest::error(
						__( 'The selected post types could not be purged because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
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

			$grouped = ADBC_Selected_Items_Validator::group_selected_items_by_site_id( $cleaned_post_types );
			$not_processed = ADBC_Post_Types::delete_posts( $grouped );

			if ( count( $cleaned_post_types ) < count( $validation_answer ) ) {
				return ADBC_Rest::success(
					__( 'Some post types were purged successfully; others were skipped because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
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
	 * Edit scan results of post types.
	 *
	 * @param WP_REST_Request $request_data The request with the post types to edit.
	 * @return WP_REST_Response The response.
	 */
	public static function edit_scan_results_post_types( WP_REST_Request $request_data ) {

		try {

			return ADBC_Scan_Utils::edit_scan_results( $request_data, 'edit_scan_results_post_types', 'post_types' );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Count the total number of post types that are not scanned.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function count_total_not_scanned_post_types() {

		try {
			return ADBC_Rest::success( "", ADBC_Post_Types::count_total_not_scanned_post_types() );
		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}
	}

	/**
	 * Count the total number of non-public post types with a large number of posts.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function count_total_large_non_public_post_types() {

		try {
			return ADBC_Rest::success( "", ADBC_Post_Types::count_total_large_non_public_post_types() );
		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

}
