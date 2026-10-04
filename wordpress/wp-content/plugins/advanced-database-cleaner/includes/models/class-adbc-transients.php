<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC transients class.
 * 
 * This class provides the transients functions
 *  
 */
class ADBC_Transients {

	private const BIG_TRANSIENTS_THRESHOLD_WARNING = 150 * 1024; // 150 KB. (If you change this value, change it as well in js filter message and slice)
	private const TRUNCATE_LENGTH = 20; // Length to truncate the transient value for display

	/**
	 * Get the transients list for the endpoint.
	 *
	 * @param array $filters Output of sanitize_filters().
	 *
	 * @return WP_REST_Response The list of transients.
	 */
	public static function get_transients_list( $filters ) {

		// Prepare variables
		$transients_list = [];
		$total_transients = 0;

		$scan_counter = new ADBC_Scan_Counter();

		$startRecord = ( $filters['current_page'] - 1 ) * $filters['items_per_page'];
		$endRecord = $startRecord + $filters['items_per_page'];
		$currentRecord = 0;

		$limit = ADBC_Settings::instance()->get_setting( 'database_rows_batch' );
		$offset = 0;

		do { // Loop through all transients in batches of $limit to avoid memory issues

			$transients = self::get_transients_list_batch( $filters, $limit, $offset );
			$fetched_count = count( $transients );

			if ( ADBC_VERSION_TYPE === 'PREMIUM' )
				ADBC_Scan_Results::instance()->load_scan_results_to_items_rows( $transients, 'transients' ); // Load scan results to the transients rows
			else
				ADBC_Common_Model::load_scan_results_to_items_for_free_version( $transients ); // Load scan results to the transients rows for free version

			ADBC_Hardcoded_Items::instance()->load_hardcoded_scan_results_to_items_rows( $transients, 'transients' ); // Load hardcoded items to the transients rows

			foreach ( $transients as $index => $transient ) {

				$scan_counter->refresh_categorization_count( $transient->belongs_to );

				if ( ! ADBC_Common_Model::is_item_satisfies_belongs_to( $filters, $transient->belongs_to ) )
					continue;

				$total_transients++; // Count transients that satisfy all filters and belongs_to

				// Only process the current batch if it's within the desired page range
				if ( $currentRecord >= $startRecord && $currentRecord < $endRecord ) {

					$transients_list[] = [ 
						// This id is used to identify the option in the frontend and take actions on it
						'composite_id' => [ 
							'items_type' => 'transients',
							'site_id' => (int) $transient->site_id,
							'id' => (int) $transient->id,
							'found_in' => $transient->found_in,
							'name' => $transient->name,
						],
						'id' => $transient->id,
						'name' => $transient->name, // Used in the known addons modal & "show value modal". To be generic and work for all items types.
						'transient_name' => $transient->name,
						'value' => $transient->value,
						'expired' => $transient->expired,
						'timeout' => $transient->timeout,
						'found_in' => $transient->found_in, // 'options' | 'sitemeta'
						'size' => $transient->size,
						'autoload' => $transient->autoload,
						'site_id' => $transient->site_id,
						'belongs_to' => $transient->belongs_to,
						'known_plugins' => $transient->known_plugins,
						'known_themes' => $transient->known_themes,
					];
				}

				$currentRecord++;
			}

			$offset += $limit;

		} while ( $fetched_count == $limit ); // Continue if the last batch was full

		// Loop over the $transients_list and $scan_counter add the plugins/themes names from the dictionary if they are empty
		// This is because load_scan_results_to_rows() only loads the names of the plugins/themes that are currently installed
		if ( ADBC_VERSION_TYPE === 'PREMIUM' )
			ADBC_Dictionary::add_missing_addons_names_from_dictionary( $transients_list, $scan_counter, 'transients' );

		// Calculate total number of pages to verify that the current page sent by the user is within the range
		$total_real_pages = max( 1, ceil( $total_transients / $filters['items_per_page'] ) );

		return ADBC_Rest::success( "", [ 
			'items' => $transients_list,
			'total_items' => $total_transients,
			'real_current_page' => min( $filters['current_page'], $total_real_pages ),
			'categorization_count' => $scan_counter->get_categorization_count(),
			'plugins_count' => $scan_counter->get_plugins_count(),
			'themes_count' => $scan_counter->get_themes_count(),
		] );
	}

