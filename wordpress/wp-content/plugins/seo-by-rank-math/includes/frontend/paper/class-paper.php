<?php
/**
 * The Paper Class
 *
 * @since      1.0.22
 * @package    RankMath
 * @subpackage RankMath\Paper
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Paper;

use RankMath\Post;
use RankMath\Helper;
use RankMath\Sitemap\Router;
use MyThemeShop\Helpers\Str;
use MyThemeShop\Helpers\Url;

defined( 'ABSPATH' ) || exit;

/**
 * Paper class.
 */
class Paper {

	/**
	 * Hold the class instance.
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Hold current paper object.
	 *
	 * @var IPaper
	 */
	private $paper = null;

	/**
	 * Hold title.
	 *
	 * @var string
	 */
	private $title = null;

	/**
	 * Hold description.
	 *
	 * @var string
	 */
	private $description = null;

	/**
	 * Hold robots.
	 *
	 * @var array
	 */
	private $robots = null;

	/**
	 * Hold canonical.
	 *
	 * @var array
	 */
	private $canonical = null;

	/**
	 * Initialize object
	 *
	 * @return object Post|Term|User.
	 */
	public static function get() {

		if ( ! is_null( self::$instance ) ) {
			return self::$instance;
		}

		self::$instance = new Paper;
		self::$instance->setup();
		return self::$instance;
	}

	/**
	 * Setup paper.
	 */
	private function setup() {
		$hash = [
			'Search'    => is_search(),
			'Shop'      => Post::is_shop_page(),
			'Singular'  => Post::is_home_static_page() || Post::is_simple_page(),
			'Blog'      => Post::is_home_posts_page(),
			'Author'    => is_author() || ( Helper::is_module_active( 'bbpress' ) && bbp_is_single_user() ),
			'Date'      => is_date(),
			'Taxonomy'  => is_category() || is_tag() || is_tax(),
			'Archive'   => is_archive(),
			'Error_404' => true,
		];

		foreach ( $hash as $class_name => $is_valid ) {
			if ( $is_valid ) {
				$class_name  = '\\RankMath\\Paper\\' . $class_name;
				$this->paper = new $class_name;
				break;
			}
		}

		if ( Post::is_home_static_page() ) {
			$this->paper->set_object( get_queried_object() );
		} elseif ( Post::is_simple_page() ) {
			$post = Post::get( Post::get_simple_page_id() );
			$this->paper->set_object( $post->get_object() );
		}
	}

	/**
	 * Get title after sanitization.
	 *
	 * @return string
	 */
	public function get_title() {
		if ( ! is_null( $this->title ) ) {
			return $this->title;
		}

		/**
		 * Allow changing the title.
		 *
		 * @param string $title The page title being put out.
		 */
		$this->title = apply_filters( 'rank_math/frontend/title', $this->paper->title() );

		// Early Bail!!
		if ( '' === $this->title ) {
			return $this->title;
		}

		// Remove excess whitespace.
		$this->title = preg_replace( '[\s\s+]', ' ', $this->title );

		// Capitalize Titles.
		if ( Helper::get_settings( 'titles.capitalize_titles' ) ) {
			$this->title = ucwords( $this->title );
		}

		$this->title = wp_strip_all_tags( stripslashes( $this->title ), true );
		$this->title = esc_html( $this->title );
		$this->title = convert_smilies( $this->title );

		return $this->title;
	}

	/**
	 * Get description after sanitization.
	 *
	 * @return string
	 */
	public function get_description() {
		if ( ! is_null( $this->description ) ) {
			return $this->description;
		}

		/**
		* Allow changing the meta description sentence.
		*
		* @param string $description The description sentence.
		*/
		$this->description = apply_filters( 'rank_math/frontend/description', trim( $this->paper->description() ) );

		// Early Bail!!
		if ( '' === $this->description ) {
			return $this->description;
		}

		$this->description = wp_strip_all_tags( stripslashes( $this->description ), true );
		$this->description = esc_html( $this->description );

		return $this->description;
	}

	/**
	 * Get robots after sanitization.
	 *
	 * @return array
	 */
	public function get_robots() {
		if ( ! is_null( $this->robots ) ) {
			return $this->robots;
		}

		$this->robots = $this->paper->robots();
		if ( empty( $this->robots ) ) {
			$this->get_global_robots();
		}

		// Add Index and Follow.
		if ( ! isset( $this->robots['index'] ) ) {
			$this->robots = [ 'index' => 'index' ] + $this->robots;
		}
		if ( ! isset( $this->robots['follow'] ) ) {
			$this->robots = [ 'follow' => 'follow' ] + $this->robots;
		}

		$this->respect_settings_for_robots();

		/**
		 * Allows filtering of the meta robots.
		 *
		 * @param array $robots The meta robots directives to be echoed.
		 */
		$this->robots = apply_filters( 'rank_math/frontend/robots', array_unique( $this->robots ) );

		return $this->robots;
	}

	/**
	 * Respect some robots settings.
	 */
	private function respect_settings_for_robots() {
		// Force override to respect the WP settings.
		if ( 0 === absint( get_option( 'blog_public' ) ) || isset( $_GET['replytocom'] ) ) {
			$this->robots['index'] = 'noindex';
		}

		// Noindex for sub-pages.
		if ( is_paged() && Helper::get_settings( 'titles.noindex_archive_subpages' ) ) {
			$this->robots['index'] = 'noindex';
		}
	}

