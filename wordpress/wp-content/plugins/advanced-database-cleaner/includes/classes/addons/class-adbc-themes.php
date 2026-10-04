<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC themes class.
 * 
 * This class provides methods for themes.
 */
class ADBC_Themes extends ADBC_Singleton {

	/**
	 * The list of installed themes with their status and info.
	 * 
	 * @var array
	 */
	private static $themes_info = [];

	/**
	 * Constructor.
	 */
	protected function __construct() {

		parent::__construct();

		if ( ! is_multisite() ) {
			$this->prepare_themes_info_in_single_site();
		} else {
			$this->prepare_themes_info_in_multisite();
		}

		// Sort the themes info alphabetically by theme name.
		uasort( self::$themes_info, function ($a, $b) {
			return strcasecmp( $a['name'], $b['name'] );
		} );

	}

	/**
	 * Get the themes info.
	 * 
	 * @return array The themes info.
	 */
	public function get_themes_info() {

		return self::$themes_info;
	}

	/**
	 * Prepare the themes info in single site.
	 * 
	 * @return void
	 */
	private function prepare_themes_info_in_single_site() {

		$themes = wp_get_themes(); // Get the themes info.
		$active_child_theme_slug = get_stylesheet(); // Get the active child theme slug.
		$active_parent_theme_slug = get_template(); // Get the active parent theme slug.

		foreach ( $themes as $theme_slug => $theme ) {

			self::$themes_info[ $theme_slug ] = [ 
				'name' => $theme->get( 'Name' ),
				'active_on' => ( $theme_slug === $active_child_theme_slug || $theme_slug === $active_parent_theme_slug ) ? [ "1" ] : []
			];
		}
	}

	/**
	 * Prepare the themes info in multisite.
	 * 
	 * @return void
	 */
	private function prepare_themes_info_in_multisite() {

		$themes = wp_get_themes(); // Get the themes info.
		$sites = ADBC_Sites::instance()->get_sites_list();

		foreach ( $sites as $site ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );

			$active_child_theme_slug = get_stylesheet(); // Get the active child theme slug.
			$active_parent_theme_slug = get_template(); // Get the active parent theme slug.

			foreach ( $themes as $theme_slug => $theme ) {

				if ( ! isset( self::$themes_info[ $theme_slug ] ) ) {

					self::$themes_info[ $theme_slug ] = [ 
						'name' => $theme->get( 'Name' ),
						'active_on' => []
					];

				}
				// If the theme is active on the current site, add the site id to the active_on array.
				if ( $theme_slug === $active_child_theme_slug || $theme_slug === $active_parent_theme_slug ) {
					self::$themes_info[ $theme_slug ]['active_on'][] = (string) $site['id'];
				}
			}

			ADBC_Sites::instance()->restore_blog();
		}
	}

	/**
	 * Get the theme name from the theme slug.
	 * 
	 * @param string $theme_slug The theme slug.
	 * @param string $default The default value to return if the theme is not found. Default is 'slug'. Set to an empty string to return an empty string.
	 * @return string The theme name.
	 */
	public function get_theme_name_from_slug( $theme_slug, $default = 'slug' ) {

		if ( isset( self::$themes_info[ $theme_slug ]['name'] ) )
			return self::$themes_info[ $theme_slug ]['name'];

		// If the theme is not found, return the slug or the default value.
		if ( 'slug' === $default )
			return $theme_slug;

		return '';
	}

	/**
	 * Check if a theme slug is currently installed.
	 * 
	 * @param string $slug The theme slug.
	 * @return bool True if the theme is installed, false otherwise.
	 */
	public function is_theme_slug_currently_installed( $slug ) {
		return array_key_exists( $slug, self::$themes_info );
	}

	/**
	 * Get the theme status based on the slug and site id.
	 * 
	 * @param string $slug The theme slug.
	 * @param string $site_id The site id. Default is "1".
	 * @return string The theme status. "active", "inactive" or "not_installed" or "N/A" if the site id is invalid.
	 */
	public function get_theme_status( $slug, $site_id = "1" ) {

		// For tables with invalid prefix, the site id is "N/A". In multi-site, return "N/A" directly, otherwise, put the site id as "1".
		if ( $site_id === "N/A" ) {
			if ( is_multisite() ) {
				return "N/A";
			} else {
				$site_id = "1";
			}
		}

		if ( isset( self::$themes_info[ $slug ] ) ) {

			if ( in_array( $site_id, self::$themes_info[ $slug ]['active_on'] ) )
				return "active";

			return "inactive";
		}

		return "not_installed";

	}

	/**
	 * Get the theme slug from the theme name.
	 * 
	 * @param string $theme_name The theme name.
	 * @return string The theme slug.
	 */
	public function get_slug_from_theme_name( $theme_name ) {

		foreach ( self::$themes_info as $slug => $theme_info ) {
			if ( $theme_info['name'] === $theme_name ) {
				return $slug;
			}
		}

		return '';

	}

}