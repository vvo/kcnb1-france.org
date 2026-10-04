<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC options class.
 * 
 * This class provides the options functions.
 */
class ADBC_Options {

	private const BIG_OPTION_THRESHOLD_WARNING = 150 * 1024; // 150 KB. (If you change this value, change it as well in js filter message and slice)
	private const TRUNCATE_LENGTH = 20; // Length to truncate the option value for display

	/**
	 * Get the options list for the endpoint.
	 *
	 * @param array $filters Output of sanitize_filters().
	 *
	 * @return WP_REST_Response The list of options.
	 */
	public static function get_options_list( $filters ) {

		// Prepare variables
		$options_list = [];
		$total_options = 0;

		$scan_counter = new ADBC_Scan_Counter();

		$startRecord = ( $filters['current_page'] - 1 ) * $filters['items_per_page'];
		$endRecord = $startRecord + $filters['items_per_page'];
		$currentRecord = 0;

		$limit = ADBC_Settings::instance()->get_setting( 'database_rows_batch' );
		$offset = 0;

		do { // Loop through all options in batches of $limit to avoid memory issues

			$options = self::get_options_list_batch( $filters, $limit, $offset );
			$fetched_count = count( $options );

			if ( ADBC_VERSION_TYPE === 'PREMIUM' )
				ADBC_Scan_Results::instance()->load_scan_results_to_items_rows( $options, 'options' ); // Load scan results to the options rows
			else
				ADBC_Common_Model::load_scan_results_to_items_for_free_version( $options ); // Load scan results to the options rows for free version

			ADBC_Hardcoded_Items::instance()->load_hardcoded_scan_results_to_items_rows( $options, 'options' ); // Load hardcoded items to the options rows

			foreach ( $options as $index => $option ) {

				$scan_counter->refresh_categorization_count( $option->belongs_to );

				if ( ! ADBC_Common_Model::is_item_satisfies_belongs_to( $filters, $option->belongs_to ) )
					continue;

				$total_options++; // Count options that satisfy all filters and belongs_to

				// Only process the current batch if it's within the desired page range
				if ( $currentRecord >= $startRecord && $currentRecord < $endRecord ) {

					$options_list[] = [ 
						// This id is used to identify the option in the frontend and take actions on it
						'composite_id' => [ 
							'items_type' => 'options',
							'site_id' => (int) $option->site_id,
							'id' => (int) $option->option_id,
							'name' => $option->name,
						],
						'id' => $option->option_id,
						'name' => $option->name, // Used in the known addons modal & "show value modal". To be generic and work for all items types.
						'option_name' => $option->name,
						'value' => $option->value,
						'size' => $option->size,
						'autoload' => $option->autoload,
						'site_id' => $option->site_id,
						'belongs_to' => $option->belongs_to,
						'known_plugins' => $option->known_plugins,
						'known_themes' => $option->known_themes,
					];
				}

				$currentRecord++;
			}

			$offset += $limit;

		} while ( $fetched_count == $limit ); // Continue if the last batch was full

		// Loop over the $options_list and $scan_counter add the plugins/themes names from the dictionary if they are empty
		// This is because load_scan_results_to_rows() only loads the names of the plugins/themes that are currently installed
		if ( ADBC_VERSION_TYPE === 'PREMIUM' )
			ADBC_Dictionary::add_missing_addons_names_from_dictionary( $options_list, $scan_counter, 'options' );

		// Calculate total number of pages to verify that the current page sent by the user is within the range
		$total_real_pages = max( 1, ceil( $total_options / $filters['items_per_page'] ) );

		return ADBC_Rest::success( "", [ 
			'items' => $options_list,
			'total_items' => $total_options,
			'real_current_page' => min( $filters['current_page'], $total_real_pages ),
			'categorization_count' => $scan_counter->get_categorization_count(),
			'plugins_count' => $scan_counter->get_plugins_count(),
			'themes_count' => $scan_counter->get_themes_count(),
		] );
	}

