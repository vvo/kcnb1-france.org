<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC post types model class.
 * 
 * This class provides the post types model.
 */
class ADBC_Post_Types {

	private const NON_PUBLIC_POST_TYPES_POST_COUNT_THRESHOLD = 100; // Post types with more than this number of posts are considered noteworthy.

	/**
	 * Get the post types list for the endpoint.
	 *
	 * @param array $filters Output of sanitize_filters().
	 *
	 * @return WP_REST_Response The list of post types.
	 */
	public static function get_post_types_list( $filters ) {

		global $wpdb;

		$sites_list = ADBC_Sites::instance()->get_sites_list( $filters['site_id'] );

		/* ──────────────────────────────────────────────────────────────
		 * 1. Collect post types per site from the database
		 * ─────────────────────────────────────────────────────────────*/
		$post_types_list = [];

		foreach ( $sites_list as $site ) {

			$table_name = $site['prefix'] . 'posts';

			$public_post_types_names = self::get_public_post_types_names_for_site( (int) $site['id'] );

			$results = $wpdb->get_results(
				"SELECT post_type, COUNT(*) AS posts_count
				 FROM {$table_name}
				 WHERE post_type != ''
				 GROUP BY post_type",
				OBJECT
			);

			if ( ! empty( $results ) ) {

				foreach ( $results as $row ) {
					$post_types_list[] = (object) [ 
						'name' => (string) $row->post_type,
						'posts_count' => (int) $row->posts_count,
						'site_id' => $site['id'],
						'is_public' => isset( $public_post_types_names[ $row->post_type ] ),
					];
				}

			}

		}

		/* ──────────────────────────────────────────────────────────────
		 * 2. Load scan / hardcoded categorization onto items
		 * ─────────────────────────────────────────────────────────────*/
		if ( ADBC_VERSION_TYPE === 'PREMIUM' )
			ADBC_Scan_Results::instance()->load_scan_results_to_items_rows( $post_types_list, 'post_types' );
		else
			ADBC_Common_Model::load_scan_results_to_items_for_free_version( $post_types_list );

		ADBC_Hardcoded_Items::instance()->load_hardcoded_scan_results_to_items_rows( $post_types_list, 'post_types' );

		/* ──────────────────────────────────────────────────────────────
		 * 3. Apply filters (belongs_to, posts_count, search) and count categories
		 * ─────────────────────────────────────────────────────────────*/
		$min_posts_count = $filters['post_types_posts_count'];
		$search_for = strtolower( $filters['search_for'] );
		$post_types_public = $filters['post_types_visibility'];

		$scan_counter = new ADBC_Scan_Counter();

		$filtered_post_types_list = [];

		foreach ( $post_types_list as $post_type ) {

			// Filter by posts count
			if ( $min_posts_count > 0 && $post_type->posts_count <= $min_posts_count )
				continue;

			// Public / non-public filter
			if ( $post_types_public === 'public' && ! $post_type->is_public )
				continue;
			if ( $post_types_public === 'non_public' && $post_type->is_public )
				continue;

			// Search filter
			if ( $search_for !== '' && strpos( strtolower( $post_type->name ), $search_for ) === false )
				continue;

			$scan_counter->refresh_categorization_count( $post_type->belongs_to );

			if ( ! ADBC_Common_Model::is_item_satisfies_belongs_to( $filters, $post_type->belongs_to ) )
				continue;

			$filtered_post_types_list[] = $post_type;

		}

		/* ──────────────────────────────────────────────────────────────
		 * 4. Sorting
		 * ─────────────────────────────────────────────────────────────*/
		$sort_by = $filters['sort_by'];
		$sort_order = $filters['sort_order'];
		$allowed_columns = [ 
			'name',
			'posts_count',
			'site_id',
			'is_public',
		];

		if ( in_array( $sort_by, $allowed_columns, true ) ) {
			usort(
				$filtered_post_types_list,
				function ($a, $b) use ($sort_by, $sort_order) {
					if ( $sort_by === 'posts_count' ) {
						$cmp = $a->posts_count <=> $b->posts_count;
					} elseif ( $sort_by === 'site_id' ) {
						$cmp = $a->site_id <=> $b->site_id;
					} elseif ( $sort_by === 'is_public' ) {
						$cmp = (int) $a->is_public <=> (int) $b->is_public;
					} else {
						$cmp = strnatcasecmp( (string) $a->name, (string) $b->name );
					}
					return ( $sort_order === 'DESC' ) ? -$cmp : $cmp;
				}
			);
		}

		/* ──────────────────────────────────────────────────────────────
		 * 5. Pagination
		 * ─────────────────────────────────────────────────────────────*/
		$total_post_types = count( $filtered_post_types_list );
		$startRecord = ( $filters['current_page'] - 1 ) * $filters['items_per_page'];
		$paginated_post_types_list = array_slice( $filtered_post_types_list, $startRecord, $filters['items_per_page'] );
		$post_types_list = [];

		foreach ( $paginated_post_types_list as $post_type ) {

			$post_types_list[] = [ 
				'composite_id' => [ 
					'items_type' => 'post_types',
					'site_id' => (int) $post_type->site_id,
					'name' => $post_type->name,
				],
				'name' => $post_type->name,
				'posts_count' => $post_type->posts_count,
				'site_id' => $post_type->site_id,
				'is_public' => (bool) $post_type->is_public,
				'belongs_to' => $post_type->belongs_to,
				'known_plugins' => $post_type->known_plugins,
				'known_themes' => $post_type->known_themes,
			];
		}

		// Dictionary names
		if ( ADBC_VERSION_TYPE === 'PREMIUM' )
			ADBC_Dictionary::add_missing_addons_names_from_dictionary( $post_types_list, $scan_counter, 'post_types' );

		$total_real_pages = max( 1, ceil( $total_post_types / $filters['items_per_page'] ) );

		return ADBC_Rest::success( "", [ 
			'items' => $post_types_list,
			'total_items' => $total_post_types,
			'real_current_page' => min( $filters['current_page'], $total_real_pages ),
			'categorization_count' => $scan_counter->get_categorization_count(),
			'plugins_count' => $scan_counter->get_plugins_count(),
			'themes_count' => $scan_counter->get_themes_count(),
		] );
	}