	/**
	 * Get the transients list that satisfy the UI filters.
	 *
	 * @param array $filters Output of sanitize_filters().
	 * @param int $limit Limit for the number of rows to return.
	 * @param int $offset Offset for the number of rows to return.
	 *
	 * @return array List of transients that satisfy the filters.
	 */
	private static function get_transients_list_batch( $filters, $limit, $offset ) {

		global $wpdb;

		$site_arg = $filters['site_id'];
		$sort_col = $filters['sort_by'];
		$sort_dir = $filters['sort_order'];

		/* ──────────────────────────────────────────────────────────────
		 * Build a safe ORDER BY clause
		 * ─────────────────────────────────────────────────────────────*/
		$allowed_columns = [ 
			'transient_name' => '`name`',
			'size' => '`size`',
			'autoload' => '`autoload`',
			'site_id' => '`site_id`',
			'expired' => '`expired`',
			'found_in' => '`found_in`',
		];

		$sort_col = $filters['sort_by'] ?? '';
		$sort_dir = strtoupper( $filters['sort_order'] ?? 'ASC' );
		$sort_dir = $sort_dir === 'DESC' ? 'DESC' : 'ASC';

		// Add 'order by' clause if the column is allowed.
		$maybe_order_by = isset( $allowed_columns[ $sort_col ] )
			? "ORDER BY {$allowed_columns[ $sort_col ]} {$sort_dir}"
			: '';

		$branch_batch_size = $offset + $limit;

		// ---- Build branches ---------------------------------------------
		$branches = [];

		$needs_collation_fix = ! ADBC_Database::is_collaction_unified( 'options', true, $site_arg );

		foreach ( ADBC_Sites::instance()->get_sites_list( $site_arg ) as $site ) {

			$branches = array_merge(
				$branches,
				self::build_branches_for_site(
					$site['id'],
					$filters, // pass whole arg set for WHERE glue
					$maybe_order_by, // pass sorting column and order or null
					$branch_batch_size,  // pass limit for this branch
					$needs_collation_fix
				)
			);

		}

		$union_sql = implode( "\nUNION ALL\n", $branches );

		$sql = "
			SELECT *
			FROM ( {$union_sql} ) AS rows_merged
			{$maybe_order_by}
			LIMIT %d OFFSET %d
		";

		$sql = $wpdb->prepare( $sql, $limit, $offset );

		return $wpdb->get_results( $sql, OBJECT );

	}

	/**
	 * Returns the prepared SQL condition to filter transients based on search term.
	 * This is used to search for transients by name or value.
	 * 
	 * @param array $filters The arguments for the size filter, including 'size' and 'size_unit'.
	 * @param array $template The sql template from which we get the table columns to search in (sitemeta, options)
	 * 
	 * @return string The prepared SQL condition for the search filter, or an empty string if no no search term is provided.
	 */
	private static function search_sql( $filters, $template ) {

		global $wpdb;

		$search_for = $filters['search_for'] ?? '';
		$search_in = $filters['search_in'] ?? 'all';
		$name_column = $template['name_col'];
		$value_column = $template['value_col'];

		if ( $search_for === '' ) {
			return '';
		}

		$like = '%' . $wpdb->esc_like( $search_for ) . '%';

		switch ( $search_in ) {
			case 'name':
				$search_sql = $wpdb->prepare( " AND {$name_column} LIKE %s", $like );
				break;
			case 'value':
				$search_sql = $wpdb->prepare( " AND {$value_column} LIKE %s", $like );
				break;
			default:
				$search_sql = $wpdb->prepare( " AND ( {$name_column} LIKE %s OR {$value_column} LIKE %s )", $like, $like );

		}

		return $search_sql;

	}

	/**
	 * Returns the prepared SQL condition to filter transients based on their size.
	 * This is used to filter transients by their total size across all columns.
	 * 
	 * @param array $filters The arguments for the size filter, including 'size' and 'size_unit'.
	 * @param array $template The sql template from which we get the table columns (sitemeta, options) to calculate the size for.
	 * 
	 * @return string The prepared SQL condition for the size filter, or an empty string if no size is specified.
	 */
	private static function size_sql( $filters, $template ) {

		global $wpdb;

		$size = $filters['size'] ?? 0;
		$size_unit = $filters['size_unit'] ?? 'B';

		if ( $size === 0 ) {
			return '';
		}

		$bytes = ADBC_Common_Utils::convert_size_to_bytes( $size, $size_unit );

		// ——— build the expression with safe NULL-to-0 handling ———
		if ( strpos( $template['name_col'], 'meta' ) !== false ) {
			// sitemeta
			$expr = '
            COALESCE( LENGTH(a.meta_id   ), 0 ) +
            COALESCE( LENGTH(a.meta_key  ), 0 ) +
            COALESCE( LENGTH(a.meta_value), 0 )
        ';
		} else {
			// options
			$expr = '
            COALESCE( LENGTH(a.option_id   ), 0 ) +
            COALESCE( LENGTH(a.option_name ), 0 ) +
            COALESCE( LENGTH(a.option_value), 0 ) +
            COALESCE( LENGTH(a.autoload    ), 0 )
        ';
		}

		// add the prepared comparison
		return $wpdb->prepare( " AND ( {$expr} ) >= %d", $bytes );
	}

