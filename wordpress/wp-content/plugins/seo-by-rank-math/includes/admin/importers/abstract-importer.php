<?php
/**
 * The abstract class for plugins import to inherit from
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Admin\Import
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Admin\Importers;

use Exception;
use RankMath\Helper;
use RankMath\Traits\Ajax;
use RankMath\Traits\Hooker;
use RankMath\Admin\Admin_Helper;
use MyThemeShop\Helpers\DB;
use MyThemeShop\Helpers\Attachment;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin_Importer class.
 */
abstract class Plugin_Importer {

	use Hooker, Ajax;

	/**
	 * The plugin name
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * The plugin file
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * Meta key, used in SQL LIKE clause for delete query
	 *
	 * @var string
	 */
	protected $meta_key;

	/**
	 * Array of option keys to import and clean
	 *
	 * @var array
	 */
	protected $option_keys;

	/**
	 * Array of table names to drop while cleaning
	 *
	 * @var array
	 */
	protected $table_names;

	/**
	 * Array of choices keys to import
	 *
	 * @var array
	 */
	protected $choices;

	/**
	 * Items to parse for post/term/user meta.
	 *
	 * @var int
	 */
	protected $items_per_page = 100;

	/**
	 * Pagination arguments.
	 *
	 * @var array
	 */
	protected $_pagination_args = array();

	/**
	 * Plugin slug for internal  use.
	 *
	 * @var string
	 */
	protected $plugin_slug = '';

	/**
	 * Class constructor
	 *
	 * @param string $plugin_file Plugins file.
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_slug = \strtolower( get_class( $this ) );
		$this->plugin_slug = \str_replace( 'rankmath\\admin\\importers\\', '', $this->plugin_slug );
		$this->plugin_file = $plugin_file;
	}

	/**
	 * Returns the string for the plugin we're importing from
	 *
	 * @return string Plugin name
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Returns the string for the plugin file
	 *
	 * @return string Plugin file
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * Returns array of choices of action which can be performed for plugin
	 *
	 * @return array
	 */
	public function get_choices() {
		if ( empty( $this->choices ) ) {
			return array();
		}

		$hash = array(
			'settings'     => esc_html__( 'Import Settings', 'rank-math' ) . Admin_Helper::get_tooltip( esc_html__( 'Import Yoast plugin settings, global meta, sitemap settings, etc.', 'rank-math' ) ),
			'postmeta'     => esc_html__( 'Import Post Meta', 'rank-math' ) . Admin_Helper::get_tooltip( esc_html__( 'Import meta information of your posts/pages like the focus keyword, titles, descriptions, robots meta, OpenGraph info, etc.', 'rank-math' ) ),
			'termmeta'     => esc_html__( 'Import Term Meta', 'rank-math' ) . Admin_Helper::get_tooltip( esc_html__( 'Import data like category, tag, and CPT meta data from Yoast SEO.', 'rank-math' ) ),
			'usermeta'     => esc_html__( 'Import Author Meta', 'rank-math' ) . Admin_Helper::get_tooltip( esc_html__( 'Import meta information like titles, descriptions, focus keyword, robots meta, etc., of your author archive pages.', 'rank-math' ) ),
			'redirections' => esc_html__( 'Import Redirections', 'rank-math' ) . Admin_Helper::get_tooltip( esc_html__( 'Import all the redirections you have already set up in Yoast.', 'rank-math' ) ),
		);

		return \array_intersect_key( $hash, \array_combine( $this->choices, $this->choices ) );
	}

	/**
	 * Detects whether an import for this plugin is needed
	 *
	 * @return bool Indicating whether there is something to import
	 */
	public function run_detect() {
		if ( true === $this->has_options() ) {
			return true;
		}

		$result = 0;
		if ( ! empty( $this->meta_key ) ) {
			$result = DB::query_builder( 'postmeta' )->selectCount( '*', 'count' )->whereLike( 'meta_key', $this->meta_key )->getVar();
		}
		return 0 === absint( $result ) ? false : true;
	}

	/**
	 * Removes the plugin data from the database.
	 *
	 * @return bool
	 */
	public function run_cleanup() {
		if ( ! $this->run_detect() ) {
			return false;
		}

		return $this->cleanup();
	}

