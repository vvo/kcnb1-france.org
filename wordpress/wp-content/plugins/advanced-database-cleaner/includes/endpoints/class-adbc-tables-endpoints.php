<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Tables Endpoints.
 * 
 * This class provides the endpoints (controllers) for the tables routes.
 */
class ADBC_Tables_Endpoints {

	/**
	 * Get the tables list.
	 *
	 * @param WP_REST_Request $filters_request The request with the filters.
	 * @return WP_REST_Response The list of tables.
	 */
	public static function get_tables_list( WP_REST_Request $filters_request ) {

		try {

			$filters = ADBC_Common_Validator::sanitize_filters( $filters_request );
			$rest_response = ADBC_Tables::get_tables_list( $filters );
			return $rest_response;

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Get the names of all tables.
	 *
	 * @return WP_REST_Response The response with the tables names.
	 */
	public static function get_tables_names() {

		try {

			$show_tables_with_invalid_prefix = ADBC_Settings::instance()->get_setting( 'show_tables_with_invalid_prefix' ) === '1';
			$tables_names = ADBC_Tables::get_tables_names( PHP_INT_MAX, 0, true, $show_tables_with_invalid_prefix );

			return ADBC_Rest::success( "", $tables_names );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Edit scan results of tables.
	 *
	 * @param WP_REST_Request $request_data The request with the tables to edit.
	 * @return WP_REST_Response The response.
	 */
	public static function edit_scan_results_tables( WP_REST_Request $request_data ) {

		try {

			return ADBC_Scan_Utils::edit_scan_results( $request_data, 'edit_scan_results_tables', 'tables' );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Optimize tables.
	 *
	 * @param WP_REST_Request $request_data The request with the tables to optimize.
	 * @return WP_REST_Response The response.
	 */
	public static function optimize_tables( WP_REST_Request $request_data ) {

		try {

			$validation_answer = ADBC_Common_Validator::validate_endpoint_action_data( "optimize_tables", "tables", $request_data, true );

			// If $validation_answer is not an array, it means that the validation failed and we have an error message.
			if ( ! is_array( $validation_answer ) )
				return ADBC_Rest::error( $validation_answer, ADBC_Rest::BAD_REQUEST );

			// Create an array containing only the table names.
			$tables_names = array_column( $validation_answer, 'name' );
			$not_processed = ADBC_Tables::optimize_tables( $tables_names ); // Optimize the tables

			return ADBC_Rest::success( "", count( $not_processed ) );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Refresh counts of tables (ANALYZE).
	 *
	 * @param WP_REST_Request $request_data The request with the tables to analyze.
	 * @return WP_REST_Response The response.
	 */
	public static function refresh_counts_tables( WP_REST_Request $request_data ) {

		try {

			$validation_answer = ADBC_Common_Validator::validate_endpoint_action_data( "refresh_counts_tables", "tables", $request_data, true );

			// If $validation_answer is not an array, it means that the validation failed and we have an error message.
			if ( ! is_array( $validation_answer ) )
				return ADBC_Rest::error( $validation_answer, ADBC_Rest::BAD_REQUEST );

			// Create an array containing only the table names.
			$tables_names = array_column( $validation_answer, 'name' );
			$not_processed = ADBC_Tables::refresh_tables_counts( $tables_names );

			return ADBC_Rest::success( "", count( $not_processed ) );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Convert a single table to InnoDB.
	 *
	 * @param WP_REST_Request $request_data The request with the table to convert (selectedItems with one table).
	 * @return WP_REST_Response The response.
	 */
	public static function convert_to_innodb_tables( WP_REST_Request $request_data ) {

		try {

			if ( ! ADBC_Tables::is_innodb_supported() )
				return ADBC_Rest::error( __( 'The InnoDB storage engine is not supported by your MySQL server. Conversion cannot be performed.', 'advanced-database-cleaner' ), ADBC_Rest::BAD_REQUEST );

			$validation_answer = ADBC_Common_Validator::validate_endpoint_action_data( "convert_to_innodb_tables", "tables", $request_data, true );

			if ( ! is_array( $validation_answer ) )
				return ADBC_Rest::error( $validation_answer, ADBC_Rest::BAD_REQUEST );

			if ( count( $validation_answer ) !== 1 )
				return ADBC_Rest::error( 'Only one table can be converted per request.', ADBC_Rest::BAD_REQUEST );

			$table_name = $validation_answer[0]['name'];

			// If setting is enabled, reject protected WordPress core tables.
			if ( ADBC_Settings::instance()->get_setting( 'prevent_taking_action_on_wp_items' ) === '1' ) {

				$cleaned = ADBC_Hardcoded_Items::instance()->exclude_hardcoded_items_from_selected_items( $validation_answer, 'tables', 'wp' );

				if ( ADBC_VERSION_TYPE === 'PREMIUM' )
					$cleaned = ADBC_Scan_Utils::exclude_r_wp_items_from_selected_items( $cleaned, 'tables' );

				if ( empty( $cleaned ) )
					return ADBC_Rest::error(
						__( 'This table cannot be converted because it belongs to WordPress core and is protected.', 'advanced-database-cleaner' ),
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

			$result = ADBC_Tables::convert_tables_to_innodb( [ $table_name ] );

			if ( $result['skipped_locked'] > 0 )
				return ADBC_Rest::error( __( 'This table is currently being converted in the background. Please check back later.', 'advanced-database-cleaner' ), ADBC_Rest::BAD_REQUEST );

			if ( ! empty( $result['not_converted'] ) )
				return ADBC_Rest::error( __( 'The table could not be converted. Please try again later.', 'advanced-database-cleaner' ), ADBC_Rest::BAD_REQUEST );

			return ADBC_Rest::success( "", 0 );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Empty rows of tables.
	 *
	 * @param WP_REST_Request $request_data The request with the tables to empty.
	 * @return WP_REST_Response The response.
	 */
	public static function empty_rows_tables( WP_REST_Request $request_data ) {

		try {

			$validation_answer = ADBC_Common_Validator::validate_endpoint_action_data( "empty_rows_tables", "tables", $request_data, true );

			// If $validation_answer is not an array, it means that the validation failed and we have an error message.
			if ( ! is_array( $validation_answer ) )
				return ADBC_Rest::error( $validation_answer, ADBC_Rest::BAD_REQUEST );

			$cleaned_tables = $validation_answer;

			// Conditionally exclude WordPress tables.
			if ( ADBC_Settings::instance()->get_setting( 'prevent_taking_action_on_wp_items' ) === '1' ) {

				$cleaned_tables = ADBC_Hardcoded_Items::instance()->exclude_hardcoded_items_from_selected_items( $validation_answer, 'tables', 'wp' );

				if ( ADBC_VERSION_TYPE === 'PREMIUM' )
					$cleaned_tables = ADBC_Scan_Utils::exclude_r_wp_items_from_selected_items( $cleaned_tables, 'tables' );

				if ( empty( $cleaned_tables ) )
					return ADBC_Rest::error(
						__( 'The selected tables could not be emptied because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
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

			// Create an array containing only the table names.
			$tables_names = array_column( $cleaned_tables, 'name' );
			$not_processed = ADBC_Tables::empty_tables( $tables_names ); // Empty the tables

			if ( count( $cleaned_tables ) < count( $validation_answer ) ) {
				return ADBC_Rest::success(
					__( 'Some tables were emptied successfully; others were skipped because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
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
	 * Delete tables.
	 *
	 * @param WP_REST_Request $request_data The request with the tables to delete.
	 * @return WP_REST_Response The response.
	 */
	public static function delete_tables( WP_REST_Request $request_data ) {

		try {

			// Verify if there is a scan in progress. If there is, return an error to prevent conflicts.
			if ( ADBC_VERSION_TYPE === 'PREMIUM' && ADBC_Scan_Utils::is_scan_exists( 'tables' ) )
				return ADBC_Rest::error( __( 'A scan is in progress. Please wait until it finishes before performing this action.', 'advanced-database-cleaner' ), ADBC_Rest::BAD_REQUEST );

			$validation_answer = ADBC_Common_Validator::validate_endpoint_action_data( "delete_tables", "tables", $request_data, true );

			// If $validation_answer is not an array, it means that the validation failed and we have an error message.
			if ( ! is_array( $validation_answer ) )
				return ADBC_Rest::error( $validation_answer, ADBC_Rest::BAD_REQUEST );

			$cleaned_tables = $validation_answer;

			// Conditionally exclude WordPress tables.
			if ( ADBC_Settings::instance()->get_setting( 'prevent_taking_action_on_wp_items' ) === '1' ) {

				$cleaned_tables = ADBC_Hardcoded_Items::instance()->exclude_hardcoded_items_from_selected_items( $validation_answer, 'tables', 'wp' );

				if ( ADBC_VERSION_TYPE === 'PREMIUM' )
					$cleaned_tables = ADBC_Scan_Utils::exclude_r_wp_items_from_selected_items( $cleaned_tables, 'tables' );

				if ( empty( $cleaned_tables ) )
					return ADBC_Rest::error(
						__( 'The selected tables could not be deleted because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
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

			// Create an array containing only the table names.
			$tables_names = array_column( $cleaned_tables, 'name' );
			$not_processed = ADBC_Tables::delete_tables( $tables_names );

			// Delete the tables from the scan results
			if ( ADBC_VERSION_TYPE === 'PREMIUM' )
				ADBC_Scan_Utils::update_scan_results_file_after_deletion( 'tables', $tables_names, $not_processed );

			if ( count( $cleaned_tables ) < count( $validation_answer ) ) {
				return ADBC_Rest::success(
					__( 'Some tables were deleted successfully; others were skipped because they belong to WordPress core and are protected.', 'advanced-database-cleaner' ),
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
	 * Repair tables.
	 *
	 * @param WP_REST_Request $request_data The request with the tables to repair.
	 * @return WP_REST_Response The response.
	 */
	public static function repair_tables( WP_REST_Request $request_data ) {

		try {

			$validation_answer = ADBC_Common_Validator::validate_endpoint_action_data( "repair_tables", "tables", $request_data, true );

			// If $validation_answer is not an array, it means that the validation failed and we have an error message.
			if ( ! is_array( $validation_answer ) )
				return ADBC_Rest::error( $validation_answer, ADBC_Rest::BAD_REQUEST );

			// Create an array containing only the table names.
			$tables_names = array_column( $validation_answer, 'name' );
			$not_processed = ADBC_Tables::repair_tables( $tables_names ); // Repair the tables

			return ADBC_Rest::success( "", count( $not_processed ) );

		} catch (Throwable $e) {

			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );

		}
	}

	/**
	 * Count the total number of tables that are not scanned.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function count_total_not_scanned_tables() {
		try {
			return ADBC_Rest::success( "", ADBC_Tables::count_total_not_scanned_tables() );
		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}
	}

	/**
	 * Count the total number of tables that are not repaired.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function count_total_tables_to_repair() {
		try {
			return ADBC_Rest::success( "", ADBC_Tables::count_total_tables_to_repair() );
		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}
	}

	/**
	 * Count the total number of tables that are not optimized.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function count_total_tables_to_optimize() {
		try {
			return ADBC_Rest::success( "", ADBC_Tables::count_total_tables_to_optimize() );
		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}
	}

	/**
	 * Count the total number of tables that have invalid prefix.
	 *
	 * @return WP_REST_Response The response.
	 */
	public static function count_total_tables_with_invalid_prefix() {
		try {
			return ADBC_Rest::success( "", ADBC_Tables::get_total_tables_with_invalid_prefix_count() );
		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}
	}

	/**
	 * Get the rows of a table.
	 *
	 * @param WP_REST_Request $request_data The request with the filters.
	 * @return WP_REST_Response The response with the rows of the table.
	 */
	public static function get_table_rows( WP_REST_Request $request_data ) {

		try {

			$filters = ADBC_Common_Validator::sanitize_filters( $request_data );
			$table_name = $request_data->get_param( 'tableName' );

			if ( ! ADBC_Tables::is_table_exists( $table_name ) )
				return ADBC_Rest::error( 'The table does not exist.', ADBC_Rest::UNPROCESSABLE_ENTITY );

			if ( ADBC_Tables::is_table_corrupted( $table_name ) )
				return ADBC_Rest::error( __( 'The table is corrupted and cannot be accessed.', 'advanced-database-cleaner' ), ADBC_Rest::BAD_REQUEST );

			$data = ADBC_Tables::get_table_rows( $table_name, $filters );

			return ADBC_Rest::success( "", $data );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

	/**
	 * Get the structure of a table.
	 *
	 * @param WP_REST_Request $request_data The request with the table name.
	 * @return WP_REST_Response The response with the structure of the table.
	 */
	public static function get_table_structure( WP_REST_Request $request_data ) {

		try {

			$table_name = $request_data->get_param( 'tableName' );

			if ( ! ADBC_Tables::is_table_exists( $table_name ) )
				return ADBC_Rest::error( 'The table does not exist.', ADBC_Rest::UNPROCESSABLE_ENTITY );

			if ( ADBC_Tables::is_table_corrupted( $table_name ) )
				return ADBC_Rest::error( __( 'The table is corrupted and cannot be accessed.', 'advanced-database-cleaner' ), ADBC_Rest::BAD_REQUEST );

			return ADBC_Rest::success( "", ADBC_Tables::get_table_structure( $table_name ) );

		} catch (Throwable $e) {
			return ADBC_Rest::error_for_uncaught_exception( __METHOD__, $e );
		}

	}

}