	/**
	 * Get the options list that satisfy the UI filters.
	 *
	 * @param array $filters Output of sanitize_filters().
	 * @param int   $limit   Limit for the number of rows to return.
	 * @param int   $offset  Offset for the number of rows to return.
	 *
	 * @return array List of options that satisfy the filters.
	 */
	private static function get_options_list_batch( $filters, $limit, $offset ) {

		global $wpdb;
		$sites_list = ADBC_Sites::instance()->get_sites_list( $filters['site_id'] );
		$is_single_site_query = ( count( $sites_list ) === 1 );

		/* ──────────────────────────────────────────────────────────────
		 * Build a safe ORDER BY clause
		 * ─────────────────────────────────────────────────────────────*/
		$allowed_columns = [ 
			'option_name' => '`name`',
			'size' => '`size`',
			'autoload' => '`autoload`',
			'site_id' => '`site_id`',
		];

		$sort_col = $filters['sort_by'] ?? '';
		$sort_dir = strtoupper( $filters['sort_order'] ?? 'ASC' );
		$sort_dir = ( $sort_dir === 'DESC' ) ? 'DESC' : 'ASC';

		// Add 'order by' clause if the column is allowed.
		$order_by_sql = isset( $allowed_columns[ $sort_col ] )
			? "ORDER BY {$allowed_columns[ $sort_col ]} {$sort_dir}"
			: '';

		/* ──────────────────────────────────────────────────────────────
		 * Single-site path (no UNION, no derived table)
		 * ─────────────────────────────────────────────────────────────*/
		if ( $is_single_site_query ) {

			$site = reset( $sites_list );
			$table_name = $site['prefix'] . 'options';
			$site_id = $site['id'];

			$sql = self::prepare_options_list_sql_for_single_site(
				$site_id,
				$table_name,
				$filters,
				$order_by_sql,
				$limit,
				$offset
			);

			return $wpdb->get_results( $sql, OBJECT );
		}

		/* ──────────────────────────────────────────────────────────────
		 * Multisite path (UNION across all sites)
		 * ─────────────────────────────────────────────────────────────*/
		$union_queries = [];

		// Offset starts from 0, so we need to add the limit to it to increment the number of rows to fetch in each iteration.
		$total_rows_to_fetch = $offset + $limit;
		foreach ( $sites_list as $site ) {
			$table_name = $site['prefix'] . 'options'; // Get the options table name for the current site
			$site_id = $site['id']; // Get the site ID for the current site
			$union_queries[] = self::prepare_options_list_sql_for_union(
				$site_id,
				$table_name,
				$filters,
				$order_by_sql,
				$total_rows_to_fetch
			);
		}

		$union_sql = implode( "\nUNION ALL\n", $union_queries );

		$sql = $wpdb->prepare(
			"SELECT *
				FROM ( {$union_sql} ) AS rows_merged
				{$order_by_sql}
				LIMIT %d OFFSET %d
			",
			$limit,
			$offset
		);

		return $wpdb->get_results( $sql, OBJECT );
	}

