<?php
/**
 * The Search Console Errors
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Search_Console;

use RankMath\Helper;
use RankMath\Traits\Hooker;

defined( 'ABSPATH' ) || exit;

/**
 * Errors class.
 */
class Errors {

	use Hooker;

	/**
	 * Hold search console api client.
	 *
	 * @var Client
	 */
	private $client;

	/**
	 * Hold error counts.
	 *
	 * @var array
	 */
	private $counts = null;

	/**
	 * Hold platform filter.
	 *
	 * @var string
	 */
	private $platform;

	/**
	 * Hold category filter.
	 *
	 * @var string
	 */
	private $category;

	/**
	 * The Constructor.
	 *
	 * @param Client $client Clien object.
	 */
	public function __construct( $client ) {
		$this->client = $client;

		$this->platform = isset( $_GET['platform'] ) ? $_GET['platform'] : 'web';
		$this->category = isset( $_GET['category'] ) ? wp_unslash( $_GET['category'] ) : '';

		$this->action( 'admin_init', 'admin_init' );
	}

	/**
	 * Admin Initialize.
	 */
	public function admin_init() {

		if ( ! empty( $_GET['refresh_errors'] ) ) {
			check_admin_referer( 'rank_math_refresh_errors', 'security' );

			$this->get_errors( $this->platform, $this->category, true );
			Helper::add_notification( esc_html__( 'Errors list refreshed.', 'rank-math' ), [ 'type' => 'success' ] );
		}

		if ( ! empty( $_GET['maf_uri'] ) && ! empty( $_GET['maf_category'] ) ) {
			check_admin_referer( 'rank_math_error_markasfixed', 'security' );

			$category = wp_unslash( $_GET['maf_category'] );
			$response = $this->delete_error( $this->platform, $category, wp_unslash( $_GET['maf_uri'] ) );

			if ( $response ) {
				$this->get_errors( $this->platform, $category, true );
				Helper::add_notification( esc_html__( 'Marked as fixed: ', 'rank-math' ) . '<code>' . esc_html( $_GET['maf_uri'] ) . '</code>', [ 'type' => 'success' ] );
			}
		}

		$this->table = new Errors_List;
		$this->table->prepare_items();
	}

	/**
	 * Display view.
	 */
	public function display() {

		$hash = array(
			'web'            => array(
				'class' => 'rank-math-errors-web',
				'icon'  => 'fa fa-desktop',
				'title' => esc_html__( 'Desktop', 'rank-math' ),
			),
			'smartphoneOnly' => array(
				'class' => 'rank-math-errors-smartphone',
				'icon'  => 'fa fa-mobile',
				'title' => esc_html__( 'Smartphone', 'rank-math' ),
			),
		);
		?>
		<div class="rank-math-review-items rank-math-review-errors">
			<?php
			foreach ( $this->get_errors_count() as $platform => $errors ) :
				if ( empty( $errors ) ) {
					continue;
				}

				$output = '';
				$total  = 0;
				$url    = Helper::get_admin_url( 'search-console', array(
					'view'     => 'errors',
					'platform' => $platform,
				));

				foreach ( $errors as $category => $count ) {
					if ( ! empty( $count ) ) {
						$output .= '<a href="' . esc_url( $url . '&category=' . $category ) . '" class="rank-math-error-count-item">' . $this->get_error_category( $category ) . ': ' . '<span>' . $count . '</span></a>';
						$total  += $count;
					}
				}
				?>
			<div data-href="<?php echo esc_url( $url ); ?>" class="rank-math-review-item <?php echo $hash[ $platform ]['class']; ?>">

				<div class="rank-math-review-item-wrapper">

					<span class="rank-math-review-icon">
						<i class="<?php echo $hash[ $platform ]['icon']; ?>"></i>
						<i class="error-status fa fa-<?php echo empty( $output ) ? 'check-circle' : 'exclamation-circle'; ?>"></i>
					</span>

					<div class="rank-math-review-text">

						<h3><?php echo $hash[ $platform ]['title']; ?></h3>

						<div class="rank-math-review-counts">
							<?php if ( empty( $output ) ) : ?>
								<h3><?php esc_html_e( 'No crawl errors. Yay!', 'rank-math' ); ?></h3>
							<?php else : ?>
								<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html__( 'Total Errors: ', 'rank-math' ) . $total; ?></a>
								<?php echo $output; ?>
							<?php endif; ?>
						</div>

					</div>

				</div>

			</div>
			<?php endforeach; ?>
		</div>

		<h3>
			<?php
			echo 'smartphoneOnly' === $this->platform ? esc_html__( 'Crawl Errors: Smartphone', 'rank-math' ) : esc_html__( 'Crawl Errors: Desktop', 'rank-math' );
			if ( ! empty( $this->category ) ) {
				echo ' - ' . esc_html( $this->get_error_category( $this->category ) );
			}
			?>
		</h3>

		<form method="post">
		<?php
		$this->table->get_refresh_button();
		$this->table->search_box( esc_html__( 'Search', 'rank-math' ), 's' );
		$this->table->display();

		echo '</form>';
	}

