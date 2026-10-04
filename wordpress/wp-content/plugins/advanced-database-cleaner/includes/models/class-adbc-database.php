<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC common database class.
 * 
 * This class provides common database functions.
 */
class ADBC_Database {

	/**
	 * Get database size using SQL query.
	 *
	 * @param bool $formatted Whether to format the size or not.
	 * @return string|int Formatted database size or raw database size.
	 */
	public static function get_database_size_sql( $formatted = true ) {

		global $wpdb;

		$sql_query = "SELECT SUM(data_length + index_length) 
						FROM information_schema.tables 
						WHERE table_schema = DATABASE()
					";

		// Get database size.
		$database_size = $wpdb->get_var( $sql_query );

		if ( $formatted === true )
			$database_size = ADBC_Common_Utils::format_bytes( $database_size );

		return $database_size;

	}

	/**
	 * Get number of tables.
	 *
	 * @return int Number of tables.
	 */
	public static function get_number_of_tables() {

		global $wpdb;

		// Get number of tables.
		$tables = $wpdb->get_results( "SHOW TABLES", ARRAY_N );

		return count( $tables );
	}

	/**
	 * Check whether collations (and charsets) are unified for a given multisite table suffix across all sites.
	 *
	 * Example: $table_suffix = 'options' -> checks wp_options, wp_2_options, ...
	 * If $check_sitemeta is true, it also includes the network sitemeta table.
	 *
	 * @param string   $table_suffix    Table suffix without prefix, e.g. 'options', 'posts', ...
	 * @param bool     $check_sitemeta  Whether to also include the base sitemeta table in the check.
	 * @param int|null $site_arg        Optional filter passed to get_sites_list(); if null, caller can omit.
	 *
	 * @return bool True if unified; false if mixed or if we can't reliably determine (safe fallback).
	 */
	public static function is_collaction_unified( $table_suffix, $check_sitemeta = false, $site_arg = null ) {

		global $wpdb;

		$table_suffix = trim( (string) $table_suffix );
		if ( $table_suffix === '' ) {
			return false;
		}

		$sites_list = ADBC_Sites::instance()->get_sites_list( $site_arg );

		// Build table names for all sites.
		$table_names = [];
		foreach ( $sites_list as $site ) {
			if ( empty( $site['prefix'] ) ) {
				continue;
			}
			$table_names[] = $site['prefix'] . $table_suffix;
		}

		// Optionally include sitemeta (network table).
		// In WP multisite, sitemeta is NOT per-blog; it uses the base prefix.
		if ( $check_sitemeta ) {
			$table_names[] = $wpdb->sitemeta; // already fully prefixed
		}

		$table_names = array_values( array_unique( array_filter( $table_names ) ) );

		if ( count( $table_names ) <= 1 ) {
			return true; // single table => "unified" enough for UNION purposes.
		}

		// Query information_schema for collation + charset.
		$placeholders = implode( ',', array_fill( 0, count( $table_names ), '%s' ) );

		$sql = $wpdb->prepare(
			"SELECT TABLE_NAME, TABLE_COLLATION
			 FROM information_schema.TABLES
			 WHERE TABLE_SCHEMA = DATABASE()
			   AND TABLE_NAME IN ($placeholders)",
			...$table_names
		);

		$rows = $wpdb->get_results( $sql, ARRAY_A );

		// If we cannot inspect (permissions) OR we didn't get all rows back, fail safe.
		if ( empty( $rows ) || count( $rows ) < count( $table_names ) ) {
			return false;
		}

		$collations = [];
		$charsets = [];

		foreach ( $rows as $r ) {
			$coll = $r['TABLE_COLLATION'] ?? '';
			if ( $coll === '' ) {
				// Some engines can return NULL; treat as mixed/unsafe for UNION.
				return false;
			}
			$collations[ $coll ] = true;

			// Derive charset from collation: charset is the part before first underscore, e.g. utf8mb4_...
			$charset = strstr( $coll, '_', true );
			if ( $charset === false || $charset === '' ) {
				return false;
			}
			$charsets[ $charset ] = true;
		}

		// For UNION stability, same collation is ideal.
		// If you want to be slightly looser, you could accept same charset only; but that can still error.
		return ( count( $collations ) === 1 && count( $charsets ) === 1 );

	}

}