<?php
/**
 * The SEO Analysis Module
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\SEO_Analysis;

use RankMath\Helper;
use RankMath\Module;
use MyThemeShop\Admin\Page;

defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 */
class Admin extends Module {

	/**
	 * The Constructor.
	 */
	public function __construct() {

		$directory = dirname( __FILE__ );
		$this->config(
			[
				'id'        => 'seo-analysis',
				'directory' => $directory,
				'help'      => [
					'title' => esc_html__( 'SEO Analysis', 'rank-math' ),
					'view'  => $directory . '/views/help.php',
				],
			]
		);
		parent::__construct();

		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || $this->page->is_current_page() ) {
			include_once 'seo-analysis-tests.php';
			$this->analyzer = new SEO_Analyzer;
		}
	}

	/**
	 * Register admin page.
	 */
	public function register_admin_page() {
		$uri = untrailingslashit( plugin_dir_url( __FILE__ ) );

		$this->page = new Page(
			'rank-math-seo-analysis',
			esc_html__( 'SEO Analysis', 'rank-math' ),
			[
				'position'   => 12,
				'parent'     => 'rank-math',
				'capability' => 'rank_math_site_analysis',
				'classes'    => [ 'rank-math-page' ],
				'render'     => $this->directory . '/views/main.php',
				'help'       => [
					'seo-analysis-overview' => [
						'title'   => esc_html__( 'SEO Analysis', 'rank-math' ),
						'content' => '<p>' . esc_html__( 'Run the SEO Analysis to see suggestions on improving your rank in search engines.', 'rank-math' ) . '</p>',
					],
				],
				'assets'     => [
					'styles'  => [
						'rank-math-common'       => '',
						'rank-math-seo-analysis' => $uri . '/assets/seo-analysis.css',
					],
					'scripts' => [
						'circle-progress'        => $uri . '/assets/circle-progress.min.js',
						'rank-math-seo-analysis' => $uri . '/assets/seo-analysis.js',
					],
				],
			]
		);
	}
}
