<?php
/**
 * The admin engine of the plugin.
 *
 * @since      1.0.9
 * @package    RankMath
 * @subpackage RankMath\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Admin;

use RankMath\Helper;
use RankMath\Updates;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Conditional;
use RankMath\Search_Console\Search_Console;

defined( 'ABSPATH' ) || exit;

/**
 * Engine class.
 *
 * @codeCoverageIgnore
 */
class Engine {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {

		rank_math()->admin        = new Admin;
		rank_math()->admin_assets = new Assets;

		$this->load_setup_wizard();
		$this->search_console_ajax();

		$runners = array(
			rank_math()->admin,
			rank_math()->admin_assets,
			new Admin_Menu,
			new Option_Center,
			new Metabox,
			new Post_Columns,
			new Import_Export,
			new Notices,
			new CMB2_Fields,
			new Deactivate_Survey,
			new Updates,
			new Watcher,
		);

		foreach ( $runners as $runner ) {
			$runner->hooks();
		}

		/**
		 * Fires when admin is loaded.
		 */
		$this->do_action( 'admin/loaded' );
	}

	/**
	 * Load setup wizard.
	 */
	private function load_setup_wizard() {
		if ( filter_input( INPUT_GET, 'page' ) === 'rank-math-wizard' || filter_input( INPUT_POST, 'action' ) === 'rank_math_save_wizard' ) {
			new Setup_Wizard;
		}
	}

	/**
	 * Search console ajax handler.
	 */
	private function search_console_ajax() {
		if ( ! Conditional::is_ajax() || class_exists( 'Search_Console' ) ) {
			return;
		}

		if ( isset( $_POST['action'] ) && in_array( $_POST['action'], array( 'rank_math_search_console_authentication', 'rank_math_search_console_deauthentication', 'rank_math_search_console_get_profiles' ) ) ) {
			Helper::update_modules( array( 'search-console' => 'on' ) );
			new Search_Console;
		}
	}
}
