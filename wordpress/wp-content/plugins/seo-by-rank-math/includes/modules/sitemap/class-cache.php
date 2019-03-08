<?php
/**
 * Handles sitemaps caching and invalidation.
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Sitemap
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Sitemap;

use WpeCommon;
use SG_CachePress_Supercacher;
use RankMath\Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\WordPress;

defined( 'ABSPATH' ) || exit;

/**
 * Cache class.
 */
class Cache {

	use Hooker;

	/**
	 * Cache mode.
	 *
	 * @var string
	 */
	private $mode = 'db';

	/**
	 * The $wp_filesystem object.
	 *
	 * @var object WP_Filesystem
	 */
	private $wp_filesystem;

	/**
	 * Prefix of the filename for sitemap caches.
	 *
	 * @var string
	 */
	const STORAGE_KEY_PREFIX = 'rank_math_';

	/**
	 * The Constructor
	 */
	public function __construct() {
		$this->wp_filesystem = WordPress::get_filesystem();
		$this->mode          = $this->is_writable() ? 'file' : 'db';
	}

	/**
	 * Is the file writable?
	 *
	 * @return bool
	 */
	public function is_writable() {
		$directory_separator = '/';
		$folder_path         = $this->get_cache_directory();
		$test_file           = $folder_path . $this->get_storage_key();

		// If folder doesn't exist?
		if ( ! file_exists( $folder_path ) ) {
			// Can we create the folder?
			// returns true if yes and false if not.
			$permissions = ( defined( 'FS_CHMOD_DIR' ) ) ? FS_CHMOD_DIR : 0755;
			return $this->wp_filesystem->mkdir( $folder_path, $permissions );
		}

		// Does the file exist?
		// File exists. Is it writable?
		if ( file_exists( $test_file ) && ! $this->wp_filesystem->is_writable( $test_file ) ) {
			// Nope, it's not writable.
			return false;
		}

		// Folder exists, but is it actually writable?
		return $this->wp_filesystem->is_writable( $folder_path );
	}

	/**
	 * Get the sitemap that is cached.
	 *
	 * @param  string $type Sitemap type.
	 * @param  int    $page Page number to retrieve.
	 * @return false|string false on no cache found otherwise sitemap file.
	 */
	public function get_sitemap( $type, $page ) {
		$filename = $this->get_storage_key( $type, $page );
		if ( false === $filename ) {
			return false;
		}

		if ( 'file' === $this->mode ) {
			return $this->wp_filesystem->get_contents( self::get_cache_directory() . $filename );
		}

		$filename = "sitemap_{$type}_$filename";
		$sitemap  = get_transient( $filename );
		return unserialize( $sitemap );
	}

	/**
	 * Store the sitemap page from cache.
	 *
	 * @param  string $type    Sitemap type.
	 * @param  int    $page    Page number to store.
	 * @param  string $sitemap Sitemap body to store.
	 * @return boolean
	 */
	public function store_sitemap( $type, $page, $sitemap ) {
		$filename = $this->get_storage_key( $type, $page );
		if ( false === $filename ) {
			return false;
		}

		if ( 'file' === $this->mode ) {
			$stored = $this->wp_filesystem->put_contents( self::get_cache_directory() . $filename, $sitemap, FS_CHMOD_FILE );
			if ( true === $stored ) {
				self::cached_files( $filename, $type );
				return $stored;
			}
		}

		$filename = "sitemap_{$type}_$filename";
		return set_transient( $filename, serialize( $sitemap ), DAY_IN_SECONDS * 100 );
	}

	/**
	 * Get filename for sitemap
	 *
	 * @param  null|string $type The type to get the key for. Null or '1' for index cache.
	 * @param  int         $page The page of cache to get the key for.
	 * @return boolean|string The key where the cache is stored on. False if the key could not be generated.
	 */
	public function get_storage_key( $type = null, $page = 1 ) {
		$type = is_null( $type ) ? '1' : $type;

		$filename = self::STORAGE_KEY_PREFIX . md5( "{$type}_{$page}_" . home_url() ) . '.xml';

		return $filename;
	}

