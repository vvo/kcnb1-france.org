<?php
/**
 * The SEO Analyzer
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\SEO_Analysis;

use RankMath\Helper;
use RankMath\Traits\Ajax;
use RankMath\Traits\Hooker;
use Rollbar\Rollbar;
use Rollbar\Payload\Level;
use MyThemeShop\Helpers\Str;

defined( 'ABSPATH' ) || exit;

/**
 * SEO_Analyzer class.
 */
class SEO_Analyzer {

	use Ajax, Hooker;

	/**
	 * Rank Math SEO Checkup API.
	 *
	 * @var string
	 */
	private $api_url = '';

	/**
	 * Url to analyze.
	 *
	 * @var string
	 */
	public $analyse_url = '';

	/**
	 * Sub-page url to analyze.
	 *
	 * @var string
	 */
	public $analyse_subpage = false;

	/**
	 * Hold analysis results.
	 *
	 * @var array
	 */
	public $results = array();

	/**
	 * Hold any api error.
	 *
	 * @var array
	 */
	private $api_error = '';

	/**
	 * Hold local test data.
	 *
	 * @var array
	 */
	private $local_tests = array();

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->api_url = $this->do_filter( 'seo_analysis/api_endpoint', 'https://mythemeshop.com/analyze/v2/json/' );

		$this->analyse_url = get_home_url();
		if ( ! empty( $_REQUEST['u'] ) && $this->is_allowed_url( $_REQUEST['u'] ) ) {
			$this->analyse_url     = $_REQUEST['u'];
			$this->analyse_subpage = true;
		}

		if ( ! $this->analyse_subpage ) {
			$this->results     = get_option( 'rank_math_seo_analysis_results' );
			$this->local_tests = $this->do_filter( 'seo_analysis/tests', array() );
		}