	/**
	 * Returns the SQL templates array to fetch transients depending on the current site.
	 * 
	 * @return array The array of SQL templates to fetch transients for the current site, each item contains the 'sql', 'name_col' and 'val_col'
	 */
	private static function get_sql_templates() {

		global $wpdb;

		$length = self::TRUNCATE_LENGTH;
		$site_id = get_current_blog_id();

		$templates = [ 
			// Regular transients from options table
			[ 
				'sql' => "
					SELECT  a.option_name AS name,
							a.option_id  AS id,
							SUBSTRING(a.option_value,1,$length) AS value,
							b.option_value  AS timeout,
							{$site_id}      AS site_id,
							'options'       AS found_in,
							a.autoload      AS autoload,
							LENGTH(a.option_id) + LENGTH(a.option_name) + LENGTH(a.option_value) + LENGTH(a.autoload) AS size,
							CASE
							WHEN b.option_value IS NOT NULL AND b.option_value < UNIX_TIMESTAMP()
							THEN 'yes'
							ELSE 'no'
							END             AS expired
					FROM    {$wpdb->options} a
					LEFT JOIN {$wpdb->options} b
						ON b.option_name = CONCAT(
								'_transient_timeout_',
								SUBSTRING(a.option_name, CHAR_LENGTH('_transient_') + 1)
						)
					WHERE   a.option_name LIKE '\_transient\_%'
							AND a.option_name NOT LIKE '\_transient\_timeout\_%'
        		",
				'name_col' => 'a.option_name',
				'value_col' => 'a.option_value',
			],
			// Site transients from options table
			[ 
				'sql' => "
					SELECT  a.option_name AS name,
							a.option_id  AS id,
							SUBSTRING(a.option_value,1,$length) AS value,
							b.option_value  AS timeout,
							{$site_id}      AS site_id,
							'options'       AS found_in,
							a.autoload      AS autoload,
							LENGTH(a.option_id) + LENGTH(a.option_name) + LENGTH(a.option_value) + LENGTH(a.autoload) AS size,
							CASE
							WHEN b.option_value IS NOT NULL AND b.option_value < UNIX_TIMESTAMP()
							THEN 'yes'
							ELSE 'no'
							END             AS expired
					FROM    {$wpdb->options} a
					LEFT JOIN {$wpdb->options} b
						ON b.option_name = CONCAT(
								'_site_transient_timeout_',
								SUBSTRING(a.option_name, CHAR_LENGTH('_site_transient_') + 1)
						)
					WHERE   a.option_name LIKE '\_site\_transient\_%'
							AND a.option_name NOT LIKE '\_site\_transient\_timeout\_%'
				",
				'name_col' => 'a.option_name',
				'value_col' => 'a.option_value',
			],
		];

		// Add sitemeta template for multisite main site
		if ( is_multisite() && is_main_site( $site_id ) ) {
			$templates[] = [ 
				'sql' => "
					SELECT  a.meta_key      AS name,
						a.meta_id       AS id,
						SUBSTRING(a.meta_value,1,$length) AS value,
						b.timeout_value AS timeout,
						{$site_id}      AS site_id,
						'sitemeta'      AS found_in,
						'off'           AS autoload,
						LENGTH(a.meta_id) + LENGTH(a.meta_key) + LENGTH(a.meta_value) AS size,
						CASE
						WHEN b.timeout_value IS NOT NULL AND b.timeout_value < UNIX_TIMESTAMP()
						THEN 'yes'
						ELSE 'no'
						END             AS expired
					FROM    {$wpdb->sitemeta} a
					LEFT JOIN (
						SELECT  meta_key,
								MIN(CAST(meta_value AS UNSIGNED)) AS timeout_value
						FROM    {$wpdb->sitemeta}
						WHERE   meta_key LIKE '\_site\_transient\_timeout\_%'
						GROUP BY meta_key
					) b
						ON b.meta_key = CONCAT(
								'_site_transient_timeout_',
								SUBSTRING(a.meta_key, CHAR_LENGTH('_site_transient_') + 1)
						)
					WHERE   a.meta_key LIKE '\_site\_transient\_%'
							AND a.meta_key NOT LIKE '\_site\_transient\_timeout\_%'
				",
				'name_col' => 'a.meta_key',
				'value_col' => 'a.meta_value',
			];
		}

		return $templates;

	}

