<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC addons class.
 * 
 * This class provides methods shared by plugins/themes that are currently installed on the site.
 */
class ADBC_Addons {

	/**
	 * Get the name of the plugin/theme based on its slug and type.
	 * 
	 * @param string $slug The plugin/theme slug.
	 * @param string $type The plugin/theme type. "p" for plugin, "t" for theme.
	 * @param string $default The default value to return if the addon is not found. Default is 'slug'. Set to an empty string to return an empty string.
	 * @return string The plugin/theme name.
	 */
	public static function get_addon_name( $slug, $type, $default = 'slug' ) {

		// If the type is "p" (plugin), get the plugin name.
		if ( $type === 'p' )
			return ADBC_Plugins::instance()->get_plugin_name_from_slug( $slug, $default );

		// If the type is "t" (theme), get the theme name.
		return ADBC_Themes::instance()->get_theme_name_from_slug( $slug, $default );

	}

	/**
	 * Get the plugin/theme status based on the slug, type and site id.
	 * 
	 * @param string $slug The plugin/theme slug.
	 * @param string $type The slug type. "p" for plugin, "t" for theme.
	 * @param string $site_id The site id. Default is "1".
	 * @return string The plugin status. "active", "inactive" or "not_installed" or "N/A" if the site id is invalid.
	 */
	public static function get_addon_status( $slug, $type, $site_id = "1" ) {

		// If the type is "p" (plugin), get the plugin status.
		if ( $type === 'p' )
			return ADBC_Plugins::instance()->get_plugin_status( $slug, $site_id );

		// If the type is "t" (theme), get the theme status.
		return ADBC_Themes::instance()->get_theme_status( $slug, $site_id );

	}

	/**
	 * Get all installed addons names.
	 * 
	 * @return array The installed addons typed slug as key and the name as value.
	 */
	public static function get_all_installed_addons() {

		$plugins_info = ADBC_Plugins::instance()->get_plugins_info();
		$themes_info = ADBC_Themes::instance()->get_themes_info();

		$addons_slug_names = [];

		foreach ( $plugins_info as $slug => $plugin_info ) {
			$addons_slug_names[ 'p:' . $slug ] = $plugin_info['name'];
		}

		foreach ( $themes_info as $slug => $theme_info ) {
			$addons_slug_names[ 't:' . $slug ] = $theme_info['name'];
		}

		return $addons_slug_names;

	}

}