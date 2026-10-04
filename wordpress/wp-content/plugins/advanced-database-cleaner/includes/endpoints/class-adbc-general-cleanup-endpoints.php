<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * General Cleanup Endpoints
 * 
 * This class provides the endpoints for general cleanup operations in WordPress.
 */
class ADBC_General_Cleanup_Endpoints {

	/**
	 * Retrieves general cleanup data based on the items type if provided, otherwise returns all general cleanup data.
	 * 
	 * Accepts:
	 * - Empty array [] to get all items
	 * - Array of item types ['revisions', 'auto_drafts'] to get only those items
	 * - String item type 'revisions' to get a single item (for backward compatibility)
	 * 
	 * @param WP_REST_Request $request The request object containing the items type if specified.
	 * 
	 * @return WP_REST_Response The response containing the general cleanup data.
	 */
	public static function get_general_data( WP_REST_Request $request ) {

		try {

			$items_type_param = $request->get_param( 'itemsType' );

			// Handle empty array - return all items
			if ( is_array( $items_type_param ) && empty( $items_type_param ) ) {
				return ADBC_Rest::success( '', ADBC_General_Cleanup::get_general_data() );
			}

			// Handle array of item types
			if ( is_array( $items_type_param ) && ! empty( $items_type_param ) ) {
				$validated_items_types = ADBC_Common_Validator::sanitize_items_types( $items_type_param );

				if ( empty( $validated_items_types ) ) {
					return ADBC_Rest::error( 'invalid items types.', ADBC_Rest::BAD_REQUEST );
				}

				return ADBC_Rest::success( '', ADBC_General_Cleanup::get_general_data( $validated_items_types ) );
			}

			// Handle string item type (backward compatibility and refresh after purge)
			$items_type = ADBC_Common_Validator::sanitize_items_type( $items_type_param );

			if ( $items_type === '' ) {
				return ADBC_Rest::success( '', ADBC_General_Cleanup::get_general_data() );
			}

			return ADBC_Rest::success( '', ADBC_General_Cleanup::get_general_data( $items_type ) );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Retrieves a list of items based on the provided filters.
	 * 
	 * @param WP_REST_Request $request The request object containing the filters.
	 * 
	 * @return WP_REST_Response The response containing the list of items.
	 */
	public static function get_items( WP_REST_Request $request ) {

		try {

			$args = ADBC_Common_Validator::sanitize_filters( $request );

			if ( $args['items_type'] === '' ) {
				return ADBC_Rest::error( 'invalid items type.', ADBC_Rest::BAD_REQUEST );
			}

			$list = ADBC_General_Cleanup::get_items( $args['items_type'], $args );

			return ADBC_Rest::success( '', $list );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Deletes items based on the provided items type and selected items.
	 * 
	 * @param WP_REST_Request $request The request object containing the items type and selected items.
	 * 
	 * @return WP_REST_Response The response indicating the success or failure of the deletion.
	 */
	public static function delete_items( WP_REST_Request $request ) {

		try {

			$items_type = ADBC_Common_Validator::sanitize_items_type( $request->get_param( 'itemsType' ) );

			if ( $items_type === '' ) {
				return ADBC_Rest::error( 'invalid items type.', ADBC_Rest::BAD_REQUEST );
			}

			$action_type = "delete_$items_type";

			$validated_selected_items = ADBC_Common_Validator::validate_endpoint_action_data( $action_type, $items_type, $request );

			if ( ! is_array( $validated_selected_items ) )
				return ADBC_Rest::error( $validated_selected_items, ADBC_Rest::BAD_REQUEST );

			$deleted = ADBC_General_Cleanup::delete_items( $items_type, $validated_selected_items );

			return ADBC_Rest::success( '', [ 'deleted' => $deleted ] );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Purges items based on the provided items type.
	 * 
	 * @param WP_REST_Request $request The request object containing the items type.
	 * 
	 * @return WP_REST_Response The response indicating the success or failure of the purge operation.
	 */
	public static function purge_items( WP_REST_Request $request ) {

		try {

			$items_type = ADBC_Common_Validator::sanitize_items_type( $request->get_param( 'itemsType' ) );

			if ( $items_type === '' ) {
				return ADBC_Rest::error( 'invalid items type.', ADBC_Rest::BAD_REQUEST );
			}

			$purged = ADBC_General_Cleanup::purge_items( $items_type );

			return ADBC_Rest::success( '', [ 'purged' => $purged ] );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Sets the "keep last" configuration for general cleanup.
	 * 
	 * @param WP_REST_Request $request The request object containing the keep last configuration.
	 * 
	 * @return WP_REST_Response The response indicating the success or failure of the operation.
	 */
	public static function set_keep_last( WP_REST_Request $request ) {

		try {

			$keep_last = $request->get_param( 'keepLast' );

			if ( ADBC_VERSION_TYPE === 'FREE' && is_array( $keep_last ) ) {
				foreach ( $keep_last as $value ) {
					if ( ( $value['type'] ?? null ) === 'items' ) {
						return ADBC_Rest::error(
							__( 'Cannot use retention by items in free version, please upgrade to premium.' ),
							ADBC_Rest::BAD_REQUEST,
							0,
							[ 
								"message_links" => [ 
									[ 
										"text" => __( 'Upgrade Now', 'advanced-database-cleaner' ),
										"url" => "https://sigmaplugin.com/downloads/wordpress-advanced-database-cleaner/?utm_source=adbc_notification_msg",
										"target" => "_blank",
									]
								]
							]
						);
					}
				}
			}

			if ( ! is_array( $keep_last ) || ! ADBC_Settings_Validator::is_keep_last_valid( 'keep_last', $keep_last ) ) {
				return ADBC_Rest::error( 'invalid keep last.', ADBC_Rest::BAD_REQUEST );
			}

			$updated = ADBC_General_Cleanup::set_keep_last( $keep_last );

			return ADBC_Rest::success( '', $updated );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Retrieves the "keep last" configuration for general cleanup.
	 * 
	 * @return WP_REST_Response The response containing the keep last configuration.
	 */
	public static function get_keep_last() {

		try {

			$keep_last = ADBC_General_Cleanup::get_keep_last();

			return ADBC_Rest::success( '', $keep_last );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Deletes the keep_last configuration for the specified items types.
	 * 
	 * @param WP_REST_Request $request The request object containing the items types.
	 * 
	 * @return WP_REST_Response The response indicating the success or failure of the deletion operation.
	 */
	public static function delete_keep_last( WP_REST_Request $request ) {

		try {

			$items_types = $request->get_param( 'itemsTypes' );

			if ( ! is_array( $items_types ) || empty( $items_types ) ) {
				return ADBC_Rest::error( 'invalid items types.', ADBC_Rest::BAD_REQUEST );
			}

			foreach ( $items_types as $items_type ) {
				if ( ADBC_Common_Validator::sanitize_items_type( $items_type ) === '' ) {
					// If any of the items types is invalid, return an error.
					return ADBC_Rest::error( 'invalid items type.', ADBC_Rest::BAD_REQUEST );
				}
			}

			$deleted = ADBC_General_Cleanup::delete_keep_last( $items_types );

			return ADBC_Rest::success( '', $deleted );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Activate auto count for specified items types.
	 * 
	 * @param WP_REST_Request $request The request object containing the items types.
	 * 
	 * @return WP_REST_Response The response indicating the success or failure of the activation operation.
	 */
	public static function activate_auto_count( WP_REST_Request $request ) {

		try {

			$items_types = $request->get_param( 'itemsTypes' );

			$validated_items_types = ADBC_Common_Validator::sanitize_items_types( $items_types );

			if ( empty( $validated_items_types ) ) {
				return ADBC_Rest::error( 'invalid items types.', ADBC_Rest::BAD_REQUEST );
			}

			$activated = ADBC_General_Cleanup::activate_auto_count( $validated_items_types );

			return ADBC_Rest::success( '', $activated );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Deactivate auto count for specified items types.
	 * 
	 * @param WP_REST_Request $request The request object containing the items types.
	 * 
	 * @return WP_REST_Response The response indicating the success or failure of the deactivation operation.
	 */
	public static function deactivate_auto_count( WP_REST_Request $request ) {

		try {

			$items_types = $request->get_param( 'itemsTypes' );

			$validated_items_types = ADBC_Common_Validator::sanitize_items_types( $items_types );

			if ( empty( $validated_items_types ) ) {
				return ADBC_Rest::error( 'invalid items types.', ADBC_Rest::BAD_REQUEST );
			}

			$deactivated = ADBC_General_Cleanup::deactivate_auto_count( $validated_items_types );

			return ADBC_Rest::success( '', $deactivated );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

}