	/**
	 * Get all distinct post type names for a site.
	 *	 *
	 * @param string $site_prefix Site table prefix (e.g. wp_ or wp_2_).
	 *
	 * @return array Flat list of post type names.
	 */
	public static function get_all_post_type_names_for_site( $site_prefix ) {

		global $wpdb;

		$table = $site_prefix . 'posts';

		$names = $wpdb->get_col(
			"SELECT DISTINCT post_type FROM `{$table}` WHERE post_type != ''"
		);

		return is_array( $names ) ? $names : [];

	}

	/**
	 * Get unique post type names across all sites.
	 * 
	 * @return array Associative array of post type slugs.
	 */
	public static function get_post_types_names() {

		global $wpdb;

		$all_post_types = [];

		$sites = ADBC_Sites::instance()->get_sites_list();

		foreach ( $sites as $site ) {

			$table_name = $site['prefix'] . 'posts';

			$post_types = $wpdb->get_col(
				"SELECT DISTINCT post_type FROM {$table_name} WHERE post_type != ''"
			);

			$all_post_types = array_merge( $all_post_types, $post_types );

		}

		return array_fill_keys( $all_post_types, true );

	}

	/**
	 * Count the total number of post types that are not scanned.
	 *
	 * @return int Total not-scanned post types.
	 */
	public static function count_total_not_scanned_post_types() {

		$total_not_scanned = 0;

		if ( ADBC_VERSION_TYPE === 'PREMIUM' )
			$registered_post_types_dictionary = array_keys( ADBC_Registered_Post_Types_Dict_Tracker::load_dictionary_from_file() );

		$sites_list = ADBC_Sites::instance()->get_sites_list();

		foreach ( $sites_list as $site ) {

			$post_types = self::get_all_post_type_names_for_site( $site['prefix'] );

			if ( ADBC_VERSION_TYPE === 'PREMIUM' ) {
				$post_types = array_diff( $post_types, $registered_post_types_dictionary ); // don't count the registered post types in dictionary as not scanned
				$not_scanned_count = ADBC_Scan_Utils::count_not_scanned_items_in_list( 'post_types', $post_types );
			} else {
				$not_scanned_count = ADBC_Common_Model::count_not_scanned_items_in_list_for_free( 'post_types', $post_types );
			}

			$total_not_scanned += $not_scanned_count;

		}

		return $total_not_scanned;

	}

