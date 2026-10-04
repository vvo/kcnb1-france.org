<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC users meta class.
 * 
 * This class provides the users meta functions.
 */
class ADBC_Users_Meta {

	private const BIG_USERMETA_THRESHOLD_WARNING = 150 * 1024; // 150 KB. (If you change this value, change it as well in js filter message and slice)
	private const TRUNCATE_LENGTH = 20; // Length to truncate the meta value for display

	/**
	 * Get the users meta list for the endpoint.
	 *
	 * @param array $filters Output of sanitize_filters().
	 *
	 * @return WP_REST_Response The list of users meta.
	 */
	public static function get_users_meta_list( $filters ) {

		// Prepare variables
		$users_meta_list = [];
		$total_users_meta = 0;

		$scan_counter = new ADBC_Scan_Counter();

		$startRecord = ( $filters['current_page'] - 1 ) * $filters['items_per_page'];
		$endRecord = $startRecord + $filters['items_per_page'];
		$currentRecord = 0;

		$limit = ADBC_Settings::instance()->get_setting( 'database_rows_batch' );
		$offset = 0;

		do { // Loop through all users meta in batches of $limit to avoid memory issues

			$users_meta = self::get_users_meta_list_batch( $filters, $limit, $offset );
			$fetched_count = count( $users_meta );

			if ( ADBC_VERSION_TYPE === 'PREMIUM' )
				ADBC_Scan_Results::instance()->load_scan_results_to_items_rows( $users_meta, 'users_meta' ); // Load scan results to the users meta rows
			else
				ADBC_Common_Model::load_scan_results_to_items_for_free_version( $users_meta ); // Load scan results to the users meta rows for free version

			ADBC_Hardcoded_Items::instance()->load_hardcoded_scan_results_to_items_rows( $users_meta, 'users_meta' ); // Load hardcoded items to the users meta rows

			foreach ( $users_meta as $index => $user_meta ) {

				$scan_counter->refresh_categorization_count( $user_meta->belongs_to );

				if ( ! ADBC_Common_Model::is_item_satisfies_belongs_to( $filters, $user_meta->belongs_to ) )
					continue;

				$total_users_meta++; // Count users meta that satisfy all filters and belongs_to

				// Only process the current batch if it's within the desired page range
				if ( $currentRecord >= $startRecord && $currentRecord < $endRecord ) {

					$users_meta_list[] = [ 
						// This id is used to identify the user meta in the frontend and take actions on it
						'composite_id' => [ 
							'items_type' => 'users_meta',
							'site_id' => $user_meta->site_id,
							'id' => (int) $user_meta->umeta_id,
							'name' => $user_meta->name,
						],
						'id' => $user_meta->umeta_id,
						'name' => $user_meta->name, // Used in the known addons modal & "show value modal". To be generic and work for all items types.
						'meta_key' => $user_meta->name,
						'value' => $user_meta->value,
						'size' => $user_meta->size,
						'user_id' => $user_meta->user_id,
						'site_id' => $user_meta->site_id,
						'belongs_to' => $user_meta->belongs_to,
						'known_plugins' => $user_meta->known_plugins,
						'known_themes' => $user_meta->known_themes,
					];
				}

				$currentRecord++;
			}

			$offset += $limit;

		} while ( $fetched_count == $limit ); // Continue if the last batch was full

		// Loop over the $users_meta_list and $scan_counter add the plugins/themes names from the dictionary if they are empty
		// This is because load_scan_results_to_rows() only loads the names of the plugins/themes that are currently installed
		if ( ADBC_VERSION_TYPE === 'PREMIUM' )
			ADBC_Dictionary::add_missing_addons_names_from_dictionary( $users_meta_list, $scan_counter, 'users_meta' );

		// Calculate total number of pages to verify that the current page sent by the user is within the range
		$total_real_pages = max( 1, ceil( $total_users_meta / $filters['items_per_page'] ) );

		return ADBC_Rest::success( "", [ 
			'items' => $users_meta_list,
			'total_items' => $total_users_meta,
			'real_current_page' => min( $filters['current_page'], $total_real_pages ),
			'categorization_count' => $scan_counter->get_categorization_count(),
			'plugins_count' => $scan_counter->get_plugins_count(),
			'themes_count' => $scan_counter->get_themes_count(),
		] );
	}