	/**
	 * Prepare a SQL query string to get the options list for a single site
	 * (no UNION, no derived table).
	 *
	 * @param int    $site_id      Site ID to query.
	 * @param string $table_name   Options table name to query.
	 * @param array  $filters      Output of sanitize_filters().
	 * @param string $order_by_sql SQL query clause to order the results.
	 * @param int    $limit        Limit for the number of rows to return.
	 * @param int    $offset       Offset for the number of rows to return.
	 *
	 * @return string SQL query to get the options list for a single site.
	 */
	private static function prepare_options_list_sql_for_single_site( $site_id, $table_name, $filters, $order_by_sql, $limit, $offset ) {

		global $wpdb;

		$truncate_length = self::TRUNCATE_LENGTH;
		$autoloaded_values = self::get_values_to_autoload();

		$params = [ absint( $site_id ) ]; // %d for site_id in SELECT

		/* ──────────────────────────────────────────────────────────────
		 * Assemble the dynamic WHERE parts
		 * ─────────────────────────────────────────────────────────────*/
		$where = [ 
			'`option_name` NOT LIKE %s',   // skip transients
			'`option_name` NOT LIKE %s',   // skip site transients
		];

		$params[] = '\_transient\_%';
		$params[] = '\_site\_transient\_%';

		/* — Autoload filter — */
		if ( isset( $filters['autoload'] ) && $filters['autoload'] !== 'all' && ! empty( $autoloaded_values ) ) {

			$placeholders = implode( ',', array_fill( 0, count( $autoloaded_values ), '%s' ) );

			if ( $filters['autoload'] === 'yes' ) {
				$where[] = "`autoload` IN ($placeholders)";
			} else {
				$where[] = "`autoload` NOT IN ($placeholders)";
			}

			$params = array_merge( $params, $autoloaded_values );
		}

		/* — Size ≥ threshold — */
		if ( ! empty( $filters['size'] ) && (int) $filters['size'] > 0 ) {
			$bytes = ADBC_Common_Utils::convert_size_to_bytes(
				$filters['size'],
				$filters['size_unit']
			);
			$where[] = 'OCTET_LENGTH(`option_value`) >= %d';
			$params[] = $bytes;
		}

		/* — Search filter — */
		if ( ! empty( $filters['search_for'] ) && ! empty( $filters['search_in'] ) ) {

			$needle = '%' . $wpdb->esc_like( $filters['search_for'] ) . '%';

			switch ( $filters['search_in'] ) {
				case 'name':
					$where[] = '`option_name` LIKE %s';
					$params[] = $needle;
					break;

				case 'value':
					$where[] = '`option_value` LIKE %s';
					$params[] = $needle;
					break;

				case 'all':
					$where[] = '(`option_name` LIKE %s OR `option_value` LIKE %s)';
					$params[] = $needle; // for option_name
					$params[] = $needle; // for option_value
					break;
			}
		}

		$where_sql = 'WHERE ' . implode( ' AND ', $where );

		// Add limit & offset at the end of the params array
		$params[] = absint( $limit );
		$params[] = absint( $offset );

		/* ──────────────────────────────────────────────────────────────
		 * Final SQL
		 * ─────────────────────────────────────────────────────────────*/
		$sql = $wpdb->prepare(
			"SELECT
				`option_name`                           AS name,
				`option_id`                             AS option_id,
				SUBSTRING(`option_value`, 1, {$truncate_length}) AS value,
				`autoload`                              AS autoload,
				OCTET_LENGTH(`option_value`)            AS size,
				%d                                      AS site_id
			FROM {$table_name}
			{$where_sql}
			{$order_by_sql}
			LIMIT %d OFFSET %d
			",
			...$params
		);

		return $sql;
	}


