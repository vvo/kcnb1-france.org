<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Scan Counter.
 *
 * This class provides the categorization counter for items. e.g. total orphans.
 */
class ADBC_Scan_Counter {

	// The categorization count.
	private $categorization_count = [ 'all' => 0, 'o' => 0, 'p' => 0, 't' => 0, 'w' => 0, 'u' => 0, 'unk' => 0 ];

	// The plugins count. Each plugin slug will have a name and a count.
	private $plugins_count = [];

	// The themes count. Each theme slug will have a name and a count.
	private $themes_count = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Refresh the categorization count.
	 *
	 * @return void
	 */
	public function refresh_categorization_count( $belongs_to ) {

		$type = $belongs_to['type'];

		if ( ! isset( $this->categorization_count[ $type ] ) )
			return;

		$this->categorization_count['all']++;
		$this->categorization_count[ $type ]++;

		if ( $type === 'p' ) {

			$this->refresh_addon_count( $this->plugins_count, $belongs_to );

		} else if ( $type === 't' ) {

			$this->refresh_addon_count( $this->themes_count, $belongs_to );

		}
	}

	/**
	 * Refresh the categorization count for plugins/themes.
	 * 
	 * @return void
	 */
	private function refresh_addon_count( &$count_array, $belongs_to ) {

		$slug = $belongs_to['slug'];

		if ( ! isset( $count_array[ $slug ] ) ) {
			$count_array[ $slug ] = [ 'name' => $belongs_to['name'], 'count' => 0 ];
		}

		$count_array[ $slug ]['count']++;
	}

	/**
	 * Get the categorization count.
	 *
	 * @return array the categorization count.
	 */
	public function get_categorization_count() {
		return $this->categorization_count;
	}

	/**
	 * Get the plugins count.
	 * 
	 * @return array the plugins count.
	 */
	public function get_plugins_count() {
		return $this->plugins_count;
	}

	/**
	 * Get the themes count.
	 * 
	 * @return array the themes count.
	 */
	public function get_themes_count() {
		return $this->themes_count;
	}

	/**
	 * Set the plugin name.
	 * 
	 * @return void
	 */
	public function set_plugin_name( $plugin_slug, $plugin_name ) {
		if ( isset( $this->plugins_count[ $plugin_slug ] ) ) {
			$this->plugins_count[ $plugin_slug ]['name'] = $plugin_name;
		}
	}

	/**
	 * Set the theme name.
	 * 
	 * @return void
	 */
	public function set_theme_name( $theme_slug, $theme_name ) {
		if ( isset( $this->themes_count[ $theme_slug ] ) ) {
			$this->themes_count[ $theme_slug ]['name'] = $theme_name;
		}
	}

}