	/**
	 * Removes the plugin data from the database.
	 *
	 * @return bool Cleanup status.
	 */
	public function cleanup() {
		global $wpdb;
		$result = false;

		if ( ! empty( $this->meta_key ) ) {
			$result = DB::query_builder( 'postmeta' )->whereLike( 'meta_key', $this->meta_key )->delete();
			$result = DB::query_builder( 'termmeta' )->whereLike( 'meta_key', $this->meta_key )->delete();
			$result = DB::query_builder( 'usermeta' )->whereLike( 'meta_key', $this->meta_key )->delete();
		}

		if ( ! empty( $this->option_keys ) ) {
			$table = DB::query_builder( 'options' );
			foreach ( $this->option_keys as $option_key ) {
				$table->orWhere( 'option_name', $option_key );
			}

			$result = $table->delete();
		}

		if ( ! empty( $this->table_names ) ) {
			foreach ( $this->table_names as $table ) {
				$wpdb->query( "DROP TABLE {$wpdb->prefix}{$table}" ); // phpcs:ignore
			}
		}

		return $result;
	}

	/**
	 * Run importer routines
	 *
	 * @throws Exception Throw error if no perform function founds.
	 *
	 * @param string $perform The action to perform when running import action.
	 */
	public function run_import( $perform ) {

		if ( ! method_exists( $this, $perform ) ) {
			throw new Exception( esc_html__( 'Unable to perform action this time.', 'rank-math' ) );
		}

		/**
		 * Number of items to import per run.
		 *
		 * @param int $items_per_page Default 100.
		 */
		$this->items_per_page = absint( $this->do_filter( 'importers/items_per_page', 100 ) );

		$hash_ok = array(
			'settings'     => esc_html__( 'Settings imported successfully.', 'rank-math' ),
			'deactivate'   => esc_html__( 'Plugin deactivated successfully.', 'rank-math' ),
			/* translators: start, end, total */
			'postmeta'     => esc_html__( 'Imported post meta for posts %1$s - %2$s out of %3$s ', 'rank-math' ),
			/* translators: total */
			'termmeta'     => esc_html__( 'Imported term meta for %s terms.', 'rank-math' ),
			/* translators: start, end, total */
			'usermeta'     => esc_html__( 'Imported user meta for users %1$s - %2$s out of %3$s ', 'rank-math' ),
			/* translators: total */
			'redirections' => esc_html__( 'Imported %s redirections.', 'rank-math' ),
		);

		$hash_failed = array(
			'settings'     => esc_html__( 'Settings import failed.', 'rank-math' ),
			'postmeta'     => esc_html__( 'Posts meta import failed.', 'rank-math' ),
			'termmeta'     => esc_html__( 'Term meta import failed.', 'rank-math' ),
			'usermeta'     => esc_html__( 'User meta import failed.', 'rank-math' ),
			'redirections' => esc_html__( 'There are no redirection to import.', 'rank-math' ),
		);

		$result = $this->$perform();
		if ( is_array( $result ) ) {
			$message = $hash_ok[ $perform ];
			if ( 'postmeta' === $perform || 'usermeta' === $perform ) {
				$result['message'] = sprintf( $message, $result['start'], $result['end'], $result['total_items'] );
			} elseif ( 'termmeta' === $perform || 'redirections' === $perform ) {
				$result['message'] = sprintf( $message, $result['count'] );
			}
			$this->success( $result );
		}

		if ( true === $result ) {
			$this->success( $hash_ok[ $perform ] );
		}

		$this->error( $hash_failed[ $perform ] );
	}

	/**
	 * Deactivate plugin action.
	 */
	protected function deactivate() {
		if ( is_plugin_active( $this->get_plugin_file() ) ) {
			deactivate_plugins( $this->get_plugin_file() );
		}

		return true;
	}