	/**
	 * Prepare a SQL query string to get the options list that satisfy the UI filters. It will be used in a UNION query to get all options in all sites.
	 *
	 * @param int $site_id Site ID to query.
	 * @param string $table_name Options table name to query.
	 * @param array $filters Output of sanitize_filters().
	 * @param string $order_by_sql SQL query clause to order the results.
	 * @param int $total_rows_to_fetch Limit for the number of rows to return.
	 *
	 * @return string SQL query to get the options list.
	 */
	private static function prepare_options_list_sql_for_union( $site_id, $table_name, $filters, $order_by_sql, $total_rows_to_fetch ) {

		global $wpdb;
		$truncate_length = self::TRUNCATE_LENGTH;
		$autoloaded_values = self::get_values_to_autoload();
		$params = [ absint( $site_id ) ];  // Place the site_id at the beginning of the params array

		$needs_fix = ! ADBC_Database::is_collaction_unified( 'options', false, $filters['site_id'] );

		$target_collation = ! empty( $wpdb->collate ) ? $wpdb->collate : 'utf8mb4_unicode_ci';

		if ( $needs_fix ) {
			$name_expr = "CONVERT(`option_name` USING utf8mb4) COLLATE {$target_collation}";
			$value_expr = "CONVERT(SUBSTRING(`option_value`, 1, {$truncate_length}) USING utf8mb4) COLLATE {$target_collation}";
			$autoload_expr = "CONVERT(`autoload` USING utf8mb4) COLLATE {$target_collation}";
		} else {
			$name_expr = "`option_name`";
			$value_expr = "SUBSTRING(`option_value`, 1, {$truncate_length})";
			$autoload_expr = "`autoload`";
		}

		/* ──────────────────────────────────────────────────────────────
		 * Assemble the dynamic WHERE parts
		 * ─────────────────────────────────────────────────────────────*/
		$where = [ 
			'`option_name` NOT LIKE %s',   // skip site transients
			'`option_name` NOT LIKE %s',
		];

		$params[] = '\_transient\_%';
		$params[] = '\_site\_transient\_%';

		/* — Autoload filter — */
		if ( isset( $filters['autoload'] ) && $filters['autoload'] !== 'all' ) {
			$placeholders = implode( ',', array_fill( 0, count( $autoloaded_values ), '%s' ) );

			if ( $filters['autoload'] === 'yes' ) {
				$where[] = "`autoload` IN ($placeholders)";
			} else {
				$where[] = "`autoload` NOT IN ($placeholders)";
			}

			$params = array_merge( $params, $autoloaded_values );
		}

		/* — Size ≥ threshold — */
		if ( ! empty( $filters['size'] ) && (int) $filters['size'] > 0 ) {
			$bytes = ADBC_Common_Utils::convert_size_to_bytes(
				$filters['size'],
				$filters['size_unit']
			);
			$where[] = 'OCTET_LENGTH(`option_value`) >= %d';
			$params[] = $bytes;
		}

		/* — Search filter — */
		if ( ! empty( $filters['search_for'] ) && ! empty( $filters['search_in'] ) ) {

			$needle = '%' . $wpdb->esc_like( $filters['search_for'] ) . '%';

			switch ( $filters['search_in'] ) {
				case 'name':
					$where[] = '`option_name` LIKE %s';
					$params[] = $needle;
					break;

				case 'value':
					$where[] = '`option_value` LIKE %s';
					$params[] = $needle;
					break;

				case 'all':
					// Search in both columns
					$where[] = '(`option_name` LIKE %s OR `option_value` LIKE %s)';
					$params[] = $needle;   // for option_name
					$params[] = $needle;   // for option_value
					break;
			}
		}

		$where_sql = 'WHERE ' . implode( ' AND ', $where );
		$params[] = absint( $total_rows_to_fetch ); // Add the limit to the params array

		/* ──────────────────────────────────────────────────────────────
		 * 3. Final SQL and fetch
		 * ─────────────────────────────────────────────────────────────*/
		$sql = $wpdb->prepare(
			"SELECT
				{$name_expr}     AS name,
				`option_id`      AS option_id,
				{$value_expr}    AS value,
				{$autoload_expr} AS autoload,
				OCTET_LENGTH(`option_value`) AS size,
				%d               AS site_id
			FROM {$table_name}
			{$where_sql}
			{$order_by_sql}
			LIMIT %d
			",
			...$params
		);

		return '(' . $sql . ')';
	}

	/**
	 * Count the size of all autoloaded options in the wp_options table.
	 * 
	 * @return array Array with autoloaded size and health status.
	 */
	public static function count_autoload_size_using_sql() {

		global $wpdb;
		$autoload_values = self::get_values_to_autoload();

		// Build "%s,%s,%s" for $wpdb->prepare()
		$in_placeholders = implode( ',', array_fill( 0, count( $autoload_values ), '%s' ) );

		$sql = $wpdb->prepare(
			"SELECT
				COALESCE(
					SUM(
						CASE WHEN autoload IN ($in_placeholders)
							THEN LENGTH(option_value)
						END
					),
					0
				)
         	FROM {$wpdb->options}
			",
			...$autoload_values
		);

		$autoloaded_size = (int) $wpdb->get_var( $sql );
		$autoload_limit_in_bytes = self::get_autoload_warning_limit();
		$autoload_health = $autoloaded_size > $autoload_limit_in_bytes ? 'bad' : 'good';

		return [ 
			'autoloaded_size' => ADBC_Common_Utils::format_bytes( $autoloaded_size ),
			'autoload_health' => $autoload_health,
			'autoload_limit' => ADBC_Common_Utils::format_bytes( $autoload_limit_in_bytes ),
		];
	}

	/**
	 * Get the autoload warning limit from WordPress filter or default value. 
	 * This warning limit is used to determine if the autoloaded options size is healthy or not.
	 * 
	 * @return int Autoload warning limit in bytes.
	 */
	public static function get_autoload_warning_limit() {

		// Get the autoload limit from WordPress filter (allows customization via site_status_autoloaded_options_size_limit)
		// This filter was introduced in WordPress 6.6.0, so we check version for backward compatibility
		$default_limit = 800000; // 800 KB - WordPress default recommendation
		global $wp_version;
		if ( version_compare( $wp_version, '6.6.0', '>=' ) ) {
			// WordPress 6.6.0+ - use the filter (allows customization)
			$autoload_limit_bytes = apply_filters( 'site_status_autoloaded_options_size_limit', $default_limit );
		} else {
			// WordPress < 6.6.0 - use default limit (filter doesn't exist yet)
			$autoload_limit_bytes = $default_limit;
		}

		return $autoload_limit_bytes;
	}