		$this->ajax( 'analyze', 'analyze_me' );
	}

	/**
	 * Analyze page.
	 */
	public function analyze_me() {

		check_ajax_referer( 'rank-math-ajax-nonce', 'security' );

		$this->has_cap_ajax( 'site_analysis' );

		if ( ! $this->run_api_tests() ) {
			\error_log( $this->api_error );
			Rollbar::log( Level::WARNING, $this->api_error );
			/* translators: API error */
			echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( __( '<strong>API Error:</strong> %s', 'rank-math' ), $this->api_error ) . '</p></div>';
		}

		if ( ! $this->analyse_subpage ) {
			$this->run_local_tests();
			$this->run_social_tests();
			update_option( 'rank_math_seo_analysis_results', $this->results );
		}

		$this->display_graphs();
		$this->display_results();
		die;
	}

	/**
	 * Run test through rank math api.
	 *
	 * @return boolean
	 */
	private function run_api_tests() {
		$api_url = add_query_arg( array(
			'u'      => $this->analyse_url,
			'ak'     => $this->get_api_key(),
			'locale' => get_locale(),
		), $this->api_url );

		$request = wp_remote_get( $api_url, array( 'timeout' => 20 ) );

		// API error.
		if ( is_wp_error( $request ) ) {
			$this->api_error = strip_tags( $request->get_error_message() );
			return false;
		}

		$response = wp_remote_retrieve_body( $request );
		if ( $response && is_string( $response ) ) {
			$response = json_decode( $response, true );
			if ( ! is_array( $response ) ) {
				return false;
			}

			if ( 200 !== absint( wp_remote_retrieve_response_code( $request ) ) ) {
				$this->api_error = join( ', ', $response['errors'] );
				return false;
			}

			foreach ( $response as $id => $results ) {

				if ( $this->analyse_subpage && in_array( $id, $this->get_excluded_tests() ) ) {
					continue;
				}

				$this->results[ $id ] = wp_parse_args( $results, array(
					'test_id'  => $id,
					'api_test' => true,
				) );
			}

			return true;
		}

		return false;
	}

	/**
	 * Run local site tests.
	 */
	private function run_local_tests() {

		foreach ( $this->local_tests as $id => $test ) {
			$this->results[ $id ] = array_merge( array(
				'test_id'     => $id,
				'api_test'    => false,
				'title'       => $test['title'],
				'description' => $test['description'],
				'how_to_fix'  => $test['how_to_fix'],
				'category'    => $test['category'],
				'info'        => array(),
			), call_user_func( $test['callback'], $this ) );
		}
	}

	/**
	 * Run Social SEO Tests
	 */
	private function run_social_tests() {

		$social_seo = array(
			'facebook'  => array(
				'name'  => esc_html__( 'Facebook', 'rank-math' ),
				'title' => esc_html__( 'Facebook Connected', 'rank-math' ),
			),
			'instagram' => array(
				'name'  => esc_html__( 'Instagram', 'rank-math' ),
				'title' => esc_html__( 'Instagram Connected', 'rank-math' ),
			),
			'linkedin'  => array(
				'name'  => esc_html__( 'Linkedin', 'rank-math' ),
				'title' => esc_html__( 'Linkedin Connected', 'rank-math' ),
			),
			'twitter'   => array(
				'name'  => esc_html__( 'Twitter', 'rank-math' ),
				'title' => esc_html__( 'Twitter Connected', 'rank-math' ),
			),
			'youtube'   => array(
				'name'  => esc_html__( 'Youtube', 'rank-math' ),
				'title' => esc_html__( 'Youtube Connected', 'rank-math' ),
			),
		);

		foreach ( $social_seo as $id => $social ) {
			$found = Helper::get_settings( 'titles.social_url_' . $id );
			$id    = $id . '_connected';

			$this->results[ $id ] = array(
				'test_id'  => $id,
				'api_test' => false,
				'title'    => $social['title'],
				'category' => 'social',
				'info'     => array(),
				'status'   => $found ? 'ok' : 'fail',
				/* translators: social name */
				'message'  => $found ? sprintf( esc_html__( 'Your website has a %s page connected to it.', 'rank-math' ), $social['name'] ) : sprintf( esc_html__( 'Your website has no %s connected to it.', 'rank-math' ), $social['name'] ),
			);
		}
	}

	/**
	 * Output graphs
	 */
	public function display_graphs() {
		if ( empty( $this->results ) ) {
			return;
		}

		$total    = 0;
		$percent  = 0;
		$statuses = array(
			'ok'      => 0,
			'fail'    => 0,
			'info'    => 0,
			'warning' => 0,
		);

		foreach ( $this->results as $test => $data ) {
			if ( 'info' === $data['status'] || ( $this->analyse_subpage && in_array( $test, $this->get_excluded_tests() ) ) ) {
				continue;
			}
			$statuses[ $data['status'] ]++;
			$total++;

			if ( 'ok' !== $data['status'] ) {
				continue;
			}
			$percent = $percent + $this->get_test_score( $test );
		}

		// calculate % result.
		$grade = 'good';
		$max   = max( $statuses['ok'], $statuses['warning'], $statuses['fail'] );

		if ( $percent < 70 ) {
			$grade = 'average';
		}

		if ( $percent < 50 ) {
			$grade = 'bad';
		}
		?>
		<div class="rank-math-result-graphs">

			<div class="two-col">

				<div class="graphs-main">
					<div id="rank-math-circle-progress" data-result="<?php echo ( $percent / 100 ); ?>"><strong class="score-<?php echo $grade; ?>"><?php echo $percent; ?></strong></div>
					<div class="result-score">
						<strong><?php echo $percent; ?>/100</strong>
						<label><?php esc_html_e( 'SEO Score', 'rank-math' ); ?></label>
					</div>
				</div>

				<div class="graphs-side">
					<ul class="chart">
						<li class="chart-bar-good">
							<span style="height:<?php echo round( $statuses['ok'] / $max * 100 ); ?>%"></span>
							<div class="result-score">
								<strong><?php echo $statuses['ok'] . '/' . $total; ?></strong>
								<label><?php esc_html_e( 'Passed Tests', 'rank-math' ); ?></label>
							</div>
						</li>
						<li class="chart-bar-average">
							<span style="height:<?php echo round( $statuses['warning'] / $max * 100 ); ?>%"></span>
							<div class="result-score">
								<strong><?php echo $statuses['warning'] . '/' . $total; ?></strong>
								<label><?php esc_html_e( 'Warnings', 'rank-math' ); ?></label>
							</div>
						</li>
						<li class="chart-bar-bad">
							<span style="height:<?php echo round( $statuses['fail'] / $max * 100 ); ?>%"></span>
							<div class="result-score">
								<strong><?php echo $statuses['fail'] . '/' . $total; ?></strong>
								<label><?php esc_html_e( 'Failed Tests', 'rank-math' ); ?></label>
							</div>
						</li>
					</ul>
				</div>

			</div>

			<?php if ( ! $this->analyse_subpage ) : ?>
			<footer class="rank-math-ui">
				<button data-what="website" class="button button-primary button-xlarge rank-math-recheck"><?php esc_html_e( 'Start Site-Wide Analysis', 'rank-math' ); ?></button>
			</footer>
			<?php endif; ?>

		</div>
		<?php
	}

	/**
	 * Output results in tables.
	 */
	public function display_results() {
		if ( empty( $this->results ) ) {
			return;
		}

		$categories = $this->sort_results_by_category();

		foreach ( $categories as $category => $results ) :
			$label = $this->get_category_label( $category );
			?>
			<div class="rank-math-result-table rank-math-result-category-<?php echo $category; ?>">
				<div class="category-title">
					<?php echo $label; ?>
				</div>
				<?php foreach ( $results as $id => $result ) : ?>
				<div class="table-row">
					<?php $this->output_test_result( $result, $id ); ?>
				</div>
				<?php endforeach; ?>
			</div>
			<?php
		endforeach;
	}

	/**
	 * Output test result.
	 *
	 * @param array  $result  Current result to output.
	 * @param string $test_id Current test ID.
	 */
	private function output_test_result( $result, $test_id ) {
		// Social entities.
		if ( Str::ends_with( '_connected', $test_id ) && 'fail' === $result['status'] ) {
			/* translators: link to social option setting */
			$result['fix'] = sprintf( __( 'Add Social Schema to your website by linking your social profiles <a href="%s">here</a>.', 'rank-math' ), Helper::get_admin_url( 'options-titles#setting-panel-social' ) );
		}
		?>
		<div class="row-title">
			<h3><?php echo $result['title']; ?></h3>
			<?php if ( ! empty( $result['tooltip'] ) ) : ?>
			<span class="rank-math-tooltip"><em class="dashicons-before dashicons-editor-help"></em><span><?php echo $result['tooltip']; ?></span></span>
			<?php endif; ?>
		</div>
		<div class="row-description">
			<?php $this->test_result_status( $result ); ?>

			<div class="row-content">

				<?php if ( in_array( $result['status'], array( 'fail', 'warning' ) ) && ! empty( $result['fix'] ) ) : ?>
				<a href="#" class="result-action"><?php esc_html_e( 'How to fix', 'rank-math' ); ?></a>
				<?php endif; ?>

				<?php echo wp_kses_post( $result['message'] ); ?>
				<?php $this->test_result_data( $result, $test_id ); ?>
				<div class="clear"></div>
				<?php if ( in_array( $result['status'], array( 'fail', 'warning' ) ) && ! empty( $result['fix'] ) ) : ?>
				<div class="how-to-fix-wrapper">
					<div class="analysis-test-how-to-fix"><?php echo $result['fix']; ?></div>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Output test data
	 *
	 * @param array  $result  Current result to output.
	 * @param string $test_id Current test ID.
	 */
	private function test_result_data( $result, $test_id ) {
		if ( ! isset( $result['data'] ) || empty( $result['data'] ) ) {
			return;
		}

		if ( 'common_keywords' === $test_id ) {
			$font_size_max = 22;
			$font_size_min = 10;

			$max = max( $result['data'] );

			echo '<div class="wp-tag-cloud">';
			foreach ( $result['data'] as $keyword => $occurrences ) {
				$size = ( $occurrences / $max ) * ( $font_size_max - $font_size_min ) + $font_size_min;
				$size = round( $size, 2 );

				printf( '<span class="keyword-cloud-item" style="font-size: %.2fpx">%s</span>', $size, htmlspecialchars( $keyword, ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8' ) );
			}
			echo '</div>';
			return;
		}

		$string = array( 'description_length' );
		if ( in_array( $test_id, $string ) ) {
			echo $result['data'];
			return;
		}

		$reverse_heading = array( 'links_ratio', 'keywords_meta', 'page_objects' );
		if ( in_array( $test_id, $reverse_heading ) ) {
			$html = '<ul class="info-list">';
			foreach ( $result['data'] as $label => $text ) {
				$text  = is_array( $text ) ? join( ', ', $text ) : $text;
				$html .= '<li><strong>' . $label . ': </strong> ' . esc_html( $text ) . '</li>';
			}
			echo $html . '</ul>';
			return;
		}

		$explode = array( 'h1_heading', 'h2_headings', 'title_length', 'canonical' );
		if ( in_array( $test_id, $explode ) ) {
			echo '<br><code>' . join( ', ', (array) $result['data'] ) . '</code>';
			return;
		}

		$list = array( 'img_alt', 'minify_css', 'minify_js', 'active_plugins' );
		if ( in_array( $test_id, $list ) ) {
			$html = '<ul class="info-list">';
			foreach ( $result['data'] as $k => $text ) {
				$text  = is_array( $text ) ? join( ', ', $text ) : $text;
				$html .= '<li>' . esc_html( ( is_string( $k ) ? $k . ' (' . $text . ')' : $text ) ) . '</li>';
			}
			echo $html . '</ul>';
			return;
		}
	}

	/**
	 * Output test result status.
	 *
	 * @param array $result Current result to output.
	 */
	private function test_result_status( $result ) {

		$status = $result['status'];
		if ( ! empty( $result['is_info'] ) ) {
			$status = 'info';
		}

		$icons = array(
			'ok'      => 'dashicons dashicons-yes',
			'fail'    => 'dashicons dashicons-no',
			'warning' => 'dashicons dashicons-warning',
			'info'    => 'dashicons dashicons-info',
		);

		$labels = array(
			'ok'      => esc_html__( 'OK', 'rank-math' ),
			'fail'    => esc_html__( 'Failed', 'rank-math' ),
			'warning' => esc_html__( 'Warning', 'rank-math' ),
			'info'    => esc_html__( 'Info', 'rank-math' ),
		);

		printf(
			'<div class="status-icon status-%1$s %3$s" title="%2$s"></div>',
			$status,
			esc_attr( $labels[ $status ] ),
			esc_attr( $icons[ $status ] )
		);
	}

	/**
	 * Check if it is a valid URL on this site.
	 *
	 * @param string $url Check url if it is allowed.
	 * @return bool
	 */
	private function is_allowed_url( $url ) {
		$home = get_home_url();
		if ( strpos( $url, $home ) !== 0 ) {
			return false;
		}

		// wp-admin pages are not allowed.
		if ( strpos( substr( $url, strlen( $home ) ), '/wp-admin' ) === 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Sort results by category.
	 *
	 * @return array
	 */
	private function sort_results_by_category() {

		$data = array();
		foreach ( $this->results as $id => $result ) {
			if ( ! isset( $data[ $result['category'] ] ) ) {
				$data[ $result['category'] ] = array();
			}
			$data[ $result['category'] ][ $id ] = $result;
		}

		return $data;
	}

	/**
	 * Get category label by slug.
	 *
	 * @param  string $category Current category slug.
	 * @return string
	 */
	private function get_category_label( $category ) {
		$category_map = array(
			'advanced'    => esc_html__( 'Advanced SEO', 'rank-math' ),
			'basic'       => esc_html__( 'Basic SEO', 'rank-math' ),
			'performance' => esc_html__( 'Performance', 'rank-math' ),
			'security'    => esc_html__( 'Security', 'rank-math' ),
			'social'      => esc_html__( 'Social SEO', 'rank-math' ),
		);

		return isset( $category_map[ $category ] ) ? $category_map[ $category ] : '';
	}

	/**
	 * Get api key for rank math api.
	 *
	 * @return string
	 */
	private function get_api_key() {
		return 'xxx-xxxx-xxxxxxxxx';
	}

	/**
	 * Get tests to exclude.
	 *
	 * @return array
	 */
	private function get_excluded_tests() {

		$exclude_tests = array(
			'active_plugins',
			'active_theme',
			'dirlist',
			'libwww_perl_access',
			'robots_txt',
			'safe_browsing',
			'xmlrpc',

			// Local tests.
			'comment_pagination',
			'site_description',
			'permalink_structure',
			'cache_plugin',
			'search_console',
			'focus_keywords',
			'post_titles',
		);

		return $exclude_tests;
	}

	/**
	 * Get tests score.
	 *
	 * @param string $test Current test ID.
	 * @return int
	 */
	private function get_test_score( $test ) {
		$score = array(
			'h1_heading'          => 5,
			'h2_headings'         => 2,
			'img_alt'             => 4,
			'keywords_meta'       => 5,
			'links_ratio'         => 3,
			'title_length'        => 3,
			'permalink_structure' => 7,
			'focus_keywords'      => 3,
			'post_titles'         => 4,
			// Advanced SEO.
			'canonical'           => 5,
			'noindex'             => 7,
			'non_www'             => 4,
			'opengraph'           => 2,
			'robots_txt'          => 3,
			'schema'              => 3,
			'sitemaps'            => 3,
			'search_console'      => 1,
			// Performance.
			'image_header'        => 3,
			'minify_css'          => 2,
			'minify_js'           => 1,
			'page_objects'        => 2,
			'page_size'           => 3,
			'response_time'       => 3,
			// Security.
			'directory_listing'   => 1,
			'safe_browsing'       => 8,
			'ssl'                 => 7,
			// Social SEO.
			'facebook_connected'  => 1,
			'instagram_connected' => 1,
			'linkedin_connected'  => 1,
			'twitter_connected'   => 1,
			'youtube_connected'   => 1,
		);

		return isset( $score[ $test ] ) ? $score[ $test ] : 0;

	}
}
