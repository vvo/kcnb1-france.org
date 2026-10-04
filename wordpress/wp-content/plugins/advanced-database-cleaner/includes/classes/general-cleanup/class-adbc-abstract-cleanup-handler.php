<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC_Abstract_Cleanup_Handler
 * 
 * This abstract class provides a base implementation for cleanup type handlers.
 */
abstract class ADBC_Abstract_Cleanup_Handler implements ADBC_Cleanup_Type_Handler {

	/** Common constants --------------------------------------------------- */
	protected const TRUNCATE_LENGTH = 100; // max chars kept in “value”
	protected const PURGE_CHUNK = 1000; // number of items deleted in one purge run

	/** Required all subclasses must supply -------------------------------- */

	/**
	 * These methods must be implemented by subclasses to provide the necessary
	 * type of items this handler is responsible for.
	 * 
	 * @return string
	 */
	abstract protected function items_type();

	/**
	 * These methods must be implemented by subclasses to provide the necessary
	 * information about the table name used by this handler.
	 * 
	 * @return string
	 */
	abstract protected function table();

	/**
	 * These methods must be implemented by subclasses to provide the necessary
	 * information about the table suffix used by this handler.
	 * 
	 * @return string
	 */
	abstract protected function table_suffix();

	/**
	 * These methods must be implemented by subclasses to provide the necessary
	 * information about the primary key of the table used by this handler.
	 * 
	 * @return string
	 */
	abstract protected function pk();

	/**
	 * These methods must be implemented by subclasses to provide the necessary
	 * information about the base WHERE clause used to fetch this type of data.
	 * 
	 * @return string
	 */
	abstract protected function base_where();

	/**
	 * These methods must be implemented by subclasses to provide the necessary
	 * information about the columns used as name in the search.
	 * 
	 * @return string
	 */
	abstract protected function name_column();

	/**
	 * These methods must be implemented by subclasses to provide the necessary
	 * information about the column used as value in the search.
	 * 
	 * @return string
	 */
	abstract protected function value_column();

	/**
	 * These methods must be implemented by subclasses to provide the necessary
	 * information about wether the handler can sort in multisite when the filter site_id = 'all
	 * 
	 * @return bool
	 */
	abstract protected function is_all_sites_sortable();

	/**
	 * These methods must be implemented by subclasses to provide the necessary
	 * information about the columns that can be used for sorting.
	 * 
	 * @return array
	 */
	abstract protected function sortable_columns();

	/** * This method must be implemented by subclasses to provide the necessary
	 * function to delete a single item. It must return false if the item is not available.
	 * 
	 * @return callable
	 */
	abstract protected function delete_helper();

	/** Optional used by subclasses when needed ----------------------------- */

	/**
	 * These methods can be overridden by subclasses to provide additional
	 * extra columns in the SELECT clause
	 * 
	 * @return array
	 */
	protected function extra_select() {
		return [];
	}

	/**
	 * These methods can be overridden by subclasses to provide additional
	 * extra JOINs to the main table ( main is the alias for the main table in the SQL query )
	 * 
	 * @return string
	 */
	protected function extra_joins() {
		return '';
	}

	/**
	 * These methods can be overridden by subclasses to provide the necessary
	 * information about the date column used for the "keep last", date range filter and sorting.
	 * 
	 * @return string|null
	 */
	protected function date_column() {
		return null;
	}

	/**
	 * Whether detailed list rows should include and support filtering by size.
	 * Summary counts can still calculate their aggregate size independently.
	 *
	 * @return bool
	 */
	protected function include_size_in_list() {
		return true;
	}

	/**
	 * These methods can be overridden by subclasses to provide the necessary
	 * information about the "keep last" mode used by this handler.
	 * by default from_total override by per-parent rule if needed (must set the parent column)
	 * 
	 * @return string
	 */
	protected function keep_last_mode() {
		return 'from_total';
	}

	/**
	 * These methods can be overridden by subclasses to provide the necessary
	 * information about the "keep last" parent column used by this handler.
	 * This is used for the per-parent keep last mode.
	 * If not set, then the default is 'from_total' (no parent column).
	 * 
	 * @return string|null
	 */
	protected function parent_column() {
		return null;
	}

	/**
	 * These methods can be overridden by subclasses to provide the necessary
	 * information about the extra arguments passed to the delete helper function.
	 * This is used to pass extra arguments to the delete helper function, e.g. for deleting by name instead of ID.
	 * 
	 * @return array
	 */
	protected function delete_helper_tail_args() {
		return [];
	}

