<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC All Routes class.
 * 
 * This class centralizes all route registrations and security checks for the plugin.
 */
class ADBC_Routes {

	/**
	 * Register all ADBC routes.
	 * 
	 * @return void
	 */
	public static function register_all_routes() {

		// Settings routes.
		self::register_route( '/update-settings', 'update_settings', WP_REST_Server::EDITABLE, ADBC_Settings_Endpoints::class);
		self::register_route( '/get-setting', 'get_setting', WP_REST_Server::EDITABLE, ADBC_Settings_Endpoints::class);

		// Tables routes.
		self::register_route( '/get-tables-list', 'get_tables_list', WP_REST_Server::EDITABLE, ADBC_Tables_Endpoints::class);
		self::register_route( '/get-tables-names', 'get_tables_names', WP_REST_Server::READABLE, ADBC_Tables_Endpoints::class);
		self::register_route( '/optimize-tables', 'optimize_tables', WP_REST_Server::EDITABLE, ADBC_Tables_Endpoints::class);
		self::register_route( '/repair-tables', 'repair_tables', WP_REST_Server::EDITABLE, ADBC_Tables_Endpoints::class);
		self::register_route( '/refresh-counts-tables', 'refresh_counts_tables', WP_REST_Server::EDITABLE, ADBC_Tables_Endpoints::class);
		self::register_route( '/convert-to-innodb-tables', 'convert_to_innodb_tables', WP_REST_Server::EDITABLE, ADBC_Tables_Endpoints::class);
		self::register_route( '/empty-rows-tables', 'empty_rows_tables', WP_REST_Server::EDITABLE, ADBC_Tables_Endpoints::class);
		self::register_route( '/delete-tables', 'delete_tables', WP_REST_Server::EDITABLE, ADBC_Tables_Endpoints::class);
		self::register_route( '/count-total-not-scanned-tables', 'count_total_not_scanned_tables', WP_REST_Server::READABLE, ADBC_Tables_Endpoints::class);
		self::register_route( '/count-total-tables-to-optimize', 'count_total_tables_to_optimize', WP_REST_Server::READABLE, ADBC_Tables_Endpoints::class);
		self::register_route( '/count-total-tables-to-repair', 'count_total_tables_to_repair', WP_REST_Server::READABLE, ADBC_Tables_Endpoints::class);
		self::register_route( '/count-total-tables-with-invalid-prefix', 'count_total_tables_with_invalid_prefix', WP_REST_Server::READABLE, ADBC_Tables_Endpoints::class);
		self::register_route( '/get-table-rows', 'get_table_rows', WP_REST_Server::EDITABLE, ADBC_Tables_Endpoints::class);
		self::register_route( '/get-table-structure', 'get_table_structure', WP_REST_Server::EDITABLE, ADBC_Tables_Endpoints::class);

		// Options routes.
		self::register_route( '/get-options-list', 'get_options_list', WP_REST_Server::EDITABLE, ADBC_Options_Endpoints::class);
		self::register_route( '/set-autoload-to-yes-options', 'set_autoload_to_yes_options', WP_REST_Server::EDITABLE, ADBC_Options_Endpoints::class);
		self::register_route( '/set-autoload-to-no-options', 'set_autoload_to_no_options', WP_REST_Server::EDITABLE, ADBC_Options_Endpoints::class);
		self::register_route( '/delete-options', 'delete_options', WP_REST_Server::EDITABLE, ADBC_Options_Endpoints::class);
		self::register_route( '/count-big-options', 'count_big_options', WP_REST_Server::READABLE, ADBC_Options_Endpoints::class);
		self::register_route( '/count-total-not-scanned-options', 'count_total_not_scanned_options', WP_REST_Server::READABLE, ADBC_Options_Endpoints::class);
		self::register_route( '/get-autoload-health', 'get_autoload_health', WP_REST_Server::READABLE, ADBC_Options_Endpoints::class);

		// Transients routes.
		self::register_route( '/get-transients-list', 'get_transients_list', WP_REST_Server::EDITABLE, ADBC_Transients_Endpoints::class);
		self::register_route( '/set-autoload-to-yes-transients', 'set_autoload_to_yes_transients', WP_REST_Server::EDITABLE, ADBC_Transients_Endpoints::class);
		self::register_route( '/set-autoload-to-no-transients', 'set_autoload_to_no_transients', WP_REST_Server::EDITABLE, ADBC_Transients_Endpoints::class);
		self::register_route( '/delete-transients', 'delete_transients', WP_REST_Server::EDITABLE, ADBC_Transients_Endpoints::class);
		self::register_route( '/count-big-transients', 'count_big_transients', WP_REST_Server::READABLE, ADBC_Transients_Endpoints::class);
		self::register_route( '/count-total-not-scanned-transients', 'count_total_not_scanned_transients', WP_REST_Server::READABLE, ADBC_Transients_Endpoints::class);
		self::register_route( '/count-expired-transients', 'count_expired_transients', WP_REST_Server::READABLE, ADBC_Transients_Endpoints::class);

		// Posts_meta routes.
		self::register_route( '/get-posts-meta-list', 'get_posts_meta_list', WP_REST_Server::EDITABLE, ADBC_Posts_Meta_Endpoints::class);
		self::register_route( '/delete-posts-meta', 'delete_posts_meta', WP_REST_Server::EDITABLE, ADBC_Posts_Meta_Endpoints::class);
		self::register_route( '/count-big-posts-meta', 'count_big_posts_meta', WP_REST_Server::READABLE, ADBC_Posts_Meta_Endpoints::class);
		self::register_route( '/count-total-not-scanned-posts-meta', 'count_total_not_scanned_posts_meta', WP_REST_Server::READABLE, ADBC_Posts_Meta_Endpoints::class);
		self::register_route( '/count-duplicated-posts-meta', 'count_duplicated_posts_meta', WP_REST_Server::READABLE, ADBC_Posts_Meta_Endpoints::class);
		self::register_route( '/count-unused-posts-meta', 'count_unused_posts_meta', WP_REST_Server::READABLE, ADBC_Posts_Meta_Endpoints::class);

		// Users_meta routes.
		self::register_route( '/get-users-meta-list', 'get_users_meta_list', WP_REST_Server::EDITABLE, ADBC_Users_Meta_Endpoints::class);
		self::register_route( '/delete-users-meta', 'delete_users_meta', WP_REST_Server::EDITABLE, ADBC_Users_Meta_Endpoints::class);
		self::register_route( '/count-big-users-meta', 'count_big_users_meta', WP_REST_Server::READABLE, ADBC_Users_Meta_Endpoints::class);
		self::register_route( '/count-total-not-scanned-users-meta', 'count_total_not_scanned_users_meta', WP_REST_Server::READABLE, ADBC_Users_Meta_Endpoints::class);
		self::register_route( '/count-duplicated-users-meta', 'count_duplicated_users_meta', WP_REST_Server::READABLE, ADBC_Users_Meta_Endpoints::class);
		self::register_route( '/count-unused-users-meta', 'count_unused_users_meta', WP_REST_Server::READABLE, ADBC_Users_Meta_Endpoints::class);

		// Post_types routes.
		self::register_route( '/get-post-types-list', 'get_post_types_list', WP_REST_Server::EDITABLE, ADBC_Post_Types_Endpoints::class);
		self::register_route( '/list-posts-by-post-type', 'list_posts_by_post_type', WP_REST_Server::EDITABLE, ADBC_Post_Types_Endpoints::class);
		self::register_route( '/delete-posts-by-post-type', 'delete_posts_by_post_type', WP_REST_Server::EDITABLE, ADBC_Post_Types_Endpoints::class);
		self::register_route( '/count-total-not-scanned-post-types', 'count_total_not_scanned_post_types', WP_REST_Server::READABLE, ADBC_Post_Types_Endpoints::class);
		self::register_route( '/count-total-large-non-public-post-types', 'count_total_large_non_public_post_types', WP_REST_Server::READABLE, ADBC_Post_Types_Endpoints::class);

		// Cron_jobs routes.
		self::register_route( '/get-cron-jobs-list', 'get_cron_jobs_list', WP_REST_Server::EDITABLE, ADBC_Cron_Jobs_Endpoints::class);
		self::register_route( '/delete-cron-jobs', 'delete_cron_jobs', WP_REST_Server::EDITABLE, ADBC_Cron_Jobs_Endpoints::class);
		self::register_route( '/count-total-not-scanned-cron-jobs', 'count_total_not_scanned_cron_jobs', WP_REST_Server::READABLE, ADBC_Cron_Jobs_Endpoints::class);
		self::register_route( '/count-total-cron-jobs-with-no-action', 'count_total_cron_jobs_with_no_action', WP_REST_Server::READABLE, ADBC_Cron_Jobs_Endpoints::class);

		// Common routes.
		self::register_route( '/get-column-value-from-table', 'get_column_value_from_table', WP_REST_Server::EDITABLE, ADBC_Common_Endpoints::class);
		self::register_route( '/dismiss-notification', 'dismiss_notification', WP_REST_Server::EDITABLE, ADBC_Common_Endpoints::class);
		self::register_route( '/delay-rating-notice', 'delay_rating_notice', WP_REST_Server::EDITABLE, ADBC_Common_Endpoints::class);
		self::register_route( '/delay-ltd-migration-notice', 'delay_ltd_migration_notice', WP_REST_Server::EDITABLE, ADBC_Common_Endpoints::class);
		self::register_route( '/get-all-schedule-frequencies', 'get_all_schedule_frequencies', WP_REST_Server::READABLE, ADBC_Common_Endpoints::class);
		self::register_route( '/get-last-week-database-size-for-free-version', 'get_last_week_database_size_for_free_version', WP_REST_Server::READABLE, ADBC_Common_Endpoints::class);

		// Logs routes.
		self::register_route( '/get-logs-content', 'get_logs_content', WP_REST_Server::EDITABLE, ADBC_Logs_Endpoints::class);
		self::register_route( '/clear-logs-content', 'clear_logs_content', WP_REST_Server::EDITABLE, ADBC_Logs_Endpoints::class);

		// Info routes.
		self::register_route( '/get-system-information', 'get_system_information', WP_REST_Server::READABLE, ADBC_Info_Endpoints::class);

		// General cleanup routes.
		self::register_route( '/general-cleanup/get-general-data', 'get_general_data', WP_REST_Server::EDITABLE, ADBC_General_Cleanup_Endpoints::class);
		self::register_route( '/general-cleanup/get-items', 'get_items', WP_REST_Server::EDITABLE, ADBC_General_Cleanup_Endpoints::class);
		self::register_route( '/general-cleanup/delete-items', 'delete_items', WP_REST_Server::EDITABLE, ADBC_General_Cleanup_Endpoints::class);
		self::register_route( '/general-cleanup/purge-items', 'purge_items', WP_REST_Server::EDITABLE, ADBC_General_Cleanup_Endpoints::class);
		self::register_route( '/general-cleanup/set-keep-last', 'set_keep_last', WP_REST_Server::EDITABLE, ADBC_General_Cleanup_Endpoints::class);
		self::register_route( '/general-cleanup/get-keep-last', 'get_keep_last', WP_REST_Server::READABLE, ADBC_General_Cleanup_Endpoints::class);
		self::register_route( '/general-cleanup/delete-keep-last', 'delete_keep_last', WP_REST_Server::EDITABLE, ADBC_General_Cleanup_Endpoints::class);
		self::register_route( '/general-cleanup/activate-auto-count', 'activate_auto_count', WP_REST_Server::EDITABLE, ADBC_General_Cleanup_Endpoints::class);
		self::register_route( '/general-cleanup/deactivate-auto-count', 'deactivate_auto_count', WP_REST_Server::EDITABLE, ADBC_General_Cleanup_Endpoints::class);

		// Automation routes.
		self::register_route( '/automation/list-tasks', 'list_tasks', WP_REST_Server::EDITABLE, ADBC_Automation_Endpoints::class);
		self::register_route( '/automation/create-task', 'create_task', WP_REST_Server::EDITABLE, ADBC_Automation_Endpoints::class);
		self::register_route( '/automation/update-task', 'update_task', WP_REST_Server::EDITABLE, ADBC_Automation_Endpoints::class);
		self::register_route( '/automation/delete-task', 'delete_task', WP_REST_Server::EDITABLE, ADBC_Automation_Endpoints::class);
		self::register_route( '/automation/get-task', 'get_task', WP_REST_Server::EDITABLE, ADBC_Automation_Endpoints::class);

	}

