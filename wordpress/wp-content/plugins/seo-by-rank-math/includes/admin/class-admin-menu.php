<?php
/**
 * The admin pages of the plugin.
 *
 * @since      1.0.9
 * @package    RankMath
 * @subpackage RankMath\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Admin;

use RankMath\Runner;
use RankMath\Traits\Hooker;
use MyThemeShop\Admin\Page;
use RankMath\Helper as GlobalHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Admin_Menu class.
 *
 * @codeCoverageIgnore
 */
class Admin_Menu implements Runner {

	use Hooker;

	/**
	 * Register hooks.
	 */
	public function hooks() {
		$this->action( 'init', 'register_pages' );
		$this->action( 'admin_menu', 'fix_first_submenu', 999 );
		$this->action( 'admin_head', 'icon_css' );
	}

	/**
	 * Register admin pages for plugin.
	 */
	public function register_pages() {
		$this->check_registration();

		if ( GlobalHelper::is_invalid_registration() && ! is_network_admin() ) {
			return;
		}

		// Dashboard / Welcome / About.
		new Page( 'rank-math', esc_html__( 'Rank Math', 'rank-math' ), array(
			'position'   => 80,
			'capability' => 'manage_options',
			'icon'       => rank_math()->plugin_url() . 'assets/admin/img/menu-icon.svg',
			'render'     => Admin_Helper::get_view( 'dashboard' ),
			'classes'    => array( 'rank-math-page' ),
			'assets'     => array(
				'styles'  => array( 'rank-math-dashboard' => '' ),
				'scripts' => array( 'rank-math-dashboard' => '' ),
			),
			'is_network' => is_network_admin() && GlobalHelper::is_plugin_active_for_network(),
		));

		// Help & Support.
		new Page( 'rank-math-help', esc_html__( 'Help &amp; Support', 'rank-math' ), array(
			'position'   => 99,
			'parent'     => 'rank-math',
			'capability' => 'level_1',
			'classes'    => array( 'rank-math-page' ),
			'render'     => Admin_Helper::get_view( 'help-manager' ),
			'assets'     => array(
				'styles'  => array( 'rank-math-common' => '' ),
				'scripts' => array( 'rank-math-common' => '' ),
			),
		));
	}

	/**
	 * Fix first submenu name.
	 */
	public function fix_first_submenu() {
		global $submenu;
		if ( ! isset( $submenu['rank-math'] ) ) {
			return;
		}

		if ( current_user_can( 'manage_options' ) && 'Rank Math' === $submenu['rank-math'][0][0] ) {
			$submenu['rank-math'][0][0] = esc_html__( 'Dashboard', 'rank-math' );
		} else {
			unset( $submenu['rank-math'][0] );
		}

		if ( empty( $submenu['rank-math'] ) ) {
			return;
		}

		// Store ID of first_menu item so we can use it in the Admin menu item.
		set_transient( 'rank_math_first_submenu_id', array_values( $submenu['rank-math'] )[0][2] );
	}

	/**
	 * Print icon CSS for admin menu bar.
	 */
	public function icon_css() {
		?>
		<style>
			#wp-admin-bar-rank-math .rank-math-icon {
				display: inline-block;
				top: 6px;
				position: relative;
				padding-right: 10px;
				max-width: 20px;
			}
			#wp-admin-bar-rank-math .rank-math-icon svg {
				fill-rule: evenodd;
				fill: #dedede;
			}
			#wp-admin-bar-rank-math:hover .rank-math-icon svg {
				fill-rule: evenodd;
				fill: #00b9eb;
			}
		</style>
		<?php
	}

	/**
	 * Check for registration.
	 */
	private function check_registration() {

		$what = isset( $_POST['registration-action'] ) ? $_POST['registration-action'] : false;
		if ( false === $what ) {
			return;
		}

		if ( 'register' === $what ) {
			Admin_Helper::allow_tracking();
			Admin_Helper::register_product( $_POST['connect-username'], $_POST['connect-password'] );
		}

		if ( 'deregister' === $what ) {
			Admin_Helper::get_registration_data( false );
		}
	}
}