	/**
	 * Get global robots
	 */
	private function get_global_robots() {
		$this->robots = self::robots_combine( Helper::get_settings( 'titles.robots_global' ) );
		if ( empty( $this->robots ) ) {
			$this->robots = [
				'index'  => 'index',
				'follow' => 'follow',
			];
		}
	}

	/**
	 * Get canonical after sanitization.
	 *
	 * @param bool $un_paged    Whether or not to return the canonical with or without pagination added to the URL.
	 * @param bool $no_override Whether or not to return a manually overridden canonical.
	 *
	 * @return string
	 */
	public function get_canonical( $un_paged = false, $no_override = false ) {
		if ( is_null( $this->canonical ) ) {
			$this->generate_canonical();
		}

		$canonical = $this->canonical['canonical'];
		if ( $un_paged ) {
			$canonical = $this->canonical['canonical_unpaged'];
		} elseif ( $no_override ) {
			$canonical = $this->canonical['canonical_no_override'];
		}

		return $canonical;
	}

	/**
	 * Generate canonical URL parts.
	 */
	private function generate_canonical() {
		$this->canonical = wp_parse_args(
			$this->paper->canonical(),
			[
				'canonical'          => false,
				'canonical_unpaged'  => false,
				'canonical_override' => false,
			]
		);
		extract( $this->canonical ); // phpcs:ignore

		if ( is_front_page() || ( function_exists( 'ampforwp_is_front_page' ) && ampforwp_is_front_page() ) ) {
			$canonical = home_url();
		}

		// If not singular than we can have pagination.
		if ( ! is_singular() ) {
			$canonical_unpaged = $canonical;
			$canonical         = $this->get_canonical_paged( $canonical );
		}

		$this->canonical['canonical_unpaged']     = $canonical_unpaged;
		$this->canonical['canonical_no_override'] = $canonical;

		// Force canonical links to be absolute, relative is NOT an option.
		$canonical = Str::is_non_empty( $canonical ) && true === Url::is_relative( $canonical ) ? $this->base_url( $canonical ) : $canonical;
		$canonical = Str::is_non_empty( $canonical_override ) ? $canonical_override : $canonical;

		/**
		 * Allow filtering of the canonical URL.
		 *
		 * @param string $canonical The canonical URL.
		 */
		$this->canonical['canonical'] = apply_filters( 'rank_math/frontend/canonical', $canonical );
	}

	/**
	 * Get canonical paged
	 *
	 * @param string $canonical Canonical URL.
	 *
	 * @return string
	 */
	private function get_canonical_paged( $canonical ) {
		global $wp_rewrite;

		if ( ! $canonical || get_query_var( 'paged' ) < 2 ) {
			return $canonical;
		}

		if ( ! $wp_rewrite->using_permalinks() ) {
			return add_query_arg(
				'paged',
				get_query_var( 'paged' ),
				is_front_page() ? trailingslashit( $canonical ) : $canonical
			);
		}

		return user_trailingslashit(
			trailingslashit( is_front_page() ? Router::get_base_url( '' ) : $canonical ) .
			trailingslashit( $wp_rewrite->pagination_base ) .
			get_query_var( 'paged' )
		);
	}

	/**
	 * Parse the home URL setting to find the base URL for relative URLs.
	 *
	 * @param  string $path Optional path string.
	 * @return string
	 */
	private function base_url( $path = null ) {
		$parts    = wp_parse_url( get_option( 'home' ) );
		$base_url = trailingslashit( $parts['scheme'] . '://' . $parts['host'] );

		if ( ! is_null( $path ) ) {
			$base_url .= ltrim( $path, '/' );
		}

		return $base_url;
	}

	/**
	 * Simple function to use to pull data from $options.
	 *
	 * All titles pulled from options will be run through the Helper::replace_vars function.
	 *
	 * @param string       $id      Name of the page to get the title from the settings for.
	 * @param object|array $source  Possible object to pull variables from.
	 * @param string       $default Default value if nothing found.
	 *
	 * @return string
	 */
	public static function get_from_options( $id, $source = [], $default = '' ) {
		$value = Helper::get_settings( "titles.$id" );
		return '' !== $value ? Helper::replace_vars( $value, $source ) : $default;
	}

	/**
	 * Make robots values as keyed array.
	 *
	 * @param array $robots Main instance.
	 *
	 * @return array
	 */
	public static function robots_combine( $robots ) {
		if ( empty( $robots ) || ! is_array( $robots ) ) {
			return [];
		}

		$robots = array_combine( $robots, $robots );

		// Fix noindex key to index.
		if ( isset( $robots['noindex'] ) ) {
			$robots = [ 'index' => $robots['noindex'] ] + $robots;
			unset( $robots['noindex'] );
		}

		// Fix nofollow key to follow.
		if ( isset( $robots['nofollow'] ) ) {
			$robots = [ 'follow' => $robots['nofollow'] ] + $robots;
			unset( $robots['nofollow'] );
		}

		return $robots;
	}
}