	/**
	 * Register a route.
	 * 
	 * @param string $route Route.
	 * @param string $callback_method Callback method.
	 * @param string $method Method (WP_REST_Server::READABLE or WP_REST_Server::EDITABLE).
	 * @param string $endpoint_class Endpoint class name.
	 * @return void
	 */
	public static function register_route( $route, $callback_method, $method, $endpoint_class ) {

		register_rest_route( ADBC_REST_API_NAMESPACE, $route, [ 
			'methods' => $method,
			'callback' => [ $endpoint_class, $callback_method ],
			'permission_callback' => [ self::class, 'rest_security_check' ],
		] );

	}

	/**
	 * Check if the user is allowed to perform the requested operation.
	 * 
	 * @return bool|WP_Error True if the user is allowed, WP_Error otherwise. 
	 */
	public static function rest_security_check() {

		// If the nonce is not set, return false.
		if ( ! isset( $_SERVER['HTTP_X_WP_NONCE'] ) )
			return new WP_Error( ADBC_Rest::UNAUTHORIZED, __( 'Security check failed! Invalid nonce.', 'advanced-database-cleaner' ), [ 'status' => ADBC_Rest::UNAUTHORIZED ] );

		// If the nonce is not valid, return false.
		$nonce = sanitize_key( $_SERVER['HTTP_X_WP_NONCE'] );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) )
			return new WP_Error( ADBC_Rest::UNAUTHORIZED, __( 'Security check failed! Invalid nonce.', 'advanced-database-cleaner' ), [ 'status' => ADBC_Rest::UNAUTHORIZED ] );

		// Check if the user is logged in.
		if ( ! is_user_logged_in() )
			return new WP_Error( ADBC_Rest::UNAUTHORIZED, __( 'Unauthorized! You should be logged in.', 'advanced-database-cleaner' ), [ 'status' => ADBC_Rest::UNAUTHORIZED ] );

		// Check if the user has the right permissions.
		if ( ! current_user_can( 'manage_options' ) )
			return new WP_Error( ADBC_Rest::UNAUTHORIZED, __( 'Unauthorized! Insufficient permissions.', 'advanced-database-cleaner' ), [ 'status' => ADBC_Rest::UNAUTHORIZED ] );

		return true;

	}
}