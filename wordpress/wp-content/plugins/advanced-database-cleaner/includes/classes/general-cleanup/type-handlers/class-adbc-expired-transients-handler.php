<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Class ADBC_Cleanup_Expired_Transients_Handler
 * 
 * This class handles the cleanup of expired transients in WordPress.
 */
class ADBC_Cleanup_Expired_Transients_Handler extends ADBC_Abstract_Cleanup_Handler {

	/** Required methods for ADBC_Cleanup_Type_Handler interface */
	protected function items_type() {
		return 'expired_transients';
	}
	protected function table() {
		return ''; // not used
	}
	protected function table_suffix() {
		return ''; // not used
	}
	protected function pk() {
		return ''; // not used
	}
	protected function base_where() {
		return ''; // not used
	}
	protected function name_column() {
		return ''; // not used
	}
	protected function value_column() {
		return ''; // not used
	}
	protected function is_all_sites_sortable() {
		return true;
	}
	protected function sortable_columns() {
		return [ 
			'id',
			'name',
			'value',
			"timeout",
			'autoload',
			'site_id',
			'found_in',
			'size',
		];
	}
	protected function delete_helper() {
		return ''; // not used
	}

	/** Private methods only used by this handler */
	private function search_sql( $args, $template ) {

		global $wpdb;

		$search_for = $args['search_for'] ?? '';
		$search_in = $args['search_in'] ?? 'all';
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

	private function size_sql( $args, $template ) {

		global $wpdb;

		$size = $args['size'] ?? 0;
		$size_unit = $args['size_unit'] ?? 'B';

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
	 * Get the expired templates for the current site.
	 *
	 * @return array The expired templates.
	 */
	private function get_expired_templates() {

		global $wpdb;

		$length = self::TRUNCATE_LENGTH;

		$site_id = get_current_blog_id();

		$templates = [ 
			[ 
				'sql' => "
					SELECT  a.option_id  AS id,
					        a.option_name AS name,
					        SUBSTRING(a.option_value,1,$length) AS value,
					        b.option_value  AS timeout,
					        {$site_id}      AS site_id,
					        'options'       AS found_in,
							a.autoload      AS autoload,
					        LENGTH(a.option_id) + LENGTH(a.option_name) + LENGTH(a.option_value) + LENGTH(a.autoload) AS size
					FROM    {$wpdb->options} a
					LEFT JOIN {$wpdb->options} b
					       ON b.option_name = CONCAT(
					            '_transient_timeout_',
					            SUBSTRING(a.option_name, CHAR_LENGTH('_transient_') + 1)
					       )
					WHERE   a.option_name LIKE '\_transient\_%'
					        AND a.option_name NOT LIKE '\_transient\_timeout\_%'
					        AND b.option_value < UNIX_TIMESTAMP()
				",
				'name_col' => 'a.option_name',
				'value_col' => 'a.option_value',
			],
			[ 
				'sql' => "
					SELECT  a.option_id  AS id,
					        a.option_name AS name,
					        SUBSTRING(a.option_value,1,$length) AS value,
					        b.option_value  AS timeout,
					        {$site_id}      AS site_id,
					        'options'       AS found_in,
							a.autoload      AS autoload,
							LENGTH(a.option_id) + LENGTH(a.option_name) + LENGTH(a.option_value) + LENGTH(a.autoload) AS size
					FROM    {$wpdb->options} a
					LEFT JOIN {$wpdb->options} b
					       ON b.option_name = CONCAT(
					            '_site_transient_timeout_',
					            SUBSTRING(a.option_name, CHAR_LENGTH('_site_transient_') + 1)
					       )
					WHERE   a.option_name LIKE '\_site\_transient\_%'
					        AND a.option_name NOT LIKE '\_site\_transient\_timeout\_%'
					        AND b.option_value < UNIX_TIMESTAMP()
				",
				'name_col' => 'a.option_name',
				'value_col' => 'a.option_value',
			],
		];

		if ( is_multisite() && is_main_site( $site_id ) ) {
			$templates[] = [ 
				'sql' => "
					SELECT  a.meta_id    AS id,
							a.meta_key   AS name,
							SUBSTRING(a.meta_value,1,$length) AS value,
							b.timeout_value AS timeout,
							{$site_id}      AS site_id,
							'sitemeta'      AS found_in,
							'off'           AS autoload,
							LENGTH(a.meta_id) + LENGTH(a.meta_key) + LENGTH(a.meta_value) AS size
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
							AND b.timeout_value < UNIX_TIMESTAMP()
				",
				'name_col' => 'a.meta_key',
				'value_col' => 'a.meta_value',
			];
		}


		return $templates;

	}

	/** Overridable helper methods */
	protected function build_branches_for_site( $site_id, $args, $maybe_order_by, $sub_limit, $needs_collation_fix = false ) {

		global $wpdb;

		ADBC_Sites::instance()->switch_to_blog_id( $site_id );

		$branches = [];

		$collation = ! empty( $wpdb->collate ) ? $wpdb->collate : 'utf8mb4_unicode_ci';

		foreach ( $this->get_expired_templates() as $template ) {

			$search = $this->search_sql( $args, $template );
			$size = $this->size_sql( $args, $template );

			$inner_sql = "
				{$template['sql']}
				{$search}
				{$size}
			";

			if ( $needs_collation_fix ) {
				$inner_sql = "
					SELECT
						id,
						CONVERT(name USING utf8mb4) COLLATE {$collation} AS name,
						CONVERT(value USING utf8mb4) COLLATE {$collation} AS value,
						CONVERT(timeout USING utf8mb4) COLLATE {$collation} AS timeout,
						site_id,
						CONVERT(found_in USING utf8mb4) COLLATE {$collation} AS found_in,
						CONVERT(autoload USING utf8mb4) COLLATE {$collation} AS autoload,
						size
					FROM ( {$inner_sql} ) AS t
				";
			}

			$sql = "
				{$inner_sql}
				{$maybe_order_by}
				LIMIT %d
			";

			$branches[] = '(' . $wpdb->prepare( $sql, $sub_limit ) . ')';

		}

		ADBC_Sites::instance()->restore_blog();

		return $branches;

	}

	/**
	 * Get the count of expired templates for the current site.
	 *
	 * @return array The count of expired templates.
	 */
	private function get_count_expired_templates() {

		global $wpdb;

		$site_id = get_current_blog_id();

		$templates = [ 
			[ 
				// normal transients in options
				'sql' => "
					SELECT
						COUNT(*) AS count,
						SUM(
							COALESCE(LENGTH(a.option_id), 0) +
							COALESCE(LENGTH(a.option_name), 0) +
							COALESCE(LENGTH(a.option_value), 0) +
							COALESCE(LENGTH(a.autoload), 0)
						) AS total_size
					FROM {$wpdb->options} a
					LEFT JOIN {$wpdb->options} b
						ON b.option_name = CONCAT(
							'_transient_timeout_',
							SUBSTRING(a.option_name, CHAR_LENGTH('_transient_') + 1)
						)
					WHERE a.option_name LIKE '\\_transient\\_%'
						AND a.option_name NOT LIKE '\\_transient\\_timeout\\_%'
						AND b.option_value IS NOT NULL
						AND b.option_value < UNIX_TIMESTAMP()
				",
				'name_col' => 'a.option_name',
				'value_col' => 'a.option_value',
			],
			[ 
				// site transients in options (edge case in multisite)
				'sql' => "
					SELECT
						COUNT(*) AS count,
						SUM(
							COALESCE(LENGTH(a.option_id), 0) +
							COALESCE(LENGTH(a.option_name), 0) +
							COALESCE(LENGTH(a.option_value), 0) +
							COALESCE(LENGTH(a.autoload), 0)
						) AS total_size
					FROM {$wpdb->options} a
					LEFT JOIN {$wpdb->options} b
						ON b.option_name = CONCAT(
							'_site_transient_timeout_',
							SUBSTRING(a.option_name, CHAR_LENGTH('_site_transient_') + 1)
						)
					WHERE a.option_name LIKE '\\_site\\_transient\\_%'
						AND a.option_name NOT LIKE '\\_site\\_transient\\_timeout\\_%'
						AND b.option_value IS NOT NULL
						AND b.option_value < UNIX_TIMESTAMP()
				",
				'name_col' => 'a.option_name',
				'value_col' => 'a.option_value',
			]
		];

		if ( is_multisite() && is_main_site( $site_id ) ) {
			$templates[] = [ 
				'sql' => "
					SELECT
						COUNT(*) AS count,
						SUM(
							COALESCE(LENGTH(a.meta_id), 0) +
							COALESCE(LENGTH(a.meta_key), 0) +
							COALESCE(LENGTH(a.meta_value), 0)
						) AS total_size
					FROM {$wpdb->sitemeta} a
					LEFT JOIN (
						SELECT meta_key, MIN(CAST(meta_value AS UNSIGNED)) AS timeout_value
						FROM {$wpdb->sitemeta}
						WHERE meta_key LIKE '\\_site\\_transient\\_timeout\\_%'
						GROUP BY meta_key
					) b
						ON b.meta_key = CONCAT(
							'_site_transient_timeout_',
							SUBSTRING(a.meta_key, CHAR_LENGTH('_site_transient_') + 1)
						)
					WHERE a.meta_key LIKE '\\_site\\_transient\\_%'
						AND a.meta_key NOT LIKE '\\_site\\_transient\\_timeout\\_%'
						AND b.timeout_value IS NOT NULL
						AND b.timeout_value < UNIX_TIMESTAMP()
				",
				'name_col' => 'a.meta_key',
				'value_col' => 'a.meta_value',
			];
		}

		return $templates;

	}

	/**
	 * Build the count branches for the current site.
	 *
	 * @param int $site_id The site ID to build the branches for.
	 * @param array $args The arguments for the query.
	 *
	 * @return array The count branches.
	 */
	protected function build_count_branches_for_site( $site_id, $args ) {

		ADBC_Sites::instance()->switch_to_blog_id( $site_id );

		$branches = [];

		foreach ( $this->get_count_expired_templates() as $template ) {

			$search = $this->search_sql( $args, $template );
			$size = $this->size_sql( $args, $template );

			// Template SQL already has WHERE; search_sql/size_sql return " AND ..."
			$branches[] = '(' . $template['sql'] . $search . $size . ')';
		}

		ADBC_Sites::instance()->restore_blog();

		return $branches;

	}

	protected function add_composite_id( &$rows ) {

		foreach ( $rows as &$row ) {
			$row['composite_id'] = [ 
				'site_id' => (int) $row['site_id'],
				'items_type' => $this->items_type(),
				'id' => (int) $row['id'],
				'name' => $row['name'],
				'found_in' => $row['found_in'],
			];
		}

		return $rows;

	}

	/** Public methods overridden from the ADBC_Cleanup_Type_Handler interface */
	public function count_filtered( $args = [] ) {

		global $wpdb;

		$site_arg = $args['site_id'] ?? 'all';

		$branches = [];
		foreach ( ADBC_Sites::instance()->get_sites_list( $site_arg ) as $site ) {
			$branches = array_merge(
				$branches,
				$this->build_count_branches_for_site( $site['id'], $args )
			);
		}

		$total = [ 'count' => 0, 'size' => 0 ];

		if ( empty( $branches ) ) {
			return $total;
		}

		$union_sql = implode( "\nUNION ALL\n", $branches );

		$sql = "
			SELECT
				SUM(count)      AS count,
				SUM(total_size) AS total_size
			FROM ( {$union_sql} ) AS t
		";

		$row = $wpdb->get_row( $sql, ARRAY_A );

		if ( $row ) {
			$total['count'] = (int) ( $row['count'] ?? 0 );
			$total['size'] = (int) ( $row['total_size'] ?? 0 );
		}

		return $total;

	}


	public function purge() {

		global $wpdb;

		$total = $this->count_filtered()['count']; // count before deleting

		foreach ( ADBC_Sites::instance()->get_sites_list() as $site ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );

			delete_expired_transients( true );   // true = force DB op

			// Clean up expired site transients stored in options table in multisite (edge case).
			if ( is_multisite() )
				$wpdb->query(
					$wpdb->prepare( "
					DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
					WHERE a.option_name LIKE %s
					AND a.option_name NOT LIKE %s
					AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
					AND b.option_value < %d",
						$wpdb->esc_like( '_site_transient_' ) . '%',
						$wpdb->esc_like( '_site_transient_timeout_' ) . '%',
						time()
					)
				);

			ADBC_Sites::instance()->restore_blog();

		}

		return $total;
	}

	protected function delete_native( $items ) {

		if ( empty( $items ) ) {
			return 0;
		}

		$by_site = [];
		foreach ( $items as $item ) {
			$by_site[ $item['site_id'] ][] = $item;
		}

		$deleted = 0;

		foreach ( $by_site as $site_id => $rows ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			foreach ( $rows as $row ) {

				$full_name = $row['name'];   // full option / meta key
				$found_in = $row['found_in'];     // 'options' | 'sitemeta'

				// site_transient
				if ( strpos( $full_name, '_site_transient_' ) === 0 ) {

					$base_name = substr( $full_name, 16 );      // strip prefix

					// Edge case where in multisite and site_transient is in options table
					if ( is_multisite() && $found_in === 'options' ) {

						if ( delete_option( $full_name ) ) {
							delete_option( "_site_transient_timeout_$base_name" );
							$deleted++;
						}

					} else {

						if ( delete_site_transient( $base_name ) ) {
							$deleted++;
						} else {
							// fallback to direct delete in sitemeta table
							// This can happen if the transient have an invalid site ID in sitemeta
							if ( $found_in === 'sitemeta' ) {

								global $wpdb;

								$timeout_key = "_site_transient_timeout_{$base_name}";

								$sql = "
									DELETE FROM {$wpdb->sitemeta}
									WHERE meta_key IN ( %s, %s )
								";

								$deleted_rows = $wpdb->query(
									$wpdb->prepare( $sql, $full_name, $timeout_key )
								);

								if ( $deleted_rows !== false && $deleted_rows > 0 ) {
									$deleted++;
								}

							}

						}

					}

					continue;

				}

				// transient
				if ( strpos( $full_name, '_transient_' ) === 0 ) {

					$base_name = substr( $full_name, 11 );
					if ( delete_transient( $base_name ) ) {
						$deleted++;
					}

				}

			}

			ADBC_Sites::instance()->restore_blog();

		}

		return $deleted;

	}

	protected function delete_sql( $items ) {

		global $wpdb;

		if ( empty( $items ) ) {
			return 0;
		}

		// Group items by site for proper blog switching.
		$by_site = [];
		foreach ( $items as $item ) {
			$by_site[ $item['site_id'] ][] = $item;
		}

		$deleted = 0;

		foreach ( $by_site as $site_id => $rows ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			// Collect primary IDs and timeout keys per table.
			$option_ids = [];
			$option_timeout_keys = [];
			$meta_ids = [];
			$meta_timeout_keys = [];

			foreach ( $rows as $row ) {

				$full_name = $row['name'];
				$found_in = $row['found_in'];

				// site transients: _site_transient_*
				if ( strpos( $full_name, '_site_transient_' ) === 0 ) {

					$base_name = substr( $full_name, 16 ); // strip prefix _site_transient_

					if ( $found_in === 'sitemeta' ) {
						$meta_ids[] = (int) $row['id'];
						$meta_timeout_keys[] = "_site_transient_timeout_{$base_name}";
					} else {
						$option_ids[] = (int) $row['id'];
						$option_timeout_keys[] = "_site_transient_timeout_{$base_name}";
					}

					continue;
				}

				// normal transients: _transient_*
				if ( strpos( $full_name, '_transient_' ) === 0 ) {

					$base_name = substr( $full_name, 11 ); // strip prefix _transient_
					$option_ids[] = (int) $row['id'];
					$option_timeout_keys[] = "_transient_timeout_{$base_name}";

				}

			}

			// Deduplicate timeout keys to avoid unnecessary placeholder bloat.
			$option_timeout_keys = array_values( array_unique( $option_timeout_keys ) );
			$meta_timeout_keys = array_values( array_unique( $meta_timeout_keys ) );

			// ---- Delete from options in a single query -------------------
			if ( $option_ids || $option_timeout_keys ) {

				$where_parts = [];
				$params = [];

				if ( $option_ids ) {
					$placeholders = implode( ',', array_fill( 0, count( $option_ids ), '%d' ) );
					$where_parts[] = "option_id IN ( {$placeholders} )";
					$params = array_merge( $params, $option_ids );
				}

				if ( $option_timeout_keys ) {
					$placeholders = implode( ',', array_fill( 0, count( $option_timeout_keys ), '%s' ) );
					$where_parts[] = "option_name IN ( {$placeholders} )";
					$params = array_merge( $params, $option_timeout_keys );
				}

				if ( $where_parts ) {
					$sql = "DELETE FROM {$wpdb->options} WHERE " . implode( ' OR ', $where_parts );
					$wpdb->query( $wpdb->prepare( $sql, $params ) );
				}

				// Count only primary transient rows, not the timeout rows.
				$deleted += count( $option_ids );

			}

			// ---- Delete from sitemeta in a single query ------------------
			if ( $meta_ids || $meta_timeout_keys ) {

				$where_parts = [];
				$params = [];

				if ( $meta_ids ) {
					$placeholders = implode( ',', array_fill( 0, count( $meta_ids ), '%d' ) );
					$where_parts[] = "meta_id IN ( {$placeholders} )";
					$params = array_merge( $params, $meta_ids );
				}

				if ( $meta_timeout_keys ) {
					$placeholders = implode( ',', array_fill( 0, count( $meta_timeout_keys ), '%s' ) );
					$where_parts[] = "meta_key IN ( {$placeholders} )";
					$params = array_merge( $params, $meta_timeout_keys );
				}

				if ( $where_parts ) {
					$sql = "DELETE FROM {$wpdb->sitemeta} WHERE " . implode( ' OR ', $where_parts );
					$wpdb->query( $wpdb->prepare( $sql, $params ) );
				}

				// Count only primary transient rows, not the timeout rows.
				$deleted += count( $meta_ids );

			}

			ADBC_Sites::instance()->restore_blog();

		}

		return $deleted;

	}

	/**
	 * Lists the items of this type across all sites.
	 * This method can be filtered by all filters available in the args array.
	 * 
	 * @param array $args The arguments to filter the list.
	 * 
	 * @return array An array of items with their details.
	 */
	public function list( $args ) {

		global $wpdb;

		$per_page = $args['items_per_page'];
		$page = $args['current_page'];
		$offset = ( $page - 1 ) * $per_page;
		$site_arg = $args['site_id'];
		$sort_col = $args['sort_by'];
		$sort_dir = $args['sort_order'];

		$apply_sort = in_array( $sort_col, $this->sortable_columns() ) && (
			( $args['site_id'] === 'all' && $this->is_all_sites_sortable() ) || ( $args['site_id'] !== 'all' )
		);
		$maybe_order_by = $apply_sort ? "ORDER BY {$sort_col} {$sort_dir}" : '';

		$branch_batch_size = $offset + $per_page;

		$needs_collation_fix = ! ADBC_Database::is_collaction_unified( 'options', true, $site_arg );

		// ---- Build branches ---------------------------------------------
		$branches = [];
		foreach ( ADBC_Sites::instance()->get_sites_list( $site_arg ) as $site ) {

			$branches = array_merge(
				$branches,
				$this->build_branches_for_site(
					$site['id'],
					$args, // pass whole arg set for WHERE glue
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

		$sql = $wpdb->prepare( $sql, $per_page, $offset );

		$rows = $wpdb->get_results( $sql, ARRAY_A );

		return $this->add_composite_id( $rows );

	}

}

// Register the handler with the cleanup type registry.
ADBC_Cleanup_Type_Registry::register( 'expired_transients', new ADBC_Cleanup_Expired_Transients_Handler );