	/**
	 * Builds the prepared SQL branches for a specific site.
	 * This is used to fetch data from multiple sites in a multisite environment.
	 *
	 * @param int $site_id The site ID to build the branch for.
	 * @param array $filters The arguments for the query.
	 * @param string $maybe_order_by The ORDER BY clause if sorting is applied.
	 * @param int $sub_limit The limit to set for this branch.
	 * 
	 * @return array An array containing the SQL branch for the specified site.
	 */
	private static function build_branches_for_site( $site_id, $filters, $maybe_order_by, $sub_limit, $needs_collation_fix = false ) {

		global $wpdb;

		ADBC_Sites::instance()->switch_to_blog_id( $site_id );

		$branches = [];

		$collation = ! empty( $wpdb->collate ) ? $wpdb->collate : 'utf8mb4_unicode_ci';

		foreach ( self::get_sql_templates() as $template ) {

			$search = self::search_sql( $filters, $template );
			$size = self::size_sql( $filters, $template );
			$autoload = self::autoload_sql( $filters, $template );

			// Build the inner query first
			$inner_sql = "{$template['sql']}{$search}{$size}{$autoload}";

			if ( $needs_collation_fix ) {
				$inner_sql = "
					SELECT
						CONVERT(name USING utf8mb4) COLLATE {$collation} AS name,
						id,
						CONVERT(value USING utf8mb4) COLLATE {$collation} AS value,
						CONVERT(timeout USING utf8mb4) COLLATE {$collation} AS timeout,
						site_id,
						CONVERT(found_in USING utf8mb4) COLLATE {$collation} AS found_in,
						CONVERT(autoload USING utf8mb4) COLLATE {$collation} AS autoload,
						size,
						CONVERT(expired USING utf8mb4) COLLATE {$collation} AS expired
					FROM ( {$inner_sql} ) AS t
				";
			}

			// Check if we need to filter by expired status
			if ( $filters['expired'] !== 'all' ) {

				// Wrap in subquery to filter on calculated expired column
				$expired_filter = $filters['expired'] === 'yes' ? "expired = 'yes'" : "expired = 'no'";
				$sql = "
					SELECT * FROM (	{$inner_sql} ) AS inner_query 
					WHERE {$expired_filter}
					{$maybe_order_by}
					LIMIT %d
            	";

			} else {

				$sql = "
					{$inner_sql}
					{$maybe_order_by}
					LIMIT %d
            	";

			}

			$branches[] = '(' . $wpdb->prepare( $sql, $sub_limit ) . ')';

		}

		ADBC_Sites::instance()->restore_blog();

		return $branches;

	}

	/**
	 * Returns the prepared SQL condition to filter transients based on their autoload status.
	 * This is used to filter transients by their autoload status.
	 * 
	 * @param array $filters The arguments for the autoload filter, including 'autoload'.
	 * @param array $template The sql template from which we get the table columns to get the autoload column from. (sitemeta, options)
	 * 
	 * @return string The prepared SQL condition for the autoload filter, or an empty string if no autoload is specified.
	 */
	private static function autoload_sql( $filters, $template ) {

		$autoload_filter = $filters['autoload'] ?? 'all';

		if ( $autoload_filter === 'all' ) {
			return '';
		}

		// Determine the correct column name based on the template
		$autoload_column = strpos( $template['name_col'], 'meta' ) !== false
			? "'off'" // sitemeta doesn't have autoload, so we use a literal
			: 'a.autoload'; // options table uses a.autoload

		switch ( $autoload_filter ) {
			case 'yes':
				return " AND {$autoload_column} IN ('yes','on','auto-on','auto')";
			case 'no':
				return " AND {$autoload_column} NOT IN ('yes','on','auto-on','auto')";
			default:
				return '';
		}

	}

	/**
	 * Get transient names that still exist anywhere across the network from a provided list.
	 * This checks both options tables for all sites and the sitemeta table on the main site.
	 *
	 * @param array $transients_names List of transient option/meta keys to check for existence.
	 *
	 * @return array Existing names found across all sites.
	 */
	public static function get_transients_names_that_exists_from_list( $transients_names ) {

		global $wpdb;

		if ( empty( $transients_names ) || ! is_array( $transients_names ) )
			return [];

		$branches = [];
		$sites = ADBC_Sites::instance()->get_sites_list();
		$in_placeholders = implode( ',', array_fill( 0, count( $transients_names ), '%s' ) );

		foreach ( $sites as $site ) {
			$prefix = $site['prefix'];
			$options_table = $prefix . 'options';

			// Options branch (regular and site transients that may live in options)
			$sql_options = $wpdb->prepare(
				"SELECT DISTINCT option_name AS name
				 FROM `{$options_table}`
				 WHERE option_name IN ( {$in_placeholders} )
				   AND option_name NOT LIKE %s
				   AND option_name NOT LIKE %s",
				...array_merge( $transients_names, [ '\_transient\_timeout\_%', '\_site\_transient\_timeout\_%' ] )
			);
			$branches[] = '(' . $sql_options . ')';

			// Sitemeta branch only for multisite main site
			$site_id = $site['id'];
			if ( is_multisite() && $site_id !== null && is_main_site( $site_id ) ) {
				$sitemeta_table = $prefix . 'sitemeta';
				$sql_meta = $wpdb->prepare(
					"SELECT DISTINCT meta_key AS name
					 FROM `{$sitemeta_table}`
					 WHERE meta_key IN ( {$in_placeholders} )
					   AND meta_key NOT LIKE %s",
					...array_merge( $transients_names, [ '\_site\_transient\_timeout\_%' ] )
				);
				$branches[] = '(' . $sql_meta . ')';
			}
		}

		if ( empty( $branches ) )
			return [];

		$union_sql = implode( "\nUNION ALL\n", $branches );
		$query = "SELECT DISTINCT name FROM ( {$union_sql} ) AS existing_names";

		$existing_names = $wpdb->get_col( $query );

		return array_values( array_unique( array_filter( (array) $existing_names ) ) );
	}