	/**
	 * Get the users meta list that satisfy the UI filters.
	 *
	 * @param array $filters Output of sanitize_filters().
	 * @param int   $limit   Limit for the number of rows to return.
	 * @param int   $offset  Offset for the number of rows to return.
	 *
	 * @return array List of users meta that satisfy the filters.
	 */
	private static function get_users_meta_list_batch( $filters, $limit, $offset ) {

		global $wpdb;
		$truncate_length = self::TRUNCATE_LENGTH;
		$site_id = get_current_blog_id(); // get the main site id

		$params[] = absint( $site_id );
		$duplicate_join_sql = '';

		/* ────────────────────────────────────────────────────────────
		 * Build a safe ORDER BY clause
		 * ────────────────────────────────────────────────────────────*/
		$allowed_columns = [ 
			'meta_key' => '`name`',
			'size' => 'size',
			'user_id' => '`user_id`',
		];

		$sort_col = $filters['sort_by'] ?? '';
		$sort_dir = strtoupper( $filters['sort_order'] ?? 'ASC' );
		$sort_dir = ( $sort_dir === 'DESC' ) ? 'DESC' : 'ASC';

		// Add 'order by' clause if the column is allowed.
		$order_by_sql = isset( $allowed_columns[ $sort_col ] )
			? "ORDER BY {$allowed_columns[ $sort_col ]} {$sort_dir}"
			: '';

		/* ────────────────────────────────────────────────────────────
		 * Assemble the dynamic WHERE parts
		 * ────────────────────────────────────────────────────────────*/
		$where = [];

		/* — Unused filter — */
		if ( isset( $filters['unused'] ) && in_array( $filters['unused'], [ 'yes', 'no' ], true ) ) {
			$unused_sql = "main.user_id NOT IN (SELECT ID FROM {$wpdb->users})";
			if ( $filters['unused'] === 'yes' ) {
				$where[] = $unused_sql;
			} else {
				$where[] = "NOT ( {$unused_sql} )";
			}
		}

		/* — Size ≥ threshold — */
		if ( ! empty( $filters['size'] ) && (int) $filters['size'] > 0 ) {
			$bytes = ADBC_Common_Utils::convert_size_to_bytes(
				$filters['size'],
				$filters['size_unit']
			);
			$where[] = 'OCTET_LENGTH(main.`meta_value`) >= %d';
			$params[] = $bytes;
		}

		/* — Search filter — */
		if ( ! empty( $filters['search_for'] ) && ! empty( $filters['search_in'] ) ) {

			$needle = '%' . $wpdb->esc_like( $filters['search_for'] ) . '%';

			switch ( $filters['search_in'] ) {
				case 'name':
					$where[] = 'main.`meta_key` LIKE %s';
					$params[] = $needle;
					break;

				case 'value':
					$where[] = 'main.`meta_value` LIKE %s';
					$params[] = $needle;
					break;

				case 'all':
					// Search in both columns
					$where[] = '(main.`meta_key` LIKE %s OR main.`meta_value` LIKE %s)';
					$params[] = $needle;   // for meta_key
					$params[] = $needle;   // for meta_value
					break;
			}
		}

		/* — Duplicated filter (optimized, no correlated subquery) — */
		if ( isset( $filters['duplicated'] ) && in_array( $filters['duplicated'], [ 'yes', 'no' ], true ) ) {

			// Build a derived table of groups: per (user_id, meta_key, value-hash),
			// compute min(umeta_id) and count(*).
			$dup_subquery = "
				SELECT
					user_id,
					meta_key,
					CRC32(meta_value) AS vhash,
					MIN(umeta_id)     AS min_umeta_id,
					COUNT(*)          AS cnt
				FROM {$wpdb->usermeta}
				GROUP BY user_id, meta_key, CRC32(meta_value)
			";

			$duplicate_join_sql = "
				LEFT JOIN ( {$dup_subquery} ) dupg
					ON dupg.user_id  = main.user_id
				AND dupg.meta_key = main.meta_key
				AND dupg.vhash    = CRC32(main.meta_value)
			";

			if ( $filters['duplicated'] === 'yes' ) {
				// Only rows that belong to a group with more than one row,
				// AND that are NOT the minimal umeta_id (i.e. the “duplicate” ones).
				$where[] = '(dupg.cnt > 1 AND main.umeta_id > dupg.min_umeta_id)';
			} else {
				// Only rows that are NOT duplicates:
				// - either not in any group (cnt is NULL)
				// - or they are the minimal umeta_id in their group.
				$where[] = '(dupg.cnt IS NULL OR main.umeta_id = dupg.min_umeta_id)';
			}
		}

		$where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';
		$params[] = absint( $limit );
		$params[] = absint( $offset );

		// Final SQL and fetch
		$sql = $wpdb->prepare(
			"SELECT
				main.`meta_key`                         			AS name,
				main.`umeta_id`                         			AS umeta_id,
				main.`user_id`                          			AS user_id,
				%d						   							AS site_id,
				SUBSTRING(main.`meta_value`, 1, {$truncate_length}) AS value,
				OCTET_LENGTH(main.`meta_value`)         			AS size
			FROM {$wpdb->usermeta} main
			{$duplicate_join_sql}
			{$where_sql}
			{$order_by_sql}
			LIMIT %d OFFSET %d
			",
			...$params
		);

		return $wpdb->get_results( $sql, OBJECT );
	}