	/**
	 * Get errors from api.
	 *
	 * @param  string  $platform Filter for this platform.
	 * @param  string  $filter   Filter for this category.
	 * @param  boolean $force    Purge cache and fetch new data.
	 * @return array
	 */
	public function get_errors( $platform = 'web', $filter = '', $force = false ) {

		$categories = $this->get_error_category( 'keys' );
		if ( empty( $filter ) ) {
			$filter = array();
			$counts = $this->get_errors_count( $force );
			if ( ! empty( $counts ) && isset( $this->counts[ $this->platform ] ) ) {
				foreach ( $counts[ $this->platform ] as $category => $count ) {
					if ( ! empty( $count ) ) {
						$filter[] = $category;
					}
				}
			}
		}

		$errors     = array();
		$categories = array_intersect( $categories, (array) $filter );
		foreach ( $categories as $category ) {
			$errors = array_merge( $errors, $this->get_errors_list( $platform, $category, $force ) );
		}

		return $errors;
	}

	/**
	 * Get error category title.
	 *
	 * @param  string $error Error code.
	 * @return string|array
	 */
	public function get_error_category( $error ) {

		$hash = array(
			'notFound'          => esc_html__( 'Not Found', 'rank-math' ),
			'notFollowed'       => esc_html__( 'Not Followed', 'rank-math' ),
			'authPermissions'   => esc_html__( 'Auth Permissions issue', 'rank-math' ),
			'manyToOneRedirect' => esc_html__( 'Many to One Redirection', 'rank-math' ),
			'roboted'           => esc_html__( 'Robots.txt issue', 'rank-math' ),
			'serverError'       => esc_html__( 'Server Error', 'rank-math' ),
			'soft404'           => esc_html__( 'Soft 404', 'rank-math' ),
			'other'             => esc_html__( 'Other', 'rank-math' ),
		);

		if ( 'keys' === $error ) {
			return \array_keys( $hash );
		}

		return isset( $hash[ $error ] ) ? $hash[ $error ] : $error;
	}

	/**
	 * Delete crawl errors list by platform and category.
	 *
	 * @param  string $platform Delete for this platform.
	 * @param  string $category Delete for this category.
	 * @param  string $uri      Delete for this url.
	 * @return boolean
	 */
	public function delete_error( $platform, $category, $uri ) {

		$platform = preg_replace( '/[^a-z0-9]/i', '', $platform );
		$category = preg_replace( '/[^a-z0-9]/i', '', $category );
		$response = $this->client->delete( 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode( $this->client->profile ) . '/urlCrawlErrorsSamples/' . urlencode( $uri ) . "?category=$category&platform=$platform" );

		if ( 'success' === $response['status'] && $response['code'] >= 200 && $response['code'] < 300 ) {
			return true;
		}

		$this->client->error_notice( $response );

		return false;
	}

	/**
	 * Fetch crawl errors and count them.
	 *
	 * @param  boolean $force Purge cache and fetch new data.
	 * @return array
	 */
	public function get_errors_count( $force = false ) {

		if ( $force || is_null( $this->counts ) ) {
			$key    = $this->client->generate_key( 'crawl_errors_count' );
			$errors = get_transient( $key );
			if ( $force || false === $errors ) {
				$response = $this->client->get( 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode( $this->client->profile ) . '/urlCrawlErrorsCounts/query' );

				if ( 'success' === $response['status'] ) {
					$errors = array(
						'web'            => array(),
						'mobile'         => array(),
						'smartphoneOnly' => array(),
					);

					if ( isset( $response['body']['countPerTypes'] ) && ! empty( $response['body']['countPerTypes'] ) ) {
						foreach ( $response['body']['countPerTypes'] as $error ) {
							$errors[ $error['platform'] ][ $error['category'] ] = $error['entries'][0]['count'];
						}
					}

					set_transient( $key, $errors, DAY_IN_SECONDS );
				} else {
					$this->client->error_notice( $response );
				}
			}

			$this->counts = empty( $errors ) ? array() : $errors;
		}

		return $this->counts;
	}

	/**
	 * Fetch crawl errors list by platform and category.
	 *
	 * @param  string  $platform Filter for this platform.
	 * @param  string  $category Filter for this category.
	 * @param  boolean $force    Purge cache and fetch new data.
	 * @return array
	 */
	public function get_errors_list( $platform, $category, $force = false ) {

		$key    = $this->client->generate_key( 'crawl_errors', array( $platform, $category ) );
		$errors = get_transient( $key );
		if ( $force || false === $errors ) {

			$allowed_platforms  = array( 'web', 'smartphoneOnly', 'mobile' );
			$allowed_categories = $this->get_error_category( 'keys' );

			if ( ! in_array( $platform, $allowed_platforms ) || ( ! empty( $category ) && ! in_array( $category, $allowed_categories ) ) ) {
				$this->client->error_notice(
					sprintf(
						/* translators: platform and category */
						esc_html__( 'Error: wrong platform or category: %1$s %2$s', 'rank-math' ),
						esc_html( $platform ),
						esc_html( $category )
					)
				);

				return array();
			}

			$response = $this->client->get( 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode( $this->client->profile ) . "/urlCrawlErrorsSamples?category=$category&platform=$platform" );

			if ( 'success' === $response['status'] ) {
				if ( isset( $response['body']['urlCrawlErrorSample'] ) ) {
					$errors = $response['body']['urlCrawlErrorSample'];
					foreach ( $errors as &$item ) {
						$item['category'] = $category;
					}
					set_transient( $key, $errors, DAY_IN_SECONDS );
				}
			} else {
				$this->client->error_notice( $response );
			}
		}

		return $errors ? $errors : array();
	}
}
