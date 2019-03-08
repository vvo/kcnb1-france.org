<?php
/**
 * The functionality to detect whether we should import from another SEO plugin
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Admin\Importers
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Admin\Importers;

use RankMath\Traits\Hooker;

defined( 'ABSPATH' ) || exit;

/**
 * Detector class.
 */
class Detector {

	use Hooker;

	/**
	 * Plugins we can import from
	 *
	 * @var array
	 */
	public static $plugins = null;

	/**
	 * Detects whether we can import anything
	 */
	public function detect() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_null( self::$plugins ) ) {
			return self::$plugins;
		}
		self::$plugins = array();

		$plugins = $this->get();
		foreach ( $plugins as $slug => $plugin ) {

			// Check if parent is set.
			if ( isset( $plugin['parent'] ) && isset( self::$plugins[ $plugin['parent'] ] ) ) {
				continue;
			}

			// Check if plugin has premium and it is active.
			if ( isset( $plugin['premium'] ) && is_plugin_active( $plugins[ $plugin['premium'] ]['file'] ) ) {
				continue;
			}

			$importer = new $plugin['class']( $plugin['file'] );
			if ( $this->run( $importer, 'detect' ) ) {
				self::$plugins[ $slug ] = array(
					'name'    => $importer->get_plugin_name(),
					'file'    => $importer->get_plugin_file(),
					'choices' => $importer->get_choices(),
				);
			}
		}

		return self::$plugins;
	}

	/**
	 * Detects active plugins
	 *
	 * @return array
	 */
	public function active_plugins() {
		$plugins = array();
		if ( is_null( self::$plugins ) ) {
			foreach ( $this->get() as $slug => $data ) {
				if ( is_plugin_active( $data['file'] ) ) {
					$plugins[ $slug ] = true;
				}
			}
		}

		return $plugins;
	}

	/**
	 * Run action by slug.
	 *
	 * @param string $slug    The importer slug that needs to perform this action.
	 * @param string $action  The action to perform.
	 * @param string $perform The action to perform when running import action.
	 */
	public static function run_by_slug( $slug, $action, $perform = '' ) {
		$detector  = new self;
		$importers = $detector->get();
		if ( ! isset( $importers[ $slug ] ) ) {
			return false;
		}

		$importer = $importers[ $slug ];
		$importer = new $importer['class']( $importer['file'] );
		$status   = $detector->run( $importer, $action, $perform );

		return \compact( 'importer', 'status' );
	}

	/**
	 * Run import class.
	 *
	 * @param Plugin_Importer $importer The importer that needs to perform this action.
	 * @param string          $action   The action to perform.
	 * @param string          $perform  The action to perform when running import action.
	 */
	public function run( $importer, $action = 'detect', $perform = '' ) {
		if ( 'cleanup' === $action ) {
			return $importer->run_cleanup();
		} elseif ( 'import' === $action ) {
			return $importer->run_import( $perform );
		}

		return $importer->run_detect();
	}

	/**
	 * Returns an array of importers available
	 *
	 * @return array Available importers
	 */
	public function get() {
		return $this->do_filter( 'importers/detect_plugins', array(
			'yoast'            => array(
				'class'   => '\\RankMath\\Admin\\Importers\\Yoast',
				'file'    => 'wordpress-seo/wp-seo.php',
				'premium' => 'yoast-premium',
			),
			'yoast-premium'    => array(
				'class'  => '\\RankMath\\Admin\\Importers\\Yoast',
				'file'   => 'wordpress-seo-premium/wp-seo-premium.php',
				'parent' => 'yoast',
			),
			'aioseo'           => array(
				'class' => '\\RankMath\\Admin\\Importers\\AIOSEO',
				'file'  => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
			),
			'aio-rich-snippet' => array(
				'class' => '\\RankMath\\Admin\\Importers\\AIO_Rich_Snippet',
				'file'  => 'all-in-one-schemaorg-rich-snippets/index.php',
			),
			'wp-schema-pro'    => array(
				'class' => '\\RankMath\\Admin\\Importers\\WP_Schema_Pro',
				'file'  => 'wp-schema-pro/wp-schema-pro.php',
			),
			'redirections'     => array(
				'class' => '\\RankMath\\Admin\\Importers\\Redirections',
				'file'  => 'redirection/redirection.php',
			),
		));
	}
}