	/**
	 * Replce settings based on key/value hash.
	 *
	 * @param array $hash        Array of hash for search and replace.
	 * @param array $source      Array for source where to search.
	 * @param array $destination Array for destination where to save.
	 * @param bool  $convert     (Optional) Conversion type. Default: false.
	 */
	protected function replace( $hash, $source, &$destination, $convert = false ) {
		foreach ( $hash as $search => $replace ) {
			if ( ! isset( $source[ $search ] ) ) {
				continue;
			}

			$destination[ $replace ] = false === $convert ? $source[ $search ] : $this->$convert( $source[ $search ] );
		}
	}

	/**
	 * Replce meta based on key/value hash.
	 *
	 * @param array  $hash        Array of hash for search and replace.
	 * @param array  $source      Array for source where to search.
	 * @param int    $object_id   Object id for destination where to save.
	 * @param string $object_type Object type for destination where to save.
	 * @param bool   $convert     (Optional) Conversion type. Default: false.
	 */
	protected function replace_meta( $hash, $source, $object_id, $object_type, $convert = false ) {
		$get  = "get_{$object_type}_meta";
		$func = "update_{$object_type}_meta";

		foreach ( $hash as $search => $replace ) {
			$value = ! empty( $source[ $search ] ) ? $source[ $search ] : $get( $object_id, $search, true );
			if ( ! empty( $value ) ) {
				$value = false === $convert ? $value : $this->$convert( $value );
				$func( $object_id, $replace, $value );
			}
		}
	}

	/**
	 * Replace and image to its url and id.
	 *
	 * @param string         $source      Source image url.
	 * @param array|callable $destination Destination array.
	 * @param string         $image       Image field key to save url.
	 * @param string         $image_id    Image id field key to save id.
	 * @param int            $object_id   Object ID either post id, term id or user id.
	 */
	protected function replace_image( $source, &$destination, $image, $image_id, $object_id = null ) {
		$attachment_id = Attachment::get_by_url( $source );
		if ( 1 > $attachment_id ) {
			return;
		}

		if ( is_null( $object_id ) ) {
			$destination[ $image ]    = $source;
			$destination[ $image_id ] = $attachment_id;
			return;
		}

		$destination( $object_id, $image, $source );
		$destination( $object_id, $image_id, $attachment_id );
	}

	/**
	 * Convert bool value to switch.
	 *
	 * @param mixed $value Value to convert.
	 * @return string
	 */
	protected function convert_bool( $value ) {
		if ( true === $value || 'true' === $value || '1' === $value || 1 === $value ) {
			return 'on';
		}
		if ( false === $value || 'false' === $value || '0' === $value || 0 === $value ) {
			return 'off';
		}

		return $value;
	}

	/**
	 * Convert Yoast / AIO SEO variables if needed.
	 *
	 * @param string $string Value to convert.
	 * @return string
	 */
	public function convert_variables( $string ) {

		// Yoast:
		// Convert %%cf_<custom-field-name>%% to %%customfield(<custom-field-name>)%%.
		// & %%ct_<custom-tax-name>%% to.
		// %%ct_desc_<custom-tax-name>%%.
		if ( 'yoast' === $this->plugin_slug ) {
			$string = str_replace( '%%term_title%%', '%term%', $string );
			$string = preg_replace( '/%%cf_([^%]+)%%/i', '%customfield($1)%', $string );
			$string = preg_replace( '/%%ct_([^%]+)%%/i', '%ct($1)%', $string );
			$string = preg_replace( '/%%ct_desc_([^%]+)%%/i', '%ct_desc($1)%', $string );
		} elseif ( 'aioseo' === $this->plugin_slug ) {
			$string = str_replace( '%blog_title%', '%sitename%', $string );
			$string = str_replace( '%blog_description%', '%sitedesc%', $string );
			$string = str_replace( '%post_title%', '%title%', $string );
			$string = str_replace( '%page_title%', '%title%', $string );
			$string = str_replace( '%category_title%', '%category%', $string );
			$string = str_replace( '%category_description%', '%term_description%', $string );
			$string = str_replace( '%archive_title%', '%term%', $string );
			$string = str_replace( '%category%', '%category%', $string );
			$string = str_replace( '%post_author_login%', '%name%', $string );
			$string = str_replace( '%post_author_login%', '%name%', $string );
			$string = str_replace( '%post_author_nicename%', '%name%', $string );
			$string = str_replace( '%post_author_firstname%', '%name%', $string );
			$string = str_replace( '%post_author_lastname%', '%name%', $string );
			$string = str_replace( '%current_date%', '%currentdate%', $string );
			$string = str_replace( '%post_date%', '%date%', $string );
			$string = str_replace( '%post_year%', '%date(Y)%', $string );
			$string = str_replace( '%post_month%', '%date(M)%', $string );
			$string = str_replace( '%page_author_login%', '%name%', $string );
			$string = str_replace( '%page_author_login%', '%name%', $string );
			$string = str_replace( '%page_author_nicename%', '%name%', $string );
			$string = str_replace( '%page_author_firstname%', '%name%', $string );
			$string = str_replace( '%page_author_lastname%', '%name%', $string );
			$string = str_replace( '%author%', '%name%', $string );
			$string = str_replace( '%search%', '%search_query%', $string );
			$string = str_replace( '%search%', '%search_query%', $string );
		}

		return str_replace( '%%', '%', $string );
	}

	/**
	 * Set pagination arguments.
	 *
	 * @param int $total_items Number of total items to set pagination.
	 */
	protected function set_pagination( $total_items = 0 ) {
		$args = array(
			'total_pages' => 0,
			'total_items' => $total_items,
			'per_page'    => $this->items_per_page,
		);

		// Total Pages.
		if ( ! $args['total_pages'] && $args['per_page'] > 0 ) {
			$args['total_pages'] = ceil( $args['total_items'] / $args['per_page'] );
		}

		// Current Page.
		$pagenum = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0;
		if ( isset( $args['total_pages'] ) && $pagenum > $args['total_pages'] ) {
			$pagenum = $args['total_pages'];
		}
		$args['page'] = max( 1, $pagenum );

		// Start n End.
		$args['start'] = ( ( $args['page'] - 1 ) * $this->items_per_page ) + 1;
		$args['end']   = min( $args['page'] * $this->items_per_page, $total_items );

		$this->_pagination_args = $args;
	}

	/**
	 * Get pagination arguments.
	 *
	 * @param bool $key If any specific data is required from arguments.
	 * @return mixed
	 */
	protected function get_pagination_arg( $key = false ) {
		if ( false === $key ) {
			return $this->_pagination_args;
		}

		return isset( $this->_pagination_args[ $key ] ) ? $this->_pagination_args[ $key ] : false;
	}

	/**
	 * Get all post ids of all allowed post types only.
	 *
	 * @param bool $count If we need count only for pagination purposes.
	 * @return int|array
	 */
	protected function get_post_ids( $count = false ) {

		$paged = $this->get_pagination_arg( 'page' );

		if ( 'aio_rich_snippet' === $this->plugin_slug ) {
			$table = DB::query_builder( 'postmeta' );
			$table->where( 'meta_key', '_bsf_post_type' );

			return $count ? absint( $table->selectCount( 'meta_id' )->getVar() ) :
			$table->page( $paged - 1, $this->items_per_page )->get();
		}

		$table = DB::query_builder( 'posts' );
		$table->whereIn( 'post_type', Helper::get_accessible_post_types() );

		return $count ? absint( $table->selectCount( 'ID', 'total' )->getVar() ) :
			$table->select( 'ID' )->page( $paged - 1, $this->items_per_page )->get();
	}

	/**
	 * Get all user ids.
	 *
	 * @param bool $count If we need count only for pagination purposes.
	 * @return int|array
	 */
	protected function get_user_ids( $count = false ) {
		$paged = $this->get_pagination_arg( 'page' );
		$table = DB::query_builder( 'users' );

		return $count ? absint( $table->selectCount( 'ID', 'total' )->getVar() ) :
			$table->select( 'ID' )->page( $paged - 1, $this->items_per_page )->get();
	}

	/**
	 * Has options.
	 *
	 * @return bool
	 */
	private function has_options() {
		if ( empty( $this->option_keys ) ) {
			return false;
		}

		$table = DB::query_builder( 'options' )->selectCount( '*', 'count' );
		foreach ( $this->option_keys as $option_key ) {
			$table->orWhere( 'option_name', $option_key );
		}

		return ( absint( $table->getVar() ) > 0 ) ? true : false;
	}
}