	/**
	 * Get the count of big transients in all sites.
	 *
	 * @return int Total count of big transients.
	 */
	public static function count_big_transients() {

		global $wpdb;

		$threshold = self::BIG_TRANSIENTS_THRESHOLD_WARNING;

		// LIKE patterns for filtering transients
		$transient_like = $wpdb->esc_like( '_transient_' ) . '%';
		$site_transient_like = $wpdb->esc_like( '_site_transient_' ) . '%';
		$transient_timeout_like = $wpdb->esc_like( '_transient_timeout_' ) . '%';
		$site_transient_timeout_like = $wpdb->esc_like( '_site_transient_timeout_' ) . '%';

		$total_count = 0;

		// Loop through all sites in multisite, or just current site in single site
		$sites = ADBC_Sites::instance()->get_sites_list();

		foreach ( $sites as $site ) {

			$prefix = $site['prefix'];

			// Options table count
			$options_table = $prefix . 'options';

			$sql_options = $wpdb->prepare(
				"SELECT COUNT(*) 
				 FROM `{$options_table}`
				 WHERE ( option_name LIKE %s OR option_name LIKE %s )
				 AND option_name NOT LIKE %s
				 AND option_name NOT LIKE %s
				 AND LENGTH(option_value) > %d",
				$transient_like,
				$site_transient_like,
				$transient_timeout_like,
				$site_transient_timeout_like,
				$threshold
			);

			$total_count += (int) $wpdb->get_var( $sql_options );

			// Sitemeta table count (only for multisite main site)
			$site_id = $site['id'];

			if ( is_multisite() && $site_id !== null && is_main_site( $site_id ) ) {
				$sitemeta_table = $prefix . 'sitemeta';
				$sql_sitemeta = $wpdb->prepare(
					"SELECT COUNT(*)
					 FROM `{$sitemeta_table}`
					 WHERE meta_key LIKE %s
					 AND meta_key NOT LIKE %s
					 AND LENGTH(meta_value) > %d",
					$site_transient_like,
					$site_transient_timeout_like,
					$threshold
				);

				$total_count += (int) $wpdb->get_var( $sql_sitemeta );

			}

		}

		return $total_count;

	}

	/**
	 * Count the total number of transients that are not scanned.
	 * 
	 * @return int Total not scanned transients.
	 */
	public static function count_total_not_scanned_transients() {

		$sites_prefixes = array_keys( ADBC_Sites::instance()->get_all_prefixes() );
		$total_not_scanned = 0;
		$limit = ADBC_Settings::instance()->get_setting( 'database_rows_batch' );

		foreach ( $sites_prefixes as $site_prefix ) {

			$offset = 0;

			do { // Loop through all transients in batches of $limit to avoid memory issues

				$transients = self::get_transients_names( $site_prefix, $limit, $offset, false );
				$fetched_count = count( $transients );
				$not_scanned_count = 0;

				if ( ADBC_VERSION_TYPE === 'PREMIUM' )
					$not_scanned_count = ADBC_Scan_Utils::count_not_scanned_items_in_list( 'transients', $transients );
				else
					$not_scanned_count = ADBC_Common_Model::count_not_scanned_items_in_list_for_free( 'transients', $transients );

				$total_not_scanned += $not_scanned_count;

				$offset += $limit;

			} while ( $fetched_count == $limit ); // Continue if the last batch was full

		}

		return $total_not_scanned;

	}

