<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC plugins class.
 * 
 * This class provides methods for plugins.
 */
class ADBC_Plugins extends ADBC_Singleton {

	/**
	 * The list of installed plugins with their status and info.
	 * 
	 * @var array
	 */
	private static $plugins_info = [];

	/**
	 * Constructor.
	 */
	protected function __construct() {

		parent::__construct();

		// Include plugin.php to get functions like get_plugins.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( is_multisite() ) {
			$this->prepare_plugins_info_in_multisite();
		} else {
			$this->prepare_plugins_info_in_single_site();
		}

		$this->prepare_mu_plugins_info();

		// Sort the plugins info alphabetically by plugin name.
		uasort( self::$plugins_info, function ($a, $b) {
			return strcasecmp( $a['name'], $b['name'] );
		} );

	}

	/**
	 * Get the plugins info.
	 * 
	 * @return array The plugins info.
	 */
	public function get_plugins_info() {

		return self::$plugins_info;
	}

	/**
	 * Prepare the plugins info in single site.
	 * 
	 * @return void
	 */
	private function prepare_plugins_info_in_single_site() {

		// Prepare the plugins info.
		$plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins', [] );

		foreach ( $plugins as $plugin_path => $plugin ) {
			$plugin_slug = $this->get_plugin_slug_from_path( $plugin_path );
			self::$plugins_info[ $plugin_slug ] = [ 
				'name' => $plugin['Name'],
				'active_on' => in_array( $plugin_path, $active_plugins ) ? [ "1" ] : [],
				'type' => 'plugin'
			];
		}
	}

	/**
	 * Prepare the plugins info in multisite.
	 * 
	 * @return void
	 */
	private function prepare_plugins_info_in_multisite() {

		$plugins = get_plugins();
		$sites = ADBC_Sites::instance()->get_sites_list();
		$network_active_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );

		foreach ( $sites as $site ) {

			ADBC_Sites::instance()->switch_to_blog_id( $site['id'] );

			$active_plugins = get_option( 'active_plugins', [] );

			foreach ( $plugins as $plugin_path => $plugin ) {

				$plugin_slug = $this->get_plugin_slug_from_path( $plugin_path );

				if ( ! isset( self::$plugins_info[ $plugin_slug ] ) ) {

					self::$plugins_info[ $plugin_slug ] = [ 
						'name' => $plugin['Name'],
						'active_on' => [],
						'type' => 'plugin'
					];

				}
				// If the plugin is active on the current site, or is network active, add the site id to the active_on array.
				if ( in_array( $plugin_path, $active_plugins ) || in_array( $plugin_path, $network_active_plugins ) ) {
					self::$plugins_info[ $plugin_slug ]['active_on'][] = (string) $site['id'];
				}
			}

			ADBC_Sites::instance()->restore_blog();
		}
	}

	/**
	 * Add the mu-plugins info to the plugins info.
	 * 
	 * @return void
	 */
	private function prepare_mu_plugins_info() {

		// Prepare the mu-plugins info.
		$mu_plugins = get_mu_plugins();

		// Prepare an array to store the "active_on" sites. In MU, the mu-plugins are active on all sites.
		$active_on = [];
		if ( is_multisite() ) {
			$sites = ADBC_Sites::instance()->get_sites_list();
			foreach ( $sites as $site ) {
				$active_on[] = (string) $site['id'];
			}
		} else {
			$active_on[] = "1";
		}

		foreach ( $mu_plugins as $mu_plugin_path => $mu_plugin ) {
			$mu_plugin_slug = $this->get_plugin_slug_from_path( $mu_plugin_path );
			// If no plugin with the same slug is found, add the mu-plugin.
			if ( ! isset( self::$plugins_info[ $mu_plugin_slug ] ) ) {
				self::$plugins_info[ $mu_plugin_slug ] = [ 
					'name' => $mu_plugin['Name'],
					'active_on' => $active_on,
					'type' => 'mu-plugin'
				];
			}
		}

		// Prepare the folders inside mu-plugins. This is useful to provide information about items that may be found in a folder inside mu-plugins.
		// We consider that the folder name is the plugin slug.
		$mu_plugins_dirs = ADBC_Files::instance()->get_list_of_dirs_inside_dir( WPMU_PLUGIN_DIR );
		foreach ( $mu_plugins_dirs as $mu_plugin_dir ) {
			// If no plugin with the same slug is found, add the mu-plugin folder.
			if ( ! isset( self::$plugins_info[ $mu_plugin_dir ] ) ) {
				self::$plugins_info[ $mu_plugin_dir ] = [ 
					'name' => $mu_plugin_dir,
					'active_on' => $active_on,
					'type' => 'mu-plugin-folder'
				];
			}
		}

	}

	/**
	 * Get plugins list based on the type (active or inactive).
	 *
	 * @param string $plugin_type Plugin type (active or inactive).
	 * @return string[] Array of plugins.
	 */
	public function get_plugins_list( $plugin_type ) {

		$plugins = get_plugins();

		$active_plugins = get_option( 'active_plugins', [] );

		$plugins_list = [];

		foreach ( $plugins as $plugin_path => $plugin ) {

			// Filter active plugins.
			if ( 'active' === $plugin_type && ! in_array( $plugin_path, $active_plugins ) ) {
				continue;
			}

			// Filter inactive plugins.
			if ( 'inactive' === $plugin_type && in_array( $plugin_path, $active_plugins ) ) {
				continue;
			}

			$plugins_list[] = '- ' . substr( $plugin['Name'], 0, 70 ) . ' - ' . $plugin['Version'];
		}

		return $plugins_list;
	}

	/**
	 * Get drop-ins list.
	 * 
	 * @return string[] Array of drop-ins.
	 */
	public function get_dropins_list() {

		$dropins = get_dropins();
		$dropins_info = [];

		foreach ( $dropins as $dropin => $dropin_data ) {
			$dropins_info[] = '- ' . substr( $dropin_data['Name'], 0, 70 ) . ' - ' . $dropin_data['Version'];
		}

		return $dropins_info;
	}

	/**
	 * Get mu-plugins list.
	 * 
	 * @return string[] Array of mu-plugins.
	 */
	public function get_mu_plugins_list() {

		$mu_plugins = get_mu_plugins();
		$mu_plugins_list = [];

		foreach ( $mu_plugins as $mu_plugin => $mu_plugin_data ) {
			$mu_plugins_list[] = '- ' . substr( $mu_plugin_data['Name'], 0, 70 ) . ' - ' . $mu_plugin_data['Version'];
		}

		return $mu_plugins_list;
	}

	/**
	 * Get network active plugins list.
	 * 
	 * @return string[] Array of network active plugins.
	 */
	public function get_network_active_plugins_list() {

		$plugins_list = [];

		if ( ! is_multisite() ) {
			return $plugins_list;
		}

		// Get the full paths of network-active plugins.
		$plugins_paths = $this->get_active_network_plugins();

		foreach ( $plugins_paths as $plugin_path ) {

			$plugin_data = get_plugin_data( $plugin_path );
			$plugins_list[] = '- ' . substr( $plugin_data['Name'], 0, 70 ) . ' - ' . $plugin_data['Version'];

		}

		return $plugins_list;

	}

	/**
	 * Get network active plugins list.
	 * 
	 * This function is an override of the wp_get_active_network_plugins function in wp-includes/ms-load.php.
	 * 
	 * @return string[] Array of network active plugins list.
	 */
	private function get_active_network_plugins() {

		$active_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );

		if ( empty( $active_plugins ) ) {
			return array();
		}

		$plugins = array();
		$active_plugins = array_keys( $active_plugins );
		sort( $active_plugins );

		foreach ( $active_plugins as $plugin ) {

			if ( ! validate_file( $plugin ) // $plugin must validate as file.
				&& substr( $plugin, -4 ) === '.php' // $plugin must end with '.php'. (substr is used to be compatible with PHP < 8)
				&& file_exists( WP_PLUGIN_DIR . '/' . $plugin ) // $plugin must exist.
			) {
				$plugins[] = WP_PLUGIN_DIR . '/' . $plugin;
			}

		}

		return $plugins;
	}

	/**
	 * Get plugin slug from the plugin path. Works for both plugins and mu-plugins.
	 * 
	 * @param string $plugin_path Plugin path.
	 * @return string Plugin slug.
	 */
	private function get_plugin_slug_from_path( $plugin_path ) {

		$plugin_slug = plugin_basename( dirname( $plugin_path ) );

		if ( '.' === $plugin_slug ) {
			$plugin_slug = basename( $plugin_path );
		}

		return $plugin_slug;
	}

	/**
	 * Get the plugin name from the plugin slug.
	 * 
	 * @param string $plugin_slug The plugin slug.
	 * @param string $default The default value to return if the plugin is not found. Default is 'slug'. Set to an empty string to return an empty string.
	 * @return string The plugin name.
	 */
	public function get_plugin_name_from_slug( $plugin_slug, $default = 'slug' ) {

		if ( isset( self::$plugins_info[ $plugin_slug ]['name'] ) )
			return self::$plugins_info[ $plugin_slug ]['name'];

		// If the plugin is not found, return the slug or the default value.
		if ( 'slug' === $default )
			return $plugin_slug;

		return '';
	}

	/**
	 * Check if a plugin slug is currently installed.
	 * 
	 * @param string $slug The plugin slug.
	 * @return bool True if the plugin is installed, false otherwise.
	 */
	public function is_plugin_slug_currently_installed( $slug ) {
		return array_key_exists( $slug, $this->get_plugins_info() );
	}

	/**
	 * Get the plugin status based on the slug and site id.
	 * 
	 * @param string $slug The plugin slug.
	 * @param string $site_id The site id. Default is "1".
	 * @return string The plugin status. "active", "inactive" or "not_installed" or "N/A" if the site id is invalid.
	 */
	public function get_plugin_status( $slug, $site_id = "1" ) {

		// For tables with invalid prefix, the site id is "N/A". In multi-site, return "N/A" directly, otherwise, put the site id as "1".
		if ( $site_id === "N/A" ) {
			if ( is_multisite() ) {
				return "N/A";
			} else {
				$site_id = "1";
			}
		}

		if ( isset( self::$plugins_info[ $slug ] ) ) {

			if ( in_array( $site_id, self::$plugins_info[ $slug ]['active_on'] ) )
				return "active";

			return "inactive";
		}

		return "not_installed";

	}

}