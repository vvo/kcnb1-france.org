<?php
/**
 * Crawl Errors List
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Search_Console;

use RankMath\Helper;
use MyThemeShop\Helpers\Str;
use MyThemeShop\Admin\List_Table;

defined( 'ABSPATH' ) || exit;

/**
 * Errors_List class.
 */
class Errors_List extends List_Table {

	/**
	 * Hold currently selected profile.
	 *
	 * @var string
	 */
	private $profile;

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
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => esc_html__( 'error', 'rank-math' ),
			'plural'   => esc_html__( 'errors', 'rank-math' ),
			'no_items' => esc_html__( 'No errors found.', 'rank-math' ),
		) );

		$this->profile  = Helper::get_settings( 'general.console_profile' );
		$this->platform = isset( $_GET['platform'] ) ? $_GET['platform'] : 'web';
		$this->category = isset( $_GET['category'] ) ? wp_unslash( $_GET['category'] ) : '';
	}

	/**
	 * Prepares the list of items for displaying.
	 */
	public function prepare_items() {
		global $per_page;
		if ( empty( $per_page ) ) {
			$per_page = 10;
		}

		$this->set_column_headers();
		$page   = $this->get_pagenum();
		$search = $this->get_search();
		$data   = Helper::search_console()->errors->get_errors( $this->platform, $this->category );

		// Filter.
		if ( ! empty( $search ) ) {
			$tmp = array();
			foreach ( $data as $item ) {
				if ( Str::contains( $search, $item['pageUrl'] ) ) {
					$tmp[] = $item;
				}
			}
			$data = $tmp;
			unset( $tmp );
		}

		// Sorting.
		usort( $data, array( $this, 'sort_data' ) );

		// Pagination.
		$count       = count( $data );
		$data        = array_slice( $data, ( $page - 1 ) * $per_page, $per_page );
		$this->items = $data;

		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
		) );
	}

	/**
	 * Sort data callback.
	 *
	 * @param  array $item_a Item A to compare.
	 * @param  array $item_b Item B to compare.
	 * @return integer
	 */
	public function sort_data( $item_a, $item_b ) {
		$order   = $this->get_order();
		$orderby = $this->get_orderby();
		$result  = strcmp( isset( $item_a[ $orderby ] ) ? $item_a[ $orderby ] : '', isset( $item_b[ $orderby ] ) ? $item_b[ $orderby ] : '' );

		return ( 'ASC' === $order ) ? $result : -$result;
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @param object $item The current item.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="pageUrls[]" value="%s" />', esc_attr( $item['pageUrl'] )
		);
	}

	/**
	 * Handles the default column output.
	 *
	 * @param object $item        The current item.
	 * @param string $column_name The current column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {

		if ( 'pageUrl' === $column_name ) {
			$out = trailingslashit( $this->profile ) . $item['pageUrl'] . $this->column_actions( $item );
			if ( isset( $item['urlDetails']['linkedFromUrls'] ) && ! empty( $item['urlDetails']['linkedFromUrls'] ) ) {
				foreach ( $item['urlDetails']['linkedFromUrls'] as &$link ) {
					$link = "<a href='{$link}' target='_blank'>{$link}</a>";
				}
				$out .= '<div class="error-linked-urls hidden"><ul><li>' . join( '</li><li>', $item['urlDetails']['linkedFromUrls'] ) . '</li></ul></div>';
			}

			return $out;
		}

		if ( 'category' === $column_name ) {
			return Helper::search_console()->errors->get_error_category( $item['category'] );
		}

		if ( in_array( $column_name, array( 'last_crawled', 'first_detected' ) ) ) {
			$date = date_parse( $item[ $column_name ] );
			return date( 'Y-m-d H:i:s', mktime( $date['hour'], $date['minute'], $date['second'], $date['month'], $date['day'], $date['year'] ) );
		}

		if ( 'responseCode' === $column_name ) {
			return ! empty( $item['responseCode'] ) ? $item['responseCode'] : '';
		}

		return $item[ $column_name ];
	}

	/**
	 * Generate row actions div.
	 *
	 * @param object $item The current item.
	 * @return string
	 */
	public function column_actions( $item ) {

		$actions = array(
			'visit'         => '<a href="' . esc_url( trailingslashit( $this->profile ) . $item['pageUrl'] ) . '" target="_blank">' . esc_html__( 'Visit Page', 'rank-math' ) . '</a>',
			'redirect'      => '<a href="' . esc_url( Helper::get_admin_url( 'redirections', array( 'url' => '/' . ltrim( $item['pageUrl'], '/' ) ) ) ) . '">' . esc_html__( 'Redirect URL', 'rank-math' ) . '</a>',
			'mark_as_fixed' => '<a href="' . esc_url( Helper::get_admin_url( 'search-console', array(
				'view'         => 'errors',
				'platform'     => $this->platform,
				'category'     => $this->category ? $this->category : false,
				'maf_uri'      => urlencode( $item['pageUrl'] ),
				'maf_category' => $item['category'],
				'security'     => wp_create_nonce( 'rank_math_error_markasfixed' ),
			) ) ) . '">' . esc_html__( 'Mark as Fixed', 'rank-math' ) . '</a>',
		);

		if ( isset( $item['urlDetails']['linkedFromUrls'] ) && ! empty( $item['urlDetails']['linkedFromUrls'] ) ) {
			$actions['view-details'] = '<a href="#">' . esc_html__( 'Linked From', 'rank-math' ) . '</a>';
		}

		if ( ! Helper::get_module( 'redirections' ) ) {
			unset( $actions['redirect'] );
		}

		return $this->row_actions( $actions );
	}

	/**
	 * Get a list of columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'pageUrl'        => esc_html__( 'URL', 'rank-math' ),
			'category'       => esc_html__( 'Type', 'rank-math' ),
			'last_crawled'   => esc_html__( 'Last Crawled', 'rank-math' ),
			'first_detected' => esc_html__( 'First Detected', 'rank-math' ),
			'responseCode'   => esc_html__( 'Response Code', 'rank-math' ),
		);
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'pageUrl'        => array( 'pageUrl', true ),
			'category'       => array( 'category', true ),
			'last_crawled'   => array( 'last_crawled', true ),
			'first_detected' => array( 'first_detected', true ),
			'responseCode'   => array( 'responseCode', true ),
		);
	}

	/**
	 * Get a refresh button to fetch the error list from google.
	 */
	public function get_refresh_button() {
		$url = Helper::get_admin_url( 'search-console', array(
			'view'           => 'errors',
			'platform'       => $this->platform,
			'category'       => $this->category ? $this->category : false,
			'refresh_errors' => '1',
			'security'       => wp_create_nonce( 'rank_math_refresh_errors' ),
		) );

		?>
		<div class="alignleft actions">
			<a href="<?php echo esc_url( $url ); ?>" class="button button-secondary"><?php esc_html_e( 'Refresh List', 'rank-math' ); ?></a>
		</div>
		<?php
	}
}