	/**
	 * Count the total number of non-public post types with a large number of posts.
	 *
	 * @return int Total count of non-public post types above the threshold.
	 */
	public static function count_total_large_non_public_post_types() {

		global $wpdb;

		$total = 0;
		$threshold = self::NON_PUBLIC_POST_TYPES_POST_COUNT_THRESHOLD;

		$sites_list = ADBC_Sites::instance()->get_sites_list();

		foreach ( $sites_list as $site ) {

			$table_name = $site['prefix'] . 'posts';
			$public_post_types_names = self::get_public_post_types_names_for_site( (int) $site['id'] );

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_type, COUNT(*) AS posts_count
					 FROM {$table_name}
					 WHERE post_type != ''
					 GROUP BY post_type
					 HAVING COUNT(*) >= %d",
					$threshold
				),
				OBJECT
			);

			if ( ! empty( $results ) ) {
				foreach ( $results as $row ) {
					if ( ! isset( $public_post_types_names[ $row->post_type ] ) ) {
						$total++;
					}
				}
			}
		}

		return $total;

	}

	/**
	 * Get total post types count.
	 *
	 * @return int Sum of unique post types per site across the network.
	 */
	public static function get_total_post_types_count() {

		global $wpdb;

		$total_post_types = 0;

		$sites_list = ADBC_Sites::instance()->get_sites_list();

		foreach ( $sites_list as $site ) {

			$table_name = $site['prefix'] . 'posts';

			$site_unique_count = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_type) FROM {$table_name}" );

			$total_post_types += $site_unique_count;

		}

		return $total_post_types;

	}

	/**
	 * Get a paginated list of posts for a given post type in a given site.
	 *
	 * @param int    $site_id    The site ID.
	 * @param string $post_type  The post type name.
	 * @param array  $filters    Sanitized filters (current_page, items_per_page, sort_by, sort_order).
	 *
	 * @return array Paginated result with items, total_items, and real_current_page.
	 */
	public static function get_posts_rows( $site_id, $post_type, $filters ) {

		global $wpdb;

		// All filters are already validated, casted and defaulted at the endpoint level.
		$current_page = $filters['current_page'];
		$items_per_page = $filters['items_per_page'];
		$sort_by = $filters['sort_by'];
		$sort_order = $filters['sort_order'];

		$prefix = ADBC_Sites::instance()->get_prefix_from_site_id( $site_id );
		$table_name = $prefix . 'posts';

		// Validate sort_by against real columns of the table. If invalid, do not sort.
		$allowed_columns = $wpdb->get_col( "SHOW COLUMNS FROM {$table_name}", 0 );
		$order_by_sql = '';

		if ( in_array( $sort_by, $allowed_columns, true ) ) {
			$order_by_sql = "ORDER BY `{$sort_by}` {$sort_order}";
		}

		// Total posts for this post type in this site.
		$total_items = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE post_type = %s",
				$post_type
			)
		);

		$offset = ( $current_page - 1 ) * $items_per_page;

		// Fetch the requested page of rows.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE post_type = %s {$order_by_sql} LIMIT %d OFFSET %d",
				$post_type,
				$items_per_page,
				$offset
			),
			ARRAY_A
		);

		$total_real_pages = $items_per_page > 0
			? max( 1, (int) ceil( $total_items / $items_per_page ) )
			: 1;

		return [ 
			'items' => is_array( $rows ) ? $rows : [],
			'total_items' => $total_items,
			'real_current_page' => min( $current_page, $total_real_pages ),
		];

	}

	/**
	 * Delete all posts belonging to the selected post types, grouped by site ID.
	 *
	 * Expected input shape:
	 * [
	 *   1 => [ [ 'name' => 'my_cpt' ], ... ],
	 *   2 => [ [ 'name' => 'another_cpt' ], ... ],
	 * ]
	 *
	 * @param array $grouped_selected Post types grouped by site ID.
	 * @return array Post type names that could not be fully purged.
	 */
	public static function delete_posts( $grouped_selected ) {

		$cleanup_method = ADBC_Settings::instance()->get_setting( 'sql_or_native_cleanup_method' );

		if ( $cleanup_method === 'native' )
			return self::delete_posts_native( $grouped_selected );

		return self::delete_posts_sql( $grouped_selected );

	}

	/**
	 * Delete posts using WordPress native wp_delete_post().
	 *
	 * @param array $grouped_selected Post types grouped by site ID.
	 * @return array Post type names that could not be fully purged.
	 */
	protected static function delete_posts_native( $grouped_selected ) {

		global $wpdb;

		$not_processed = [];

		foreach ( $grouped_selected as $site_id => $group ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			foreach ( $group as $selected ) {

				$post_type = $selected['name'];

				$post_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
						$post_type
					)
				);

				if ( empty( $post_ids ) )
					continue;

				$has_failure = false;

				foreach ( $post_ids as $post_id ) {
					$result = wp_delete_post( (int) $post_id, true );
					if ( ! $result )
						$has_failure = true;
				}

				if ( $has_failure ) {
					$remaining = (int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
							$post_type
						)
					);
					if ( $remaining > 0 )
						$not_processed[] = $post_type;
				}
			}

			ADBC_Sites::instance()->restore_blog();
		}

		return $not_processed;

	}

	/**
	 * Delete posts using direct SQL for each post type per site.
	 *
	 * @param array $grouped_selected Post types grouped by site ID.
	 * @return array Post type names that could not be fully purged.
	 */
	protected static function delete_posts_sql( $grouped_selected ) {

		global $wpdb;

		$not_processed = [];

		foreach ( $grouped_selected as $site_id => $group ) {

			$prefix = ADBC_Sites::instance()->get_prefix_from_site_id( $site_id );
			$table_name = $prefix . 'posts';

			foreach ( $group as $selected ) {

				$post_type = $selected['name'];

				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$table_name} WHERE post_type = %s",
						$post_type
					)
				);

				$remaining = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$table_name} WHERE post_type = %s",
						$post_type
					)
				);

				if ( $remaining > 0 )
					$not_processed[] = $post_type;
			}

		}

		return $not_processed;

	}

	/**
	 * Get public post types names for a given site.
	 * 
	 * @param int $site_id The site ID.
	 * @return array Associative array of public post types names as keys and true as values.
	 */
	protected static function get_public_post_types_names_for_site( $site_id ) {

		// Cache the public post types names for each site to avoid multiple calls to the database if the site public post types names were already fetched.
		static $cache = [];

		if ( isset( $cache[ $site_id ] ) ) {
			return $cache[ $site_id ];
		}

		$sites = ADBC_Sites::instance();

		$sites->switch_to_blog_id( $site_id );

		$public_post_types = get_post_types(
			[ 
				'public' => true,
			],
			'names'
		);

		$sites->restore_blog();

		$cache[ $site_id ] = is_array( $public_post_types )
			? array_fill_keys( $public_post_types, true )
			: [];

		return $cache[ $site_id ];

	}

}