	/**
	 * Get the count of big options in all sites.
	 * 
	 * @return int Total count of big options.
	 */
	public static function count_big_options() {

		global $wpdb;
		$total_big_options = 0;

		$sites_prefixes = array_keys( ADBC_Sites::instance()->get_all_prefixes() );

		foreach ( $sites_prefixes as $site_prefix ) {

			$table = $site_prefix . "options";

			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
						FROM `$table`
						WHERE option_name NOT LIKE %s
							AND option_name NOT LIKE %s
							AND OCTET_LENGTH(option_value) > %d",
					'\_transient\_%',
					'\_site\_transient\_%',
					self::BIG_OPTION_THRESHOLD_WARNING
				)
			);

			$total_big_options += $count;
		}

		return $total_big_options;
	}

	/**
	 * Count the total number of options that are not scanned.
	 * 
	 * @return int Total not scanned options.
	 */
	public static function count_total_not_scanned_options() {

		$sites_prefixes = array_keys( ADBC_Sites::instance()->get_all_prefixes() );
		$total_not_scanned = 0;
		$limit = ADBC_Settings::instance()->get_setting( 'database_rows_batch' );

		foreach ( $sites_prefixes as $site_prefix ) {

			$offset = 0;

			do { // Loop through all options in batches of $limit to avoid memory issues

				$options = self::get_options_names( $site_prefix, $limit, $offset, false );
				$fetched_count = count( $options );
				$not_scanned_count = 0;

				if ( ADBC_VERSION_TYPE === 'PREMIUM' )
					$not_scanned_count = ADBC_Scan_Utils::count_not_scanned_items_in_list( "options", $options );
				else
					$not_scanned_count = ADBC_Common_Model::count_not_scanned_items_in_list_for_free( "options", $options );

				$total_not_scanned += $not_scanned_count;

				$offset += $limit;

			} while ( $fetched_count == $limit ); // Continue if the last batch was full

		}

		return $total_not_scanned;

	}

	/**
	 * Return an array with values of autoload that should be autoloaded
	 * 
	 * @return array Autoload values. E.g. [ "yes", "on"... ]
	 */
	public static function get_values_to_autoload() {

		$autoload_values = [ "yes" ]; // Default value is "yes" prior to WP 6.6.0
		if ( function_exists( 'wp_autoload_values_to_autoload' ) )
			$autoload_values = wp_autoload_values_to_autoload(); // WP 6.6.0 and above have this function to get values that should be autoloaded

		return $autoload_values;
	}

	/**
	 * Get total of all options in wp_options table, excluding transients.
	 * 
	 * @return int Options count.
	 */
	public static function get_total_options_count() {

		global $wpdb;

		$total_options = 0;

		$sites_list = ADBC_Sites::instance()->get_sites_list();

		foreach ( $sites_list as $site ) {

			$table_name = $site['prefix'] . 'options';

			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
				 	 FROM {$table_name} 
				 	 WHERE option_name NOT LIKE %s AND option_name NOT LIKE %s",
					'\_transient\_%', '\_site\_transient\_%'
				)
			);

			$total_options += $count;

		}

		return $total_options;

	}

	/**
	 * Get options names for a specific site excluding transients.
	 * 
	 * @param string $site_prefix Site prefix.
	 * @param int $limit Limit.
	 * @param int $offset Offset.
	 * @param boolean $keyed wether or not to key the array by names
	 * 
	 * @return array Associative options names.
	 */
	public static function get_options_names( $site_prefix, $limit, $offset, $keyed = true ) {

		global $wpdb;
		$table = $site_prefix . 'options'; // $site_prefix is safe to use here as it is validated in the calling function.

		$query = $wpdb->prepare(
			"SELECT option_name FROM `$table`
				WHERE option_name NOT LIKE %s
				AND option_name NOT LIKE %s
				LIMIT %d OFFSET %d",
			'\_transient\_%',
			'\_site\_transient\_%',
			absint( $limit ),
			absint( $offset )
		);

		$options_names = $wpdb->get_col( $query );

		if ( $keyed )
			return array_fill_keys( $options_names, true );
		else
			return $options_names;

	}

	/**
	 * Get options names from their ids in a specific site prefix excluding transients.
	 * 
	 * @param string $site_prefix Site prefix of the options.
	 * @param array $options_ids Options ids to get their names.
	 * 
	 * @return array Associative options names.
	 */
	public static function get_options_names_from_ids( $site_prefix, $options_ids ) {

		global $wpdb;
		$table = $site_prefix . 'options'; // $site_prefix is safe to use here as it is validated in the calling function.

		if ( empty( $options_ids ) )
			return [];

		$in_placeholders = implode( ',', array_fill( 0, count( $options_ids ), '%d' ) );

		// Prepare args to pass to the query.
		$args = array_merge(
			$options_ids, // the %d placeholders
			[ '\_transient\_%', '\_site\_transient\_%' ]  // the %s placeholders
		);

		$query = $wpdb->prepare(
			"SELECT option_name FROM `$table`
				WHERE option_id IN ($in_placeholders)
				AND option_name NOT LIKE %s
				AND option_name NOT LIKE %s",
			...$args
		);

		$options_names = $wpdb->get_col( $query );

		// transform the options names array to associative array with the option name as key and true as value
		$options_names = array_fill_keys( $options_names, true );

		return $options_names;

	}

	/**
	 * Set autoload to "no" for grouped options. Options are grouped by site ID as key.
	 * 
	 * @param array $grouped_selected Grouped selected options to set autoload to "no".
	 * 
	 * @return array An array of options names that were not processed.
	 */
	public static function set_autoload_to_no( $grouped_selected ) {

		$autoload_value = function_exists( 'wp_autoload_values_to_autoload' ) ? 'off' : 'no';
		$not_processed = self::set_autoload( $grouped_selected, $autoload_value );
		return $not_processed;

	}

	/**
	 * Set autoload to "yes" for grouped options. Options are grouped by site ID as key.
	 * 
	 * @param array $grouped_selected Grouped selected options to set autoload to "yes".
	 * 
	 * @return array An array of options names that were not processed.
	 */
	public static function set_autoload_to_yes( $grouped_selected ) {

		$autoload_value = function_exists( 'wp_autoload_values_to_autoload' ) ? 'on' : 'yes';
		$not_processed = self::set_autoload( $grouped_selected, $autoload_value );
		return $not_processed;

	}

	/**
	 * Change autoload value for options. Options are grouped by site ID as key.
	 * 
	 * @param array $grouped_selected Grouped selected options to set autoload for.
	 * @param string $autoload_value The value to set for autoload, typically "yes"/"no" (or "on"/"off" for WP 6.6.0 and above).
	 * 
	 * @return array Normally should return an array of options names that were not processed, but in this case it returns an empty array.
	 */
	private static function set_autoload( $grouped_selected, $autoload_value ) {

		global $wpdb;

		foreach ( $grouped_selected as $site_id => $group ) {

			if ( empty( $group ) )
				continue;

			$ids = array_column( $group, 'id' );
			$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			$sql = $wpdb->prepare(
				"UPDATE {$wpdb->options}
				SET autoload = %s
				WHERE option_id IN ( $ids_placeholder )",
				...array_merge( [ $autoload_value ], $ids )
			);

			$wpdb->query( $sql );

			// Clear the object cache so WordPress picks up the new autoload flags
			wp_cache_delete( 'alloptions', 'options' );

			ADBC_Sites::instance()->restore_blog();
		}

		return [];
	}

	/**
	 * Delete grouped options. Options are grouped by site ID as key.
	 *
	 * Expected input shape:
	 * [
	 *   1 => [ [ 'id' => 123, 'name' => 'foo' ], ... ],
	 *   2 => [ [ 'id' => 456, 'name' => 'bar' ], ... ],
	 * ]
	 *
	 * @param array $grouped_selected Grouped selected options to delete.
	 * @return array An array of option names that were not processed (not deleted).
	 */
	public static function delete_options( $grouped_selected ) {

		$cleanup_method = ADBC_Settings::instance()->get_setting( 'sql_or_native_cleanup_method' );

		if ( $cleanup_method === 'native' ) {
			return self::delete_options_native( $grouped_selected );
		}

		return self::delete_options_sql( $grouped_selected );

	}

	/**
	 * Deletes options using WordPress native delete_option() where safe.
	 *
	 * Important edge case:
	 * delete_option() effectively operates on a sanitized option name; if the stored option_name has
	 * leading/trailing spaces, delete_option( $name ) can be misleading (it will not target the exact row).
	 * For such cases, we fall back to SQL-by-ID even in "native" mode to avoid leaving undeletable rows.
	 *
	 * @param array $grouped_selected
	 * @return array Not processed option names.
	 */
	protected static function delete_options_native( $grouped_selected ) {

		global $wpdb;

		$not_processed = [];

		foreach ( $grouped_selected as $site_id => $group ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			foreach ( $group as $selected ) {

				// check if the option have a leading or ending space which will be trimmed by delete_option()
				$can_be_trimmed = $selected['name'] !== trim( $selected['name'] );

				$success = false;

				// try deleting using standard wordpress function if the trimming will not mislead the name
				if ( ! $can_be_trimmed )
					$success = delete_option( $selected['name'] );

				// try deleting using direct sql by option id to be sure there's no problem in the name
				if ( ! $success )
					$success = $wpdb->delete( $wpdb->options, array( 'option_id' => $selected['id'] ) );

				// if the deletion failed, add the option name to the not processed list
				if ( ! $success )
					$not_processed[] = $selected['name'];

			}

			ADBC_Sites::instance()->restore_blog();

		}

		return $not_processed;

	}

	/**
	 * Deletes options using direct SQL (bulk delete by option_id) for each site.
	 *
	 * @param array $grouped_selected
	 * @return array Not processed option names.
	 */
	protected static function delete_options_sql( $grouped_selected ) {

		global $wpdb;

		$not_processed = [];

		foreach ( $grouped_selected as $site_id => $group ) {

			$ids = [];
			foreach ( $group as $selected )
				$ids[] = $selected['id'];

			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

			// Bulk delete.
			$sql = "DELETE FROM {$wpdb->options} WHERE option_id IN ($placeholders)";
			$sql = $wpdb->prepare( $sql, ...$ids );
			$wpdb->query( $sql );

			// Identify any remaining rows (not processed) deterministically.
			$check_sql = "SELECT option_name FROM {$wpdb->options} WHERE option_id IN ($placeholders)";
			$check_sql = $wpdb->prepare( $check_sql, ...$ids );

			$remaining_names = $wpdb->get_col( $check_sql );

			if ( ! empty( $remaining_names ) )
				foreach ( $remaining_names as $opt_name )
					$not_processed[] = $opt_name;

			ADBC_Sites::instance()->restore_blog();

		}

		return $not_processed;

	}

	/**
	 * Get options names that exist from a list of options names.
	 * 
	 * @param array $options_names The list of options names to check.
	 * 
	 * @return array The list of options names that exist.
	 */
	public static function get_options_names_that_exists_from_list( $options_names ) {
		global $wpdb;

		// Normalize and validate input
		if ( empty( $options_names ) || ! is_array( $options_names ) )
			return [];

		// Build UNION branches over all sites' options tables
		$branches = [];
		$sites = ADBC_Sites::instance()->get_sites_list();

		// Prepare the IN() placeholders once (same for all branches)
		$in_placeholders = implode( ',', array_fill( 0, count( $options_names ), '%s' ) );

		foreach ( $sites as $site ) {
			$table = $site['prefix'] . 'options';

			// Each branch is a fully prepared subquery to safely embed into the UNION
			$sql = $wpdb->prepare(
				"SELECT DISTINCT option_name AS name
				 FROM `{$table}`
				 WHERE option_name IN ( {$in_placeholders} )
				   AND option_name NOT LIKE %s
				   AND option_name NOT LIKE %s",
				...array_merge( $options_names, [ '\_transient\_%', '\_site\_transient\_%' ] )
			);

			$branches[] = '(' . $sql . ')';
		}

		if ( empty( $branches ) )
			return [];

		$union_sql = implode( "\nUNION ALL\n", $branches );
		$query = "SELECT DISTINCT name FROM ( {$union_sql} ) AS existing_names";

		$existing_names = $wpdb->get_col( $query );

		return array_values( array_unique( array_filter( (array) $existing_names ) ) );
	}

}