	/**
	 * Get transients names for a specific site keyed by name
	 * 
	 * @param string $site_prefix Site prefix.
	 * @param int $limit Limit.
	 * @param int $offset Offset.
	 * @param boolean $keyed wether or not to key the array by names
	 * 
	 * @return array Associative transients names.
	 */
	public static function get_transients_names( $site_prefix, $limit, $offset, $keyed = true ) {

		global $wpdb;

		// LIKE patterns
		$transient_like = $wpdb->esc_like( '_transient_' ) . '%';
		$site_transient_like = $wpdb->esc_like( '_site_transient_' ) . '%';
		$transient_timeout_like = $wpdb->esc_like( '_transient_timeout_' ) . '%';
		$site_transient_timeout_like = $wpdb->esc_like( '_site_transient_timeout_' ) . '%';

		// Tables
		$options_table = $site_prefix . 'options';

		// Do we also have to union with sitemeta (multisite main site)?
		$prefix_site_id = ADBC_Sites::instance()->get_site_id_from_prefix( $site_prefix );
		$do_union = is_multisite() && $prefix_site_id !== null && is_main_site( $prefix_site_id );

		if ( $do_union ) {

			$sitemeta = $site_prefix . 'sitemeta';

			$sql = "
				SELECT name
				FROM (
					SELECT option_name AS name
					FROM `{$options_table}`
					WHERE ( option_name LIKE %s OR option_name LIKE %s )
					AND option_name NOT LIKE %s
					AND option_name NOT LIKE %s

					UNION ALL

					SELECT meta_key AS name
					FROM `{$sitemeta}`
					WHERE meta_key LIKE %s
					AND meta_key NOT LIKE %s
				) AS all_transients
				LIMIT %d OFFSET %d
			";

			$query = $wpdb->prepare(
				$sql,
				$transient_like,
				$site_transient_like,
				$transient_timeout_like,
				$site_transient_timeout_like,
				$site_transient_like,
				$site_transient_timeout_like,
				$limit,
				$offset
			);

		} else {
			// Single-site (or non-main multisite blog) – only options table
			$sql = "
				SELECT option_name AS name
				FROM `{$options_table}`
				WHERE ( option_name LIKE %s OR option_name LIKE %s )
				AND option_name NOT LIKE %s
				AND option_name NOT LIKE %s
				LIMIT %d OFFSET %d
			";

			$query = $wpdb->prepare(
				$sql,
				$transient_like,
				$site_transient_like,
				$transient_timeout_like,
				$site_transient_timeout_like,
				$limit,
				$offset
			);
		}

		$names = $wpdb->get_col( $query );

		if ( $keyed )
			return array_fill_keys( $names, true );
		else
			return $names;

	}

	/**
	 * Get transients names from their id|table in a specific site prefix.
	 *
	 * @param string $site_prefix Site prefix of the transients.
	 * @param array  $transients_map  [ <int ID> => 'options'|'sitemeta', … ]
	 *
	 * @return array Associative transients names.
	 */
	public static function get_transients_names_from_ids( $site_prefix, $transients_map ) {

		global $wpdb;

		if ( empty( $transients_map ) ) {
			return [];
		}

		// LIKE patterns
		$transient_like = $wpdb->esc_like( '_transient_' ) . '%';
		$site_transient_like = $wpdb->esc_like( '_site_transient_' ) . '%';
		$transient_timeout_like = $wpdb->esc_like( '_transient_timeout_' ) . '%';
		$site_transient_timeout_like = $wpdb->esc_like( '_site_transient_timeout_' ) . '%';

		// Partition incoming IDs by table
		$option_ids = $sitemeta_ids = [];
		foreach ( $transients_map as $transient_map ) {
			// get the key and value of the array
			$id = key( $transient_map );
			$table = $transient_map[ $id ];
			$table === 'options' ? $option_ids[] = (int) $id : $sitemeta_ids[] = (int) $id;
		}

		$transients_names = [];

		// Fetch from options
		if ( ! empty( $option_ids ) ) {

			$options_table = $site_prefix . 'options';

			$placeholders = implode( ',', array_fill( 0, count( $option_ids ), '%d' ) );

			$sql = "
				SELECT option_name AS name
				FROM `{$options_table}`
				WHERE option_id IN ( {$placeholders} )
				AND ( option_name LIKE %s OR option_name LIKE %s )
				AND option_name NOT LIKE %s
				AND option_name NOT LIKE %s
			"; // we add the second LIKE to avoid false positives 
			$args = array_merge( $option_ids,
				[ $transient_like,
					$site_transient_like,
					$transient_timeout_like,
					$site_transient_timeout_like
				]
			);

			$query = $wpdb->prepare( $sql, $args );

			$transients_names = $wpdb->get_col( $query );

		}

		// Determine if we need to fetch from sitemeta
		$prefix_site_id = ADBC_Sites::instance()->get_site_id_from_prefix( $site_prefix );
		$get_from_sitemeta = ! empty( $sitemeta_ids ) && is_multisite() && $prefix_site_id !== null && is_main_site( $prefix_site_id );

		// Fetch from wp_sitemeta (only site-wide transients)
		if ( $get_from_sitemeta ) {

			$sitemeta_table = $site_prefix . 'sitemeta';

			$placeholders = implode( ',', array_fill( 0, count( $sitemeta_ids ), '%d' ) );

			$sql = "
				SELECT meta_key AS name
				FROM `{$sitemeta_table}`
				WHERE meta_id IN ( {$placeholders} )
				AND meta_key LIKE %s
				AND meta_key NOT LIKE %s
			"; // we add the second LIKE to avoid false positives
			$args = array_merge( $sitemeta_ids, [ $site_transient_like, $site_transient_timeout_like ] );

			$query = $wpdb->prepare( $sql, $args );

			$transients_names = array_merge( $transients_names, $wpdb->get_col( $query ) );

		}

		return array_fill_keys( $transients_names, true );

	}

