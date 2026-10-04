<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC common model class.
 * 
 * This class provides common functions used across the plugin.
 */
class ADBC_Common_Model {

	/**
	 * Filter item by the given belongs_to filter.
	 * 
	 * @param array $filters The filters.
	 * @param object $belongs_to The belongs_to data.
	 * @return bool True if the table satisfies the belongs_to filter, false otherwise.
	 */
	public static function is_item_satisfies_belongs_to( $filters, $belongs_to ) {

		// Check the belongs_to filter
		if ( ! empty( $filters['belongs_to'] ) && $filters['belongs_to'] !== 'all' ) {

			$type_map = [ 
				'not_scanned' => 'u',
				'plugins' => 'p',
				'themes' => 't',
				'wordpress' => 'w',
				'orphans' => 'o',
				'unknown' => 'unk'
			];

			// Return false if the type does not match the expected type
			if ( ! isset( $type_map[ $filters['belongs_to'] ] ) || $type_map[ $filters['belongs_to'] ] !== $belongs_to['type'] )
				return false;

		}

		// Check 'belongs_to_plugin_slug' filter.
		if ( ! empty( $filters['belongs_to_plugin_slug'] ) ) {
			// For 'u', the slug is not set, so we need to check if it's not empty before comparing
			if ( ! isset( $belongs_to['slug'] ) || $belongs_to['slug'] !== $filters['belongs_to_plugin_slug'] )
				return false;
		}

		// Check 'belongs_to_theme_slug' filter.
		if ( ! empty( $filters['belongs_to_theme_slug'] ) ) {
			// For 'u', the slug is not set, so we need to check if it's not empty before comparing
			if ( ! isset( $belongs_to['slug'] ) || $belongs_to['slug'] !== $filters['belongs_to_theme_slug'] )
				return false;
		}

		// Check 'show_manual_corrections_only' filter.
		if ( ! empty( $filters['show_manual_corrections_only'] ) && $filters['show_manual_corrections_only'] === true ) {
			// For 'u', the slug is not set, so we need to check if it's not empty before comparing
			if ( ! isset( $belongs_to['slug'] ) || $belongs_to['by'] !== 'm' )
				return false;
		}

		return true;
	}

	/**
	 * Get rows from a specific column in a table for a specific site based on IDs.
	 *
	 * @param int $site_id The site ID.
	 * @param string $table_name The name of the table.
	 * @param string $column_name The name of the column to get items from.
	 * @param string $id_column_name The name of the ID column.
	 * @param array  $ids An array of IDs to filter the results.
	 * @return array An array of items from the specified column, or an empty array if no items are found.
	 */
	public static function get_column_rows_from_table( $site_id, $table_name, $column_name, $id_column_name, $ids = [] ) {

		global $wpdb;

		$prefix = ADBC_Sites::instance()->get_prefix_from_site_id( $site_id );

		if ( $prefix === null || empty( $ids ) )
			return [];

		$tbl = '`' . esc_sql( $prefix . $table_name ) . '`';
		$col = '`' . esc_sql( $column_name ) . '`';
		$idCol = '`' . esc_sql( $id_column_name ) . '`';

		// Prepare the SQL query to get the items names from the database.
		$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql = $wpdb->prepare(
			"SELECT $col FROM $tbl WHERE $idCol IN ($ids_placeholder)",
			...$ids
		);

		// Execute the query and get the results.
		$results = $wpdb->get_col( $sql );

		return $results;
	}

	/**
	 * Load scan results to items for free version.
	 * 
	 * @param array $items The items to load scan results into.
	 * @return void
	 */
	public static function load_scan_results_to_items_for_free_version( &$items ) {

		// First of all, add some properties to each item to prevent any undefined index error.
		foreach ( $items as $item ) {
			$item->belongs_to = [ 
				'type' => 'u',
				'slug' => 'u',
				'name' => '',
				'by' => '',
				'percent' => '',
				'status' => '',
			];
			$item->known_plugins = []; // known plugins that are related to the item
			$item->known_themes = []; // known themes that are related to the item
		}

	}

	/**
	 * Count the number of items that were not scanned in the given list.
	 * 
	 * @param string $items_type The type of items to count. "tables", "options", "cron_jobs", etc.
	 * @param array  $items_list The list of items to count. ["name1" => true, "name2" => true, ...]
	 * 
	 * @return int The number of items that were not scanned.
	 */
	public static function count_not_scanned_items_in_list_for_free( $items_type, &$items_list ) {

		if ( empty( $items_list ) )
			return 0; // Nothing to count.

		// ADBC hardcoded items (exact matches only).
		$adbc_items = ADBC_Hardcoded_Items::instance()->get_adbc_items( $items_type );
		$wp_hardcoded_items = ADBC_Hardcoded_Items::instance()->get_wordpress_items( $items_type );

		foreach ( $items_list as $index => $item ) {

			// Check WordPress core hardcoded items (exact + rule-based for transients).
			if ( ADBC_Hardcoded_Items::instance()->is_item_belongs_to_wp_core( $item, $items_type, $wp_hardcoded_items ) ) {
				unset( $items_list[ $index ] );
				continue;
			}

			// Check ADBC hardcoded items (exact matches).
			if ( isset( $adbc_items[ $item ] ) ) {
				unset( $items_list[ $index ] );
				continue;
			}

		}

		return count( $items_list );

	}

}