<?php
/**
 * Updates related functions and actions.
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Core
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath;

use RankMath\Traits\Hooker;


defined( 'ABSPATH' ) || exit;

/**
 * Updates class
 */
class Updates implements Runner {

	use Hooker;

	/**
	 * Updates that need to be run
	 *
	 * @var array
	 */
	private static $updates = [
		'0.9.8'  => 'updates/update-0.9.8.php',
		'0.10.0' => 'updates/update-0.10.0.php',
		'1.0.14' => 'updates/update-1.0.14.php',
		'1.0.15' => 'updates/update-1.0.15.php',
		'1.0.18' => 'updates/update-1.0.18.php',
		'1.0.24' => 'updates/update-1.0.24.php',
	];

	/**
	 * Register hooks.
	 */
	public function hooks() {
		$this->action( 'admin_init', 'do_updates' );
	}

	/**
	 * Check if need any update is required.
	 */
	public function do_updates() {
		$installed_version = get_option( 'rank_math_version' );

		// may be it's the first install.
		if ( ! $installed_version ) {
			return;
		}

		if ( version_compare( $installed_version, rank_math()->version, '<' ) ) {
			$this->perform_updates();
		}
	}

	/**
	 * Perform all updates.
	 */
	public function perform_updates() {
		$installed_version = get_option( 'rank_math_version' );

		foreach ( self::$updates as $version => $path ) {
			if ( version_compare( $installed_version, $version, '<' ) ) {
				include $path;
				update_option( 'rank_math_version', $version );
			}
		}

		// Save install date.
		if ( false == get_option( 'rank_math_install_date' ) ) {
			update_option( 'rank_math_install_date', current_time( 'timestamp' ) );
		}

		update_option( 'rank_math_version', rank_math()->version );
		update_option( 'rank_math_db_version', rank_math()->db_version );
	}
}