	/**
	 * Delete transients.
	 *
	 * @param array $grouped_selected Selected transients to delete grouped by site ID.
	 * @return array An array of transients names that were not processed.
	 */
	public static function delete_transients( $grouped_selected ) {

		$cleanup_method = ADBC_Settings::instance()->get_setting( 'sql_or_native_cleanup_method' );

		if ( $cleanup_method === 'native' ) {
			return self::delete_transients_native( $grouped_selected );
		}

		return self::delete_transients_sql( $grouped_selected );

	}

	/**
	 * Deletes transients using the current WordPress native delete logic (unchanged).
	 *
	 * @param array $grouped_selected
	 * @return array Not processed transients names.
	 */
	protected static function delete_transients_native( $grouped_selected ) {

		$not_processed = [];

		foreach ( $grouped_selected as $site_id => $group ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			foreach ( $group as $selected ) {

				$full_name = $selected['name'];   // full option / meta key
				$found_in = $selected['found_in'];     // 'options' | 'sitemeta'

				// site_transient
				if ( strpos( $full_name, '_site_transient_' ) === 0 ) {

					$base_name = substr( $full_name, 16 );      // strip prefix

					// Edge case where in multisite and site_transient is in options table
					if ( is_multisite() && $found_in === 'options' ) {

						if ( delete_option( $full_name ) ) {
							delete_option( "_site_transient_timeout_$base_name" );
						} else {
							$not_processed[] = $selected['name'];
						}

					} else {

						if ( ! delete_site_transient( $base_name ) ) {

							// fallback to direct delete in sitemeta table
							// This can happen if the transient have an invalid site ID in sitemeta
							if ( $found_in === 'sitemeta' ) {

								global $wpdb;

								$timeout_key = "_site_transient_timeout_{$base_name}";

								$sql = "DELETE FROM {$wpdb->sitemeta} WHERE meta_key IN ( %s, %s )";

								$deleted_rows = $wpdb->query(
									$wpdb->prepare( $sql, $full_name, $timeout_key )
								);

								if ( $deleted_rows !== false && $deleted_rows === 0 ) {
									$not_processed[] = $selected['name']; // delete using direct query failed
								}

							} else { // delete using native wordpress function failed
								$not_processed[] = $selected['name'];
							}

						}

					}

					continue;

				}

				// transient
				if ( strpos( $full_name, '_transient_' ) === 0 ) {

					$base_name = substr( $full_name, 11 );
					if ( ! delete_transient( $base_name ) ) {
						$not_processed[] = $selected['name'];
					}

				}

			}

			ADBC_Sites::instance()->restore_blog();

		}

		return $not_processed;

	}

	/**
	 * Deletes transients using direct SQL (bulk) for each site.
	 *
	 * @param array $grouped_selected
	 * @return array Not processed transients names.
	 */
	protected static function delete_transients_sql( $grouped_selected ) {

		global $wpdb;

		$not_processed = [];

		foreach ( $grouped_selected as $site_id => $group ) {

			$option_keys = [];
			$sitemeta_keys = [];

			foreach ( $group as $selected ) {

				$full_name = $selected['name'];     // full option / meta key
				$found_in = $selected['found_in']; // 'options' | 'sitemeta'

				// site_transient
				if ( strpos( $full_name, '_site_transient_' ) === 0 ) {

					$base_name = substr( $full_name, 16 );
					$timeout_key = "_site_transient_timeout_{$base_name}";

					if ( is_multisite() && $found_in === 'sitemeta' ) {
						$sitemeta_keys[] = $full_name;
						$sitemeta_keys[] = $timeout_key;
					} else {
						$option_keys[] = $full_name;
						$option_keys[] = $timeout_key;
					}

					continue;

				}

				// transient
				if ( strpos( $full_name, '_transient_' ) === 0 ) {

					$base_name = substr( $full_name, 11 );
					$timeout_key = "_transient_timeout_{$base_name}";

					$option_keys[] = $full_name;
					$option_keys[] = $timeout_key;

				}

			}

			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			// Bulk delete from options.
			if ( ! empty( $option_keys ) ) {

				$placeholders = implode( ',', array_fill( 0, count( $option_keys ), '%s' ) );

				$sql = "DELETE FROM {$wpdb->options} WHERE option_name IN ($placeholders)";
				$sql = $wpdb->prepare( $sql, ...$option_keys );
				$wpdb->query( $sql );

				$check_sql = "SELECT option_name FROM {$wpdb->options} WHERE option_name IN ($placeholders)";
				$check_sql = $wpdb->prepare( $check_sql, ...$option_keys );

				$remaining = $wpdb->get_col( $check_sql );

				if ( ! empty( $remaining ) )
					foreach ( $remaining as $name )
						$not_processed[] = $name;

			}

			// Bulk delete from sitemeta.
			if ( ! empty( $sitemeta_keys ) ) {

				$placeholders = implode( ',', array_fill( 0, count( $sitemeta_keys ), '%s' ) );

				$sql = "DELETE FROM {$wpdb->sitemeta} WHERE meta_key IN ($placeholders)";
				$sql = $wpdb->prepare( $sql, ...$sitemeta_keys );
				$wpdb->query( $sql );

				$check_sql = "SELECT meta_key FROM {$wpdb->sitemeta} WHERE meta_key IN ($placeholders)";
				$check_sql = $wpdb->prepare( $check_sql, ...$sitemeta_keys );

				$remaining = $wpdb->get_col( $check_sql );

				if ( ! empty( $remaining ) )
					foreach ( $remaining as $name )
						$not_processed[] = $name;

			}

			ADBC_Sites::instance()->restore_blog();

		}

		return $not_processed;

	}