	/**
	 * Get cache directory
	 *
	 * @return string
	 */
	public static function get_cache_directory() {
		$default = rank_math()->plugin_dir() . 'sitemap-cache';

		/**
		 * Filter XML sitemap cache directory.
		 *
		 * @param string $unsigned Default cache directory
		 */
		$filtered = apply_filters( 'rank_math/sitemap/cache_directory', $default );

		if ( ! is_string( $filtered ) || '' === $filtered ) {
			$filtered = $default;
		}

		return trailingslashit( $filtered );
	}

	/**
	 * Read/Write cached files.
	 *
	 * @param  mixed  $value Pass null to get option,
	 *                       Pass false to delete option,
	 *                       Pass value to update option.
	 * @param  string $type  Sitemap type.
	 * @return mixed
	 */
	public static function cached_files( $value = null, $type = '' ) {
		if ( '' !== $type ) {
			$options           = Helper::option( 'sitemap_cache_files' );
			$options[ $value ] = $type;
			return Helper::option( 'sitemap_cache_files', $options );
		}

		return Helper::option( 'sitemap_cache_files', $value );
	}

	/**
	 * Invalidate sitemap cache.
	 *
	 * @param null|string $type The type to get the key for. Null for all caches.
	 */
	public static function invalidate_storage( $type = null ) {
		$directory     = self::get_cache_directory();
		$wp_filesystem = WordPress::get_filesystem();

		if ( is_null( $type ) ) {
			$wp_filesystem->delete( $directory, true );
			$wp_filesystem->mkdir( $directory, FS_CHMOD_FILE );
			self::clear_transients();
			self::cached_files( false );
			self::clear_cache();
			return;
		}

		$data  = array();
		$files = self::cached_files();
		foreach ( $files as $file => $sitemap_type ) {
			if ( $type !== $sitemap_type ) {
				$data[ $file ] = $sitemap_type;
				continue;
			}

			$wp_filesystem->delete( $directory . $file );
		}

		self::clear_transients( $type );
		self::cached_files( $data );
		self::clear_cache();
	}

	/**
	 * Reset ALL transient caches.
	 *
	 * @param null|string $type The type to get the key for. Null for all caches.
	 */
	private static function clear_transients( $type = null ) {
		global $wpdb;
		if ( is_null( $type ) ) {
			return $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_sitemap_%'" );
		}

		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_sitemap_" . $type . "_%'" ); // phpcs:ignore
	}

	/**
	 * Clear cache from:
	 *  - W3TC,
	 *  - WordPress Total Cache
	 *  - WPEngine
	 *  - Varnish
	 *
	 * @access public
	 */
	private static function clear_cache() {
		// If W3 Total Cache is being used, clear the cache.
		if ( function_exists( 'w3tc_pgcache_flush' ) ) {
			w3tc_pgcache_flush();
		}

		// if WP Super Cache is being used, clear the cache.
		if ( function_exists( 'wp_cache_clean_cache' ) ) {
			global $file_prefix;
			wp_cache_clean_cache( $file_prefix );
		}

		// If SG CachePress is installed, rese its caches.
		if ( class_exists( 'SG_CachePress_Supercacher' ) && is_callable( array( 'SG_CachePress_Supercacher', 'purge_cache' ) ) ) {
			SG_CachePress_Supercacher::purge_cache();
		}

		// Clear caches on WPEngine-hosted sites.
		if ( class_exists( 'WpeCommon' ) ) {
			WpeCommon::purge_memcached();
			WpeCommon::clear_maxcdn_cache();
			WpeCommon::purge_varnish_cache();
		}

		// Clear Varnish caches.
		self::clear_varnish_cache();
	}

	/**
	 * Clear varnish cache for the dynamic CSS file.
	 */
	private static function clear_varnish_cache() {
		// Parse the URL for proxy proxies.
		$parsed_url = wp_parse_url( home_url() );

		// Build a varniship.
		$varniship = get_option( 'vhp_varnish_ip' );
		if ( defined( 'VHP_VARNISH_IP' ) && VHP_VARNISH_IP != false ) {
			$varniship = VHP_VARNISH_IP;
		}

		// If we made varniship, let it sail.
		$purgeme = ( isset( $varniship ) && null != $varniship ) ? $varniship : $parsed_url['host'];
		wp_remote_request( 'http://' . $purgeme,
			array(
				'method'  => 'PURGE',
				'headers' => array(
					'host'           => $parsed_url['host'],
					'X-Purge-Method' => 'default',
				),
			)
		);
	}
}
