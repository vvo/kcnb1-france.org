<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC sites class.
 * 
 * This class provides the sites functions. Used to get the sites list and count...
 */
class ADBC_Sites extends ADBC_Singleton {

	private static $prefix_list = []; // Holds the list of prefixes for all tables with the prefix as the key and the site ID as the value.
	private static $switched = null; // Holds the blog ID we are currently switched to. Or null if not switched.

	/**
	 * Override the parent constructor to prepare all tables prefixes.
	 */
	protected function __construct() {
		parent::__construct();
		$this->prepare_all_prefixes();
	}

	/**
	 * Prepare all tables prefixes either for single site or multisite.
	 *
	 * @return void
	 */
	private function prepare_all_prefixes() {

		global $wpdb;

		if ( is_multisite() ) {

			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs ORDER BY blog_id ASC" );

			foreach ( $blog_ids as $blog_id ) {
				self::$prefix_list[ $wpdb->get_blog_prefix( $blog_id ) ] = (int) $blog_id;
			}

		} else {
			self::$prefix_list[ $wpdb->prefix ] = get_current_blog_id();
		}

	}

	/**
	 * Get all tables prefixes.
	 *
	 * @return array The list of all tables prefixes.
	 */
	public function get_all_prefixes() {
		return self::$prefix_list;
	}

	/**
	 * Switch to the given blog ID.
	 *
	 * @param int $id The blog ID to switch to.
	 */
	public function switch_to_blog_id( $id ) {

		$id = (int) $id;

		if ( $id !== get_current_blog_id() ) {
			switch_to_blog( $id );
			self::$switched = $id;
		}
	}

	/**
	 * Restore the current blog to the original one.
	 *
	 * @return void
	 */
	public function restore_blog() {
		if ( self::$switched !== null ) {
			restore_current_blog();
			self::$switched = null;
		}
	}

	/**
	 * Get the table prefix from the site ID.
	 *
	 * @param int $site_id The site ID.
	 * @return string|null The table prefix for the given site ID or null if not found.
	 */
	public function get_prefix_from_site_id( $site_id ) {

		$site_id = (int) $site_id;

		foreach ( self::$prefix_list as $prefix => $id ) {

			if ( $id === $site_id )
				return $prefix;
		}

		return null;
	}

	/**
	 * Get the site ID from the table prefix.
	 *
	 * @param string $prefix The table prefix.
	 * @return int|null The site ID for the given prefix or null if not found.
	 */
	public function get_site_id_from_prefix( $prefix ) {
		return isset( self::$prefix_list[ $prefix ] ) ? self::$prefix_list[ $prefix ] : null;
	}

	/**
	 * Count sites in multisite.
	 *
	 * @return string|int Formatted site count.
	 */
	public function count_sites_in_multisite( $return_type = 'int' ) {

		if ( ! is_multisite() )
			return $return_type == 'string' ? __( '1 site', 'advanced-database-cleaner' ) : 1;

		$site_count = get_sites( [ 'count' => true ] );

		if ( $return_type === 'string' )
			return sprintf(
				/* translators: 1: Number of sites */
				_n( '%s site', '%s sites', $site_count, 'advanced-database-cleaner' ), $site_count );

		return $site_count;
	}

	/**
	 * Return a list of sites (network-wide or a single site).
	 *
	 * @param string|int $site_id_filter Either "all" or a numeric site-ID (as string/int).
	 * @return array[] Array of [ id, name, prefix ] rows.
	 */
	public function get_sites_list( $site_id_filter = 'all' ) {
		global $wpdb;

		// Single site
		if ( ! is_multisite() ) {
			return [ 
				[ 
					'id' => get_current_blog_id(),
					'name' => get_bloginfo( 'name' ),
					'prefix' => $wpdb->prefix,
				],
			];
		}

		// Forced site ID in multisite
		if ( $site_id_filter !== 'all' ) {

			$site = get_site( $site_id_filter );
			if ( $site instanceof WP_Site ) {
				return [ 
					[ 
						'id' => $site->blog_id,
						'name' => get_blog_option( $site->blog_id, 'blogname' ),
						'prefix' => $wpdb->get_blog_prefix( $site->blog_id ),
					],
				];
			}
		}

		// Get all sites in multisite
		$sites = get_sites( [ 'number' => 0 ] ); // load all
		$results = [];

		foreach ( $sites as $site ) {
			$results[] = [ 
				'id' => $site->blog_id,
				'name' => get_blog_option( $site->blog_id, 'blogname' ),
				'prefix' => $wpdb->get_blog_prefix( $site->blog_id ),
			];
		}

		return $results;
	}

}