	/**
	 * Set autoload to "no" for grouped transients. Transients are grouped by site ID as key.
	 * 
	 * @param array $grouped_selected Grouped selected transients to set autoload to "no".
	 * 
	 * @return array An array of transients names that were not processed.
	 */
	public static function set_autoload_to_no( $grouped_selected ) {

		$autoload_value = function_exists( 'wp_autoload_values_to_autoload' ) ? 'off' : 'no';
		$not_processed = self::set_autoload( $grouped_selected, $autoload_value );
		return $not_processed;

	}

	/**
	 * Set autoload to "yes" for grouped transients. Transients are grouped by site ID as key.
	 * 
	 * @param array $grouped_selected Grouped selected transients to set autoload to "yes".
	 * 
	 * @return array An array of transients names that were not processed.
	 */
	public static function set_autoload_to_yes( $grouped_selected ) {

		$autoload_value = function_exists( 'wp_autoload_values_to_autoload' ) ? 'on' : 'yes';
		$not_processed = self::set_autoload( $grouped_selected, $autoload_value );
		return $not_processed;

	}

	/**
	 * Change autoload value for transients. Transients are grouped by site ID as key.
	 * 
	 * @param array $grouped_selected Grouped selected transients to set autoload for.
	 * @param string $autoload_value The value to set for autoload, typically "yes"/"no" (or "on"/"off" for WP 6.6.0 and above).
	 * 
	 * @return array Normally should return an array of transients names that were not processed, but in this case it returns an empty array.
	 */
	private static function set_autoload( $grouped_selected, $autoload_value ) {

		global $wpdb;

		foreach ( $grouped_selected as $site_id => $group ) {

			if ( empty( $group ) )
				continue;

			// get only ids where found_in !== "sitemeta" because sitemeta transients doesn't have autoload
			$ids = array_column(
				array_filter( $group, function ($item) {
					return $item['found_in'] !== 'sitemeta';
				} ),
				'id'
			);

			if ( empty( $ids ) )
				continue;

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
	 * Get the total number of transients across all sites.
	 * 
	 * @return int Total number of transients.
	 */
	public static function get_total_transients_count() {

		global $wpdb;

		$total_transients = 0;

		$sites = ADBC_Sites::instance()->get_sites_list();

		// LIKE patterns for filtering transients
		$transient_like = $wpdb->esc_like( '_transient_' ) . '%';
		$site_transient_like = $wpdb->esc_like( '_site_transient_' ) . '%';
		$transient_timeout_like = $wpdb->esc_like( '_transient_timeout_' ) . '%';
		$site_transient_timeout_like = $wpdb->esc_like( '_site_transient_timeout_' ) . '%';

		foreach ( $sites as $site ) {

			$prefix = $site['prefix'];
			$options_table = $prefix . 'options';

			// Count transients stored in options (regular and site transients that may live in options)
			$sql_options = $wpdb->prepare(
				"SELECT COUNT(*)
				 FROM `{$options_table}`
				 WHERE ( option_name LIKE %s OR option_name LIKE %s )
				   AND option_name NOT LIKE %s
				   AND option_name NOT LIKE %s",
				$transient_like,
				$site_transient_like,
				$transient_timeout_like,
				$site_transient_timeout_like
			);

			$total_transients += (int) $wpdb->get_var( $sql_options );

			// Count site-wide transients stored in sitemeta (only for multisite main site)
			$site_id = $site['id'];
			if ( is_multisite() && $site_id !== null && is_main_site( $site_id ) ) {
				$sitemeta_table = $prefix . 'sitemeta';
				$sql_sitemeta = $wpdb->prepare(
					"SELECT COUNT(*)
					 FROM `{$sitemeta_table}`
					 WHERE meta_key LIKE %s
					   AND meta_key NOT LIKE %s",
					$site_transient_like,
					$site_transient_timeout_like
				);

				$total_transients += (int) $wpdb->get_var( $sql_sitemeta );
			}
		}

		return $total_transients;

	}

}