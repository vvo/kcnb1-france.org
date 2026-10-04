<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC_Automation_Endpoints class.
 *
 * This class defines the REST API endpoints for managing automation tasks.
 */
class ADBC_Automation_Endpoints {

	/**
	 * Get the list of all automation tasks.
	 * 
	 * @return WP_REST_Response
	 */
	public static function list_tasks( WP_REST_Request $request ) {

		try {

			// Ensure the integrity of automation tasks only here because it will be always called before the other endpoints
			ADBC_Admin_Init::ensure_automation_integrity();

			$tasks = ADBC_Automation::instance()->tasks();

			$status = sanitize_key( $request->get_param( 'status' ) );
			if ( ! in_array( $status, [ 'all', 'active', 'paused' ], true ) ) {
				$status = 'all';
			}

			$all_count = count( $tasks );
			$active_count = count( array_filter( $tasks, function ($task) {
				return $task['active'] === true;
			} ) );
			$paused_count = $all_count - $active_count;

			$items = $tasks;
			if ( $status === 'active' ) {
				$items = array_filter( $tasks, function ($task) {
					return $task['active'] === true;
				} );
			} elseif ( $status === 'paused' ) {
				$items = array_filter( $tasks, function ($task) {
					return $task['active'] === false;
				} );
			}

			return ADBC_Rest::success( '', [ 
				'items' => $items,
				'counts' => [ 
					'total' => $all_count,
					'active' => $active_count,
					'paused' => $paused_count,
				],
			] );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Create a new automation task using the provided details, returns the ID of the created task.
	 * 
	 * @param WP_REST_Request $request
	 * 
	 * @return WP_REST_Response
	 */
	public static function create_task( WP_REST_Request $request ) {

		try {

			$task_details = $request->get_json_params();

			if ( ADBC_VERSION_TYPE === 'FREE' && is_array( $task_details ) && isset( $task_details['operations'] ) && is_array( $task_details['operations'] ) ) {
				foreach ( $task_details['operations'] as $items_type => $keep_last_config ) {
					if ( ( $keep_last_config['type'] ?? null ) === 'items' ) {
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

			$is_valid = ADBC_Automation_Validator::validate_task_structure( $task_details );
			if ( $is_valid === false ) {
				return ADBC_Rest::error( "Invalid task structure", ADBC_Rest::BAD_REQUEST );
			}

			$number_of_tasks = count( ADBC_Automation::instance()->tasks() );
			if ( ADBC_VERSION_TYPE === 'FREE' && $number_of_tasks >= 5 ) {
				return ADBC_Rest::error( "Maximum number of tasks reached in free version. Please upgrade to premium.", ADBC_Rest::BAD_REQUEST );
			}

			$id = ADBC_Automation::instance()->create( $task_details );

			if ( $id === null ) {
				return ADBC_Rest::error( 'Failed to create task.', ADBC_Rest::INTERNAL_SERVER_ERROR );
			}

			return ADBC_Rest::success( '', [ 'id' => $id ] );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Update an existing automation task using its ID and the provided details, return the updated task details.
	 * 
	 * @param WP_REST_Request $request
	 * 
	 * @return WP_REST_Response
	 */
	public static function update_task( WP_REST_Request $request ) {

		try {

			$id = sanitize_key( $request->get_param( 'id' ) );
			if ( empty( $id ) || ! is_string( $id ) ) {
				return ADBC_Rest::error( 'Invalid task ID.', ADBC_Rest::BAD_REQUEST );
			}

			$task_details = $request->get_json_params();

			$is_valid = ADBC_Automation_Validator::validate_task_structure( $task_details );
			if ( $is_valid === false ) {
				return ADBC_Rest::error( "Invalid task structure", ADBC_Rest::BAD_REQUEST );
			}

			$updated_details = ADBC_Automation::instance()->update( $id, $task_details );
			if ( $updated_details === null ) {
				return ADBC_Rest::error( 'Task not found or failed to update.', ADBC_Rest::NOT_FOUND );
			}

			return ADBC_Rest::success( '', $updated_details );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Get an automation task by its ID.
	 * 
	 * @param WP_REST_Request $request
	 * 
	 * @return WP_REST_Response
	 */
	public static function get_task( WP_REST_Request $request ) {

		try {

			$id = sanitize_key( $request->get_param( 'id' ) );
			if ( empty( $id ) || ! is_string( $id ) ) {
				return ADBC_Rest::error( 'Invalid task ID.', ADBC_Rest::BAD_REQUEST );
			}

			$task = ADBC_Automation::instance()->get_task( $id );

			if ( $task === null ) {
				return ADBC_Rest::error( 'Task not found.', ADBC_Rest::NOT_FOUND );
			}

			return ADBC_Rest::success( '', $task );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Delete an automation task using its ID.
	 * 
	 * @param WP_REST_Request $request
	 * 
	 * @return WP_REST_Response
	 */
	public static function delete_task( WP_REST_Request $request ) {

		try {

			$id = sanitize_key( $request->get_param( 'id' ) );
			if ( empty( $id ) || ! is_string( $id ) ) {
				return ADBC_Rest::error( 'Invalid task ID.', ADBC_Rest::BAD_REQUEST );
			}

			$deleted = ADBC_Automation::instance()->delete( $id );

			if ( $deleted === false ) {
				return ADBC_Rest::error( 'Task not found or failed to delete.', ADBC_Rest::NOT_FOUND );
			}

			return ADBC_Rest::success( '', [] );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Get the events log paginated for a specific automation task using its ID.
	 * 
	 * @param WP_REST_Request $request
	 * 
	 * @return WP_REST_Response
	 */
	public static function get_task_events_log( WP_REST_Request $request ) {

		try {

			$id = sanitize_key( $request->get_param( 'id' ) );
			if ( empty( $id ) || ! is_string( $id ) ) {
				return ADBC_Rest::error( 'Invalid task ID.', ADBC_Rest::BAD_REQUEST );
			}

			$page = ADBC_Common_Validator::sanitize_validate_current_page( $request->get_param( 'currentPage' ) );
			$limit = ADBC_Common_Validator::sanitize_validate_limit( $request->get_param( 'itemsPerPage' ) );

			$events = ADBC_Automation_Events_Log::get_events( $id, $page, $limit );

			$total_pages = max( 1, ceil( $events['total_items'] / $limit ) );
			$real_current_page = min( $page, $total_pages );
			$events['real_current_page'] = $real_current_page;

			return ADBC_Rest::success( '', $events );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Clear the events log for a specific automation task using its ID.
	 * 
	 * @param WP_REST_Request $request
	 * 
	 * @return WP_REST_Response
	 */
	public static function clear_task_events_log( WP_REST_Request $request ) {

		try {

			$id = sanitize_key( $request->get_param( 'id' ) );
			if ( empty( $id ) || ! is_string( $id ) ) {
				return ADBC_Rest::error( 'Invalid task ID.', ADBC_Rest::BAD_REQUEST );
			}

			$ok = ADBC_Automation_Events_Log::clear_events( $id );

			if ( ! $ok ) {
				return ADBC_Rest::error( 'Task not found.', ADBC_Rest::NOT_FOUND );
			}

			return ADBC_Rest::success( '', [] );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

}