	/**
	 * Get the count of big users meta.
	 * 
	 * @return int Total count of big users meta.
	 */
	public static function count_big_users_meta() {

		global $wpdb;

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				 FROM {$wpdb->usermeta}
				 WHERE OCTET_LENGTH(meta_value) > %d
				",
				self::BIG_USERMETA_THRESHOLD_WARNING
			)
		);

		return $count;
	}

	/**
	 * Count the total number of users meta that are not scanned.
	 * 
	 * @return int Total not scanned users meta.
	 */
	public static function count_total_not_scanned_users_meta() {

		$total_not_scanned = 0;
		$limit = ADBC_Settings::instance()->get_setting( 'database_rows_batch' );
		$offset = 0;

		do { // Loop through all users meta in batches of $limit to avoid memory issues

			$users_meta = self::get_users_meta_names( '', $limit, $offset, false );
			$fetched_count = count( $users_meta );
			$not_scanned_count = 0;

			if ( ADBC_VERSION_TYPE === 'PREMIUM' )
				$not_scanned_count = ADBC_Scan_Utils::count_not_scanned_items_in_list( 'users_meta', $users_meta );
			else
				$not_scanned_count = ADBC_Common_Model::count_not_scanned_items_in_list_for_free( 'users_meta', $users_meta );

			$total_not_scanned += $not_scanned_count;

			$offset += $limit;

		} while ( $fetched_count == $limit ); // Continue if the last batch was full

		return $total_not_scanned;

	}

	/**
	 * Get users meta keys for a specific site.
	 * 
	 * @param string $site_prefix Site prefix (not used for usermeta as it's a single table).
	 * @param int $limit Limit.
	 * @param int $offset Offset.
	 * @param boolean $keyed wether or not to key the array by names
	 * 
	 * @return array Users meta keys.
	 */
	public static function get_users_meta_names( $site_prefix, $limit, $offset, $keyed = true ) {

		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT meta_key
			 FROM {$wpdb->usermeta} 
			 LIMIT %d OFFSET %d",
			absint( $limit ),
			absint( $offset )
		);

		$users_meta_names = $wpdb->get_col( $query );

		if ( $keyed )
			return array_fill_keys( $users_meta_names, true );
		else
			return $users_meta_names;

	}

	/**
	 * Get users meta keys from their ids in a specific site prefix.
	 * 
	 * @param string $site_prefix Site prefix (not used for usermeta as it's a single table).
	 * @param array $users_meta_ids Users meta ids to get their keys.
	 * 
	 * @return array Associative users meta keys.
	 */
	public static function get_users_meta_names_from_ids( $site_prefix, $users_meta_ids ) {

		global $wpdb;

		if ( empty( $users_meta_ids ) )
			return [];

		$in_placeholders = implode( ',', array_fill( 0, count( $users_meta_ids ), '%d' ) );

		$query = $wpdb->prepare(
			"SELECT meta_key
			 FROM {$wpdb->usermeta}
			 WHERE umeta_id IN ($in_placeholders)",
			...$users_meta_ids
		);

		$users_meta_names = $wpdb->get_col( $query );

		// transform the users_meta names array to associative array with the option name as key and true as value
		$users_meta_names = array_fill_keys( $users_meta_names, true );

		return $users_meta_names;

	}

	/**
	 * Delete grouped users meta.
	 *
	 * @param array $selected_items Selected users meta to delete.
	 *
	 * @return array An array of users meta names that were not processed (not deleted).
	 */
	public static function delete_users_meta( $selected_items ) {

		$cleanup_method = ADBC_Settings::instance()->get_setting( 'sql_or_native_cleanup_method' );

		if ( $cleanup_method === 'native' ) {
			return self::delete_users_meta_native( $selected_items );
		}

		return self::delete_users_meta_sql( $selected_items );

	}

	/**
	 * Deletes users meta using WordPress native delete logic (current logic, unchanged).
	 *
	 * @param array $selected_items
	 * @return array Not processed users meta names.
	 */
	protected static function delete_users_meta_native( $selected_items ) {

		global $wpdb;

		$not_processed = [];

		foreach ( $selected_items as $selected ) {

			// try deleting using standard wordpress function
			$success = delete_metadata_by_mid( 'user', $selected['id'] );

			// try deleting using direct sql by umeta id to be sure there's no problem in the name
			if ( ! $success )
				$success = $wpdb->delete( $wpdb->usermeta, array( 'umeta_id' => $selected['id'] ) );

			if ( ! $success )
				$not_processed[] = $selected['name'];

		}

		return $not_processed;

	}

	/**
	 * Deletes users meta using direct SQL (bulk delete by umeta_id).
	 *
	 * @param array $selected_items
	 * @return array Not processed users meta names.
	 */
	protected static function delete_users_meta_sql( $selected_items ) {

		global $wpdb;

		$not_processed = [];

		$ids = [];
		foreach ( $selected_items as $selected )
			$ids[] = $selected['id'];

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// Bulk delete.
		$sql = "DELETE FROM {$wpdb->usermeta} WHERE umeta_id IN ($placeholders)";
		$sql = $wpdb->prepare( $sql, ...$ids );
		$wpdb->query( $sql );

		// Identify any remaining rows (not processed) deterministically (names == meta_key).
		$check_sql = "SELECT meta_key FROM {$wpdb->usermeta} WHERE umeta_id IN ($placeholders)";
		$check_sql = $wpdb->prepare( $check_sql, ...$ids );

		$not_processed = $wpdb->get_col( $check_sql );

		return $not_processed;

	}

	/**
	 * Get users meta names that still exist from a provided list.
	 * Note: usermeta is a single table for the whole installation (main site), no per-site variants.
	 *
	 * @param array $users_meta_names List of meta_key names to check for existence.
	 *
	 * @return array Existing names found in usermeta table.
	 */
	public static function get_users_meta_names_that_exists_from_list( $users_meta_names ) {

		global $wpdb;

		if ( empty( $users_meta_names ) || ! is_array( $users_meta_names ) )
			return [];

		$in_placeholders = implode( ',', array_fill( 0, count( $users_meta_names ), '%s' ) );

		$sql = $wpdb->prepare(
			"SELECT DISTINCT meta_key AS name
			 FROM {$wpdb->usermeta}
			 WHERE meta_key IN ( {$in_placeholders} )",
			...$users_meta_names
		);

		$existing_names = $wpdb->get_col( $sql );

		return array_values( array_unique( array_filter( (array) $existing_names ) ) );
	}

	/** 
	 * Count duplicated usermeta
	 * 
	 * @return int Total duplicated users meta.
	 */
	public static function count_duplicated_users_meta() {
		global $wpdb;

		return (int) $wpdb->get_var( "
			SELECT COALESCE(SUM(g.cnt - 1), 0)
			FROM (
				SELECT
					user_id,
					meta_key,
					CRC32(meta_value) AS vhash,
					COUNT(*)          AS cnt
				FROM {$wpdb->usermeta}
				GROUP BY user_id, meta_key, CRC32(meta_value)
				HAVING cnt > 1
			) AS g
		" );
	}

	/**
	 * Count unused usermeta (user_id not existing)
	 * 
	 * @return int Total unused users meta.
	 */
	public static function count_unused_users_meta() {
		global $wpdb;
		return (int) $wpdb->get_var( "
			SELECT COUNT(*)
			FROM {$wpdb->usermeta} main
			LEFT JOIN {$wpdb->users} u ON u.ID = main.user_id
			WHERE u.ID IS NULL
		" );
	}

	/**
	 * Get total users meta count.
	 * 
	 * @return int Total users meta count.
	 */
	public static function get_total_users_meta_count() {
		global $wpdb;
		return (int) $wpdb->get_var( "
			SELECT COUNT(*)
			FROM {$wpdb->usermeta}
		" );
	}

}