	/**
	 * This method can be overridden by subclasses to provide the necessary
	 * documentation URL for this handler.
	 * By default, it returns a generic URL that can be overridden by each handler.
	 * 
	 * @return string
	 */
	protected function documentation_url() {
		return 'https://sigmaplugin.com/category/blog/'; // override by the documentation URL for each type
	}

	/** Private helper methods ----------------------------------------------- */
	private $keep_last_config = null; // used to override the keep last settings for this handler, can be NULL (use default keep_last settings), FALSE (no keep last) or an array (custom rule)
	private static $columns_cache = []; // cache for table columns to avoid querying the database multiple times

	/**
	 * Returns the columns of the table used by this handler.
	 * Uses a cache to avoid querying the database multiple times.
	 *
	 * @return array
	 */
	private function table_columns() {

		global $wpdb;

		if ( isset( self::$columns_cache[ $this->table()] ) ) {
			return self::$columns_cache[ $this->table()];
		}

		self::$columns_cache[ $this->table()] = $wpdb->get_col( "DESC {$this->table()}" );

		return self::$columns_cache[ $this->table()];

	}

	/**
	 * Returns the prepared SQL condition to filter items based on the "keep last" days rule.
	 *
	 * @return string
	 */
	protected function keep_days_filter() {

		global $wpdb;

		$keep_last = $this->current_keep_last();
		if ( ! $keep_last || $keep_last['type'] !== 'days' || ! $this->date_column() ) {
			return '';
		}

		$nb_days = $keep_last['value'];

		$sql = " AND {$this->date_column()} <= DATE_SUB(NOW(), INTERVAL %d DAY)";

		return $wpdb->prepare( $sql, $nb_days );

	}

	/**
	 * Returns the prepared SQL condition to filter items based on the "keep last" items rule.
	 * It can be either per-parent or from_total rule.
	 *
	 * @return string
	 */
	protected function keep_items_filter() {

		global $wpdb;

		$keep_last = $this->current_keep_last();
		if ( ! $keep_last || $keep_last['type'] !== 'items' || ! $this->date_column() ) {
			return '';
		}

		$limit = $keep_last['value'];

		/* ---------- per‑parent rule ---------- */
		if ( $this->keep_last_mode() === 'per_parent' ) {

			$parent = $this->parent_column();
			if ( ! $parent ) {
				return '';
			}

			$sql = "
				AND (
					SELECT COUNT(*)
					FROM   {$this->table()} t2
					WHERE  t2.{$parent} = main.{$parent}
						AND {$this->base_where()}
						AND t2.{$this->date_column()} >= main.{$this->date_column()}
				) > %d
			";

			return $wpdb->prepare( $sql, $limit );

		}

		/* ---------- from_total rule ---------- */
		$sql = "
			AND {$this->pk()} NOT IN (
				SELECT latest.{$this->pk()}
				FROM (
					SELECT {$this->pk()}
					FROM   {$this->table()}
					WHERE  {$this->base_where()}
					ORDER  BY {$this->date_column()} DESC
					LIMIT  %d
				) AS latest
			)
		";

		return $wpdb->prepare( $sql, $limit );

	}

	/**
	 * Returns the SQL expression to truncate the value column to a maximum length.
	 * This is used to avoid displaying long values in the UI.
	 *
	 * @return string
	 */
	private function truncated_value() {
		$length = self::TRUNCATE_LENGTH;
		return "SUBSTRING(main.{$this->value_column()},1,{$length})";
	}

	/**
	 * Returns the current "keep last" settings for this handler run.
	 * This can be overridden by the set_keep_last_override() method by any caller.
	 *
	 * @return array|false The current keep last settings or false if not set.
	 */
	private function current_keep_last() {

		// If an override is set, return it
		if ( $this->keep_last_config !== null ) {
			return $this->keep_last_config;     // false or array
		}

		// If no override is set, return the global keep last settings
		return ADBC_General_Cleanup::get_keep_last( $this->items_type() );

	}

	/**
	 * Builds the prepared SQL branches for a specific site.
	 * This is used to fetch data from multiple sites in a multisite environment.
	 *
	 * @param int $site_id The site ID to build the branch for.
	 * @param array $args The arguments for the query.
	 * @param string $maybe_order_by The ORDER BY clause if sorting is applied.
	 * @param int $sub_limit The limit to set for this branch.
	 * 
	 * @return array An array containing the SQL branch for the specified site.
	 */
	protected function build_branches_for_site( $site_id, $args, $maybe_order_by, $sub_limit, $needs_collation_fix = false ) {

		global $wpdb;

		// Build all select columns
		$columns[] = "main.{$this->pk()}";

		if ( $needs_collation_fix ) {
			$collation = ! empty( $wpdb->collate ) ? $wpdb->collate : 'utf8mb4_unicode_ci';
			$columns[] = "CONVERT(main.{$this->name_column()} USING utf8mb4) COLLATE {$collation} AS {$this->name_column()}";
			$columns[] = "CONVERT({$this->truncated_value()} USING utf8mb4) COLLATE {$collation} AS {$this->value_column()}";
		} else {
			$columns[] = "main.{$this->name_column()}";
			$columns[] = "{$this->truncated_value()} AS {$this->value_column()}";
		}

		if ( $this->include_size_in_list() ) {
			$columns[] = "{$this->size_expression()} AS size";
		}
		$columns[] = "{$site_id} AS site_id";
		$columns = array_merge( $columns, $this->extra_select() ); // extra select columns

		$select_columns = implode( ', ', $columns );

		$sql = "
			SELECT {$select_columns}
			FROM   {$this->table()} main
				   {$this->extra_joins()}
			WHERE  {$this->base_where()}
				   {$this->keep_days_filter()}
				   {$this->keep_items_filter()}
				   {$this->search_filter( $args )}
				   {$this->date_filter( $args )}
				   {$this->size_filter( $args )}
			{$maybe_order_by}
			LIMIT  %d
		";

		$branch = '(' . $wpdb->prepare( $sql, $sub_limit ) . ')';

		return [ $branch ];

	}

	/**
	 * Adds a composite ID to each row in the result set.
	 * This is used to create a unique identifier for each item across sites.
	 *
	 * @param array $rows The rows to add the composite ID to.
	 * 
	 * @return array The rows with the composite ID added.
	 */
	protected function add_composite_id( &$rows ) {

		foreach ( $rows as &$row ) {
			$row['composite_id'] = [ 
				'site_id' => (int) $row['site_id'],
				'items_type' => $this->items_type(),
				'id' => (int) $row[ $this->pk()],
			];
		}

		return $rows;

	}

	/**
	 * Returns the prepared SQL condition to filter items based on a search term.
	 * This is used to search for items by name or value.
	 * 
	 * @param array $args The arguments for the search, including 'search_for' and 'search_in'.
	 * 
	 * @return string The prepared SQL condition for the search filter, or an empty string if no search term is provided.
	 */
	protected function search_filter( $args ) {

		global $wpdb;

		$search_for = $args['search_for'] ?? '';
		if ( $search_for === '' ) {
			return '';
		}

		$like = '%' . $wpdb->esc_like( $search_for ) . '%';

		switch ( $args['search_in'] ) {
			case 'name':
				$search_sql = $wpdb->prepare( " AND main.{$this->name_column()} LIKE %s", $like );
				break;
			case 'value':
				$search_sql = $wpdb->prepare( " AND main.{$this->value_column()} LIKE %s", $like );
				break;
			default:
				$search_sql = $wpdb->prepare( " AND ( main.{$this->name_column()} LIKE %s OR main.{$this->value_column()} LIKE %s )", $like, $like );
		}

		return $search_sql;

	}

	/**
	 * Returns the prepared SQL condition to filter items based on a date range.
	 * This is used to filter items by their date column.
	 * 
	 * @param array $args The arguments for the date filter, including 'start_date' and 'end_date'.
	 * 
	 * @return string The prepared SQL condition for the date filter, or an empty string if no date column is defined.
	 */
	protected function date_filter( $args ) {

		global $wpdb;

		if ( ! $this->date_column() )
			return '';

		$sql = '';

		if ( ! empty( $args['start_date'] ) )
			$sql .= $wpdb->prepare( " AND {$this->date_column()} >= %s", $args['start_date'] . ' 00:00:00' );

		if ( ! empty( $args['end_date'] ) )
			$sql .= $wpdb->prepare( " AND {$this->date_column()} <= %s", $args['end_date'] . ' 23:59:59' );

		return $sql;

	}

	/**
	 * Returns the prepared SQL condition to filter items based on their size.
	 * This is used to filter items by their total size across all columns.
	 * 
	 * @param array $args The arguments for the size filter, including 'size' and 'size_unit'.
	 * 
	 * @return string The prepared SQL condition for the size filter, or an empty string if no size is specified.
	 */
	protected function size_filter( $args ) {

		global $wpdb;

		if ( ! $this->include_size_in_list() ) {
			return '';
		}

		// We check if there are args since it can be called by the count method without args
		$size = $args['size'] ?? 0;
		$size_unit = $args['size_unit'] ?? 'B';

		if ( $size === 0 ) {
			return '';
		}

		$bytes = ADBC_Common_Utils::convert_size_to_bytes( $size, $size_unit );

		return $wpdb->prepare( " AND ( {$this->size_expression()} ) >= %d", $bytes );

	}

	/**
	 * Returns the SQL expression to calculate the total size of the item.
	 * This is used to sum the lengths of all columns in the table.
	 *
	 * @return string The SQL expression for the total size.
	 */
	protected function size_expression() {

		$column_expressions = [];
		foreach ( $this->table_columns() as $column ) {
			$column_expressions[] = "COALESCE( LENGTH(main.`$column`), 0 )";
		}

		$full_column_expression = implode( ' + ', $column_expressions );

		return "($full_column_expression)";

	}

	/** Public methods called by the ADBC_General_Cleanup main class ------------------ */

	/**
	 * Counts the total number of items and their total size across all sites.
	 * This method is used to get the count without any filters.
	 * 
	 * @return array{"count"=>int, "size"=>int}
	 */
	public function count() {
		return $this->count_filtered();
	}

	/**
	 * Counts the total number of items and their total size across all sites.
	 * This method can be filtered by all filters available in the list method.
	 * 
	 * @param array|null $args Optional arguments to filter the count, otherwise counts all items.
	 * 
	 * @return array{"count"=>int, "size"=>int}
	 */
	public function count_filtered( $args = [] ) {

		global $wpdb;

		$site_id = $args['site_id'] ?? 'all';

		$total = [ 
			'count' => 0,
			'size' => 0,
		];

		foreach ( ADBC_Sites::instance()->get_sites_list( $site_id ) as $site ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );

			// Make sure the tables exists in this blog
			if ( ! ADBC_Tables::is_table_exists( $this->table() ) ) {
				ADBC_Sites::instance()->restore_blog();
				continue; // nothing to do on this blog
			}

			$sql = "
				SELECT COUNT({$this->pk()}) AS count,
					   SUM({$this->size_expression()}) AS size
				FROM   {$this->table()} main
				   	   {$this->extra_joins()}
				WHERE  {$this->base_where()}
				       {$this->keep_days_filter()}
				       {$this->keep_items_filter()}
				       {$this->search_filter( $args )}
					   {$this->date_filter( $args )}
					   {$this->size_filter( $args )}
			";

			$row = $wpdb->get_row( $sql, ARRAY_A );

			if ( ! empty( $row ) ) {
				$total['count'] += (int) $row['count'];
				$total['size'] += (int) $row['size'];
			}

			ADBC_Sites::instance()->restore_blog();

		}

		return $total;

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

		$apply_sort = in_array( $sort_col, $this->sortable_columns(), true ) && (
			( $site_arg === 'all' && $this->is_all_sites_sortable() ) || ( $site_arg !== 'all' )
		);
		$maybe_order_by = $apply_sort ? "ORDER BY {$sort_col} {$sort_dir}" : '';

		// Resolve sites once.
		$sites = ADBC_Sites::instance()->get_sites_list( $site_arg );

		// ── Single-site fast path ────────────────────────────────────
		if ( count( $sites ) === 1 ) {

			$site = reset( $sites );

			$rows = $this->list_single_site_rows(
				$site['id'],
				$args,
				$per_page,
				$offset,
				$maybe_order_by
			);

			return $this->add_composite_id( $rows );
		}

		// ── Multi-site path (UNION over branches) ────────────────────────────

		$branch_batch_size = $offset + $per_page;
		$branches = [];

		$needs_collation_fix = ! ADBC_Database::is_collaction_unified( $this->table_suffix(), false, $site_arg );

		foreach ( $sites as $site ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );

			// Make sure the tables exists in this blog
			if ( ! ADBC_Tables::is_table_exists( $this->table() ) ) {
				ADBC_Sites::instance()->restore_blog();
				continue; // nothing to do on this blog
			}

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

			ADBC_Sites::instance()->restore_blog();
		}

		if ( empty( $branches ) ) {
			return [];
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

	/**
	 * Lists items for a single site (no UNION/derived table).
	 *
	 * @param int    $site_id       Site ID.
	 * @param array  $args          Filter / paging args.
	 * @param int    $per_page      Items per page.
	 * @param int    $offset        Offset for LIMIT/OFFSET.
	 * @param string $maybe_order_by Already validated ORDER BY clause or ''.
	 *
	 * @return array Raw rows (ARRAY_A) without composite_id; caller can decorate.
	 */
	protected function list_single_site_rows( $site_id, $args, $per_page, $offset, $maybe_order_by ) {

		global $wpdb;

		ADBC_Sites::instance()->switch_to_blog_id( $site_id );

		// Make sure the tables exists in this blog
		if ( ! ADBC_Tables::is_table_exists( $this->table() ) ) {
			ADBC_Sites::instance()->restore_blog();
			return [];
		}

		// Build select columns
		$columns = [];
		$columns[] = "main.{$this->pk()}";
		$columns[] = "main.{$this->name_column()}";
		$columns[] = "{$this->truncated_value()} AS {$this->value_column()}";
		if ( $this->include_size_in_list() ) {
			$columns[] = "{$this->size_expression()} AS size";
		}
		$columns[] = "{$site_id} AS site_id";
		$columns = array_merge( $columns, $this->extra_select() );

		$select_columns = implode( ', ', $columns );

		$sql = "
			SELECT {$select_columns}
			FROM   {$this->table()} main
				   {$this->extra_joins()}
			WHERE  {$this->base_where()}
				   {$this->keep_days_filter()}
				   {$this->keep_items_filter()}
				   {$this->search_filter( $args )}
				   {$this->date_filter( $args )}
				   {$this->size_filter( $args )}
			{$maybe_order_by}
			LIMIT %d OFFSET %d
		";

		$sql = $wpdb->prepare( $sql, $per_page, $offset );
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		ADBC_Sites::instance()->restore_blog();

		return $rows;
	}

	/**
	 * Purges all items of this type across all sites.
	 * This method deletes all items that match the base WHERE clause and the keep last rules.
	 * This method purges the items using either native or SQL method depending on the settings.
	 * 
	 * @return int The number of deleted items.
	 */
	public function purge() {

		$cleanup_method = ADBC_Settings::instance()->get_setting( 'sql_or_native_cleanup_method' );

		if ( $cleanup_method === 'native' )
			return $this->purge_native();
		else
			return $this->purge_sql();

	}

	/**
	 * Deletes the specified items across all sites.
	 * The items should be an array of arrays with 'site_id' and 'id' keys.
	 * This method deletes the items using either native or SQL method depending on the settings.
	 * 
	 * @param array $items The items to delete, each item should have 'site_id' and 'id'.
	 * 
	 * @return int The number of affected rows.
	 */
	public function delete( $items ) {

		$cleanup_method = ADBC_Settings::instance()->get_setting( 'sql_or_native_cleanup_method' );

		if ( $cleanup_method === 'native' )
			return $this->delete_native( $items );
		else
			return $this->delete_sql( $items );

	}

	/**
	 * Deletes the specified items using the wordpress native delete helper function.
	 *
	 * @param array $items The items to delete, each item should have 'site_id' and 'id'.
	 * 
	 * @return int The number of affected rows.
	 */
	protected function delete_native( $items ) {

		if ( empty( $items ) )
			return 0;

		$by_site = [];

		foreach ( $items as $item ) {
			$by_site[ $item['site_id'] ][] = $item['id'];
		}

		$helper = $this->delete_helper();
		$tail = $this->delete_helper_tail_args();

		$affected = 0;

		foreach ( $by_site as $site_id => $ids ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			foreach ( $ids as $id ) {

				$result = $helper( (int) $id, ...$tail );

				if ( $result !== false && $result !== null ) {
					$affected++;
				}

			}

			ADBC_Sites::instance()->restore_blog();

		}

		return $affected;

	}

	/**
	 * Deletes the specified items using direct SQL queries.
	 *
	 * @param array $items The items to delete, each item should have 'site_id' and 'id'.
	 * 
	 * @return int The number of affected rows.
	 */
	protected function delete_sql( $items ) {

		global $wpdb;

		if ( empty( $items ) )
			return 0;

		$by_site = [];

		foreach ( $items as $item ) {
			$by_site[ $item['site_id'] ][] = $item['id'];
		}

		$affected = 0;

		foreach ( $by_site as $site_id => $ids ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site_id );

			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

			$sql = "DELETE FROM {$this->table()} main
					WHERE {$this->pk()} IN ( $placeholders )";

			$sql = $wpdb->prepare( $sql, ...$ids );

			$affected += $wpdb->query( $sql );

			ADBC_Sites::instance()->restore_blog();

		}

		return $affected;

	}

	/**
	 * Purges all items of this type across all sites using wordpress native delete helper.
	 * This method deletes all items that match the base WHERE clause and the keep last rules.
	 * 
	 * @return int The number of deleted items.
	 */
	protected function purge_native() {

		global $wpdb;

		$keep_days_sql = $this->keep_days_filter();
		$keep_items_sql = $this->keep_items_filter();

		$helper = $this->delete_helper();
		$tail = $this->delete_helper_tail_args();

		$chunk = self::PURGE_CHUNK; // number of items to delete in one run
		$deleted = 0;

		foreach ( ADBC_Sites::instance()->get_sites_list() as $site ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );

			// Make sure the tables exists in this blog
			if ( ! ADBC_Tables::is_table_exists( $this->table() ) ) {
				ADBC_Sites::instance()->restore_blog();
				continue; // nothing to do on this blog
			}

			while ( true ) {

				$ids = $wpdb->get_col( "
					SELECT main.{$this->pk()}
					FROM   {$this->table()} main {$this->extra_joins()}
					WHERE  {$this->base_where()}
						   {$keep_days_sql}
						   {$keep_items_sql}
					LIMIT  {$chunk}
				" );

				if ( empty( $ids ) ) {
					break;
				}

				foreach ( $ids as $id ) {
					$helper( (int) $id, ...$tail );
					$deleted++;
				}

			}

			ADBC_Sites::instance()->restore_blog();

		}

		return $deleted;

	}

	/**
	 * Purges all items of this type across all sites using direct SQL queries.
	 * This method deletes all items that match the base WHERE clause and the keep last rules.
	 * 
	 * @return int The number of deleted items.
	 */
	protected function purge_sql() {

		global $wpdb;

		$keep_days_sql = $this->keep_days_filter();
		$keep_items_sql = $this->keep_items_filter();

		$deleted = 0;

		foreach ( ADBC_Sites::instance()->get_sites_list() as $site ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );

			// Make sure the tables exists in this blog
			if ( ! ADBC_Tables::is_table_exists( $this->table() ) ) {
				ADBC_Sites::instance()->restore_blog();
				continue; // nothing to do on this blog
			}

			// Wrap in a derived table to dodge MySQL error 1093
			$sql = "
				DELETE FROM {$this->table()}
				WHERE {$this->pk()} IN (
					SELECT del_id FROM (
						SELECT main.{$this->pk()} AS del_id
						FROM   {$this->table()}  AS main
						   	   {$this->extra_joins()}
						WHERE  {$this->base_where()}
							   {$keep_days_sql}
							   {$keep_items_sql}
					) AS tmp
				)
			";

			$deleted += $wpdb->query( $sql );

			ADBC_Sites::instance()->restore_blog();

		}

		return $deleted;

	}

	/**
	 * Sets the "keep last" config for this handler.
	 * This can be used to set a custom rule for the "keep last" feature.
	 * 
	 * @param array|false|null $value The value to set, can be NULL (use default keep_last setting), FALSE (no keep‐last for this run), or an array (custom rule).
	 */
	public function set_keep_last_config( $value ) {
		// NULL  = use default keep_last settings (normal behavior)
		// FALSE = no keep‐last for this run
		// array = custom rule (same structure as default settings)
		$this->keep_last_config = $value;
	}

	/**
	 * Checks if the given column is valid for sorting.
	 *
	 * @return bool True if the column is valid for sorting, false otherwise.
	 */
	public function is_valid_sortable_column( $column ) {
		return in_array( $column, $this->sortable_columns() );
	}

	/**
	 * Checks if the handler can have a "keep last" feature.
	 * This is true if the handler has a date column defined.
	 *
	 * @return bool True if the handler can have a "keep last" feature, false otherwise.
	 */
	public function can_have_keep_last() {
		return $this->date_column() !== null;
	}

}

