<?php
/**
 * The generate seo meta tag values.
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Frontend
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Frontend;

use RankMath\Post;
use RankMath\Term;
use RankMath\User;
use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Sitemap\Router;
use MyThemeShop\Helpers\Url;
use MyThemeShop\Helpers\WordPress;

defined( 'ABSPATH' ) || exit;

/**
 * Generate class.
 */
class Generate {

	use Hooker;

	/**
	 * Hold generated parts.
	 *
	 * @var array
	 */
	private $parts = null;

	/**
	 * Get part by id
	 *
	 * @param  string $id Part id to get.
	 * @return mixed
	 */
	public function get( $id ) {
		if ( is_null( $this->parts ) ) {
			$this->generate();
		}

		return isset( $this->parts[ $id ] ) ? $this->parts[ $id ] : '';
	}

	/**
	 * Generates a title, description, based on current query, without additions or prefixes.
	 */
	public function generate() {

		if ( ! is_null( $this->parts ) ) {
			return $this->parts;
		}

		$parts = array();

		if ( is_404() ) {
			$parts = $this->get_404_parts();
		} elseif ( is_search() ) {
			$parts = $this->get_search_parts();
		} elseif ( Post::is_shop_page() ) {
			$parts = $this->get_shop_parts();
		} elseif ( Post::is_home_static_page() ) {
			$parts = $this->get_single_parts();
		} elseif ( Post::is_home_posts_page() ) {
			$parts = $this->get_home_posts_page_parts();
		} elseif ( Post::is_simple_page() ) {
			$post  = Post::get( Post::get_simple_page_id() );
			$parts = $this->get_single_parts( $post->get_object() );
		} elseif ( is_archive() || ( function_exists( 'bbp_is_topic_tag' ) && bbp_is_topic_tag() ) ) {
			$parts = $this->get_archive_parts();
		}

		// Sanitize.
		$parts['title']  = isset( $parts['title'] ) ? $this->sanitize_title( $parts['title'] ) : '';
		$parts['desc']   = isset( $parts['desc'] ) ? $this->sanitize_description( $parts['desc'] ) : '';
		$parts['robots'] = $this->sanitize_robots( isset( $parts['robots'] ) ? $parts['robots'] : array() );

		$this->parts = $parts;
		return $parts;
	}

	/**
	 * This function normally outputs the canonical but is also used in other places to retrieve
	 * the canonical URL for the current page.
	 */
	public function generate_canonical() {
		global $wp_rewrite;

		$canonical          = false;
		$canonical_unpaged  = false;
		$canonical_override = false;

		if ( is_search() ) {
			$search_query = get_search_query();
			// Regex catches case when /search/page/N without search term is itself mistaken for search term.
			if ( ! empty( $search_query ) && ! preg_match( '|^page/\d+$|', $search_query ) ) {
				$canonical = get_search_link();
			}
		} elseif ( Post::is_simple_page() ) {
			$obj                = get_queried_object();
			$object_id          = Post::get_simple_page_id();
			$canonical          = get_permalink( $object_id );
			$canonical_unpaged  = $canonical;
			$canonical_override = Post::get_meta( 'canonical_url', $object_id );

			// Fix paginated pages canonical, but only if the page is truly paginated.
			if ( get_query_var( 'page' ) > 1 ) {
				$num_pages = ( substr_count( $obj->post_content, '<!--nextpage-->' ) + 1 );
				if ( $num_pages && get_query_var( 'page' ) <= $num_pages ) {
					$canonical = ! $wp_rewrite->using_permalinks() ? add_query_arg( 'page', get_query_var( 'page' ), $canonical ) :
						user_trailingslashit( trailingslashit( $canonical ) . get_query_var( 'page' ) );
				}
			}
		} elseif ( is_front_page() || ( function_exists( 'ampforwp_is_front_page' ) && ampforwp_is_front_page() ) ) {
			$canonical = home_url();
		} elseif ( Post::is_posts_page() ) {
			$posts_page_id = get_option( 'page_for_posts' );
			$canonical     = Post::get_meta( 'canonical_url', $posts_page_id );
			if ( empty( $canonical ) ) {
				$canonical = get_permalink( $posts_page_id );
			}
		} elseif ( is_archive() ) {

			if ( is_category() || is_tag() || is_tax() ) {
				$term = get_queried_object();
				if ( ! empty( $term ) && ! Term::is_multiple_terms_query() ) {
					$canonical_override = Term::get_meta( 'canonical_url', $term, $term->taxonomy );
					$term_link          = get_term_link( $term, $term->taxonomy );
					if ( ! is_wp_error( $term_link ) ) {
						$canonical = $term_link;
					}
				}
			} elseif ( is_post_type_archive() ) {
				$post_type = $this->get_queried_post_type();
				$canonical = get_post_type_archive_link( $post_type );
			} elseif ( is_author() ) {
				$canonical          = get_author_posts_url( get_query_var( 'author' ), get_query_var( 'author_name' ) );
				$canonical_override = User::get_meta( 'canonical_url', get_query_var( 'author' ) );
			} elseif ( is_date() ) {
				if ( is_day() ) {
					$canonical = get_day_link( get_query_var( 'year' ), get_query_var( 'monthnum' ), get_query_var( 'day' ) );
				} elseif ( is_month() ) {
					$canonical = get_month_link( get_query_var( 'year' ), get_query_var( 'monthnum' ) );
				} elseif ( is_year() ) {
					$canonical = get_year_link( get_query_var( 'year' ) );
				}
			}
		}

		// If not singular than we can have pagination.
		if ( ! is_singular() ) {
			$canonical_unpaged = $canonical;
			if ( $canonical && get_query_var( 'paged' ) > 1 ) {
				if ( ! $wp_rewrite->using_permalinks() ) {
					if ( is_front_page() ) {
						$canonical = trailingslashit( $canonical );
					}
					$canonical = add_query_arg( 'paged', get_query_var( 'paged' ), $canonical );
				} else {
					if ( is_front_page() ) {
						$canonical = Router::get_base_url( '' );
					}
					$canonical = user_trailingslashit( trailingslashit( $canonical ) . trailingslashit( $wp_rewrite->pagination_base ) . get_query_var( 'paged' ) );
				}
			}
		}

		$this->parts['canonical_unpaged']     = $canonical_unpaged;
		$this->parts['canonical_no_override'] = $canonical;

		if ( is_string( $canonical ) && '' !== $canonical ) {
			// Force canonical links to be absolute, relative is NOT an option.
			if ( true === Url::is_relative( $canonical ) ) {
				$canonical = $this->base_url( $canonical );
			}
		}

		if ( is_string( $canonical_override ) && '' !== $canonical_override ) {
			$canonical = $canonical_override;
		}

		/**
		 * Allow filtering of the canonical URL.
		 *
		 * @param string $canonical The canonical URL.
		 */
		$this->parts['canonical'] = $this->do_filter( 'frontend/canonical', $canonical );
	}

	/**
	 * Returns 404 parts
	 *
	 * @return array The generated parts.
	 */
	private function get_404_parts() {
		$parts['title']  = $this->get_from_options( '404_title', array(), esc_html__( 'Page not found', 'rank-math' ) );
		$parts['robots'] = array( 'index' => 'noindex' );

		return $parts;
	}

	/**
	 * Returns search parts
	 *
	 * @return array The generated parts.
	 */
	private function get_search_parts() {
		$parts['title'] = $this->get_from_options( 'search_title', array(), 'Searched for %searchphrase% %page% %sep% %sitename%' );
		if ( Helper::get_settings( 'titles.noindex_search' ) ) {
			$parts['robots'] = array( 'index' => 'noindex' );
		}

		return $parts;
	}

	/**
	 * Returns home posts page parts
	 *
	 * @return array The generated parts.
	 */
	private function get_home_posts_page_parts() {
		$parts['title']    = $this->get_from_options( 'homepage_title' );
		$parts['desc']     = $this->get_from_options( 'homepage_description', array(), get_bloginfo( 'description' ) );
		$no_index_subpages = is_paged() && Helper::get_settings( 'titles.noindex_paginated_pages' );

		$robots = array();
		if ( Helper::get_settings( 'titles.homepage_custom_robots' ) ) {
			$this->robots_merge( $robots, Helper::get_settings( 'titles.homepage_robots' ) );
		}
		if ( $no_index_subpages ) {
			$robots['index'] = 'noindex';
		}

		$parts['robots'] = $robots;
		return $parts;
	}

	/**
	 * Returns static home and posts pages as well as singular parts
	 *
	 * @param  object|null $object If filled, object to get the title for.
	 * @return array The generated parts.
	 */
	private function get_single_parts( $object = null ) {
		if ( is_null( $object ) ) {
			$object = $GLOBALS['wp_query']->get_queried_object();
		}

		$parts['title']  = $this->get_post_title( $object );
		$parts['desc']   = $this->get_post_description( $object );
		$parts['robots'] = $this->get_post_robots( $object );

		return $parts;
	}

	/**
	 * Returns woocommerce shop page parts
	 *
	 * @return array The generated parts.
	 */
	private function get_shop_parts() {
		$post  = Post::get( Post::get_shop_page_id() );
		$title = $this->get_post_title( $post->get_object() );

		$parts['title']  = ( ! is_string( $title ) || '' === $title ) ? $this->get_post_type_archive_title() : $title;
		$parts['desc']   = $this->get_post_description( $post->get_object() );
		$parts['robots'] = $this->get_post_robots( $post->get_object() );

		return $parts;
	}

	/**
	 * Returns archive pages parts
	 *
	 * @return array The generated parts.
	 */
	private function get_archive_parts() {
		if ( is_category() || is_tag() || is_tax() || ( function_exists( 'bbp_is_topic_tag' ) && bbp_is_topic_tag() ) ) {
			$object = $GLOBALS['wp_query']->get_queried_object();

			$parts['title']  = $this->get_taxonomy_title( $object );
			$parts['desc']   = $this->get_taxonomy_description( $object );
			$parts['robots'] = $this->get_taxonomy_robots( $object );
		} elseif ( is_post_type_archive() ) {
			$parts['title']  = $this->get_post_type_archive_title();
			$parts['desc']   = $this->get_post_type_archive_description();
			$parts['robots'] = $this->get_post_type_archive_robots();
		} elseif ( is_author() ) {
			$parts['title']  = $this->get_author_title();
			$parts['desc']   = $this->get_author_description();
			$parts['robots'] = $this->get_author_robots();
		} elseif ( is_date() ) {
			$parts['title'] = $this->get_from_options( 'date_archive_title' );
			$parts['desc']  = $this->get_from_options( 'date_archive_description' );
		}

		// Noindex these.
		if (
			( is_date() && ( Helper::get_settings( 'titles.disable_date_archives' ) || Helper::get_settings( 'titles.noindex_date' ) ) ) ||
			( is_paged() && Helper::get_settings( 'titles.noindex_archive_subpages' ) )
		) {
			$parts['robots']['index'] = 'noindex';
		}

		return $parts;
	}

	/**
	 * Retrieves the SEO title set in the post metabox.
	 *
	 * @param  object|null $object Object to retrieve the title from.
	 * @return string The SEO title for the specified object, or queried object if not supplied.
	 */
	private function get_post_title( $object = null ) {
		if ( ! is_object( $object ) ) {
			return $this->get_from_options( '404_title', array(), esc_html__( 'Page not found', 'rank-math' ) );
		}

		$title = Post::get_meta( 'title', $object->ID );
		if ( '' !== $title ) {
			return $title;
		}

		$post_type = isset( $object->post_type ) ? $object->post_type : $object->query_var;
		return $this->get_from_options( "pt_{$post_type}_title", $object, '%title% %sep% %sitename%' );
	}

	/**
	 * Retrieves the SEO description set in the post metabox.
	 *
	 * Retrieve in this order:
	 *     1. Custom meta description set for the post in SERP field
	 *     2. Excerpt
	 *     3. Description template set in the Titles & Meta
	 *     4. Paragraph with the focus keyword
	 *     5. The First paragraph of the content
	 *
	 * @param  object|null $object Object to retrieve the description from.
	 * @return string The SEO description for the specified object, or queried object if not supplied.
	 */
	private function get_post_description( $object = null ) {
		if ( ! is_object( $object ) ) {
			return '';
		}

		// 1. Custom meta description set for the post in SERP field.
		$description = Post::get_meta( 'description', $object->ID );
		if ( '' !== $description ) {
			return $description;
		}

		// 2. Excerpt
		if ( ! empty( $object->post_excerpt ) ) {
			return $object->post_excerpt;
		}

		// 3. Description template set in the Titles & Meta.
		$post_type   = isset( $object->post_type ) ? $object->post_type : $object->query_var;
		$description = $this->get_from_options( "pt_{$post_type}_description", $object );

		return '' !== $description ? $description : $this->get_post_description_auto_generated( $object );
	}

	/**
	 * Auto-generate description for metadesc
	 *
	 * @param  object|null $object Object to retrieve the description from.
	 * @return string
	 */
	private function get_post_description_auto_generated( $object ) {
		// If shop page.
		if ( Post::is_shop_page() ) {
			return $this->get_post_type_archive_description();
		}

		// Early Bail!
		if ( empty( $object ) || empty( $object->post_content ) ) {
			return '';
		}

		$keywords     = Post::get_meta( 'focus_keyword', $object->ID );
		$post_content = wpautop( WordPress::strip_shortcodes( Post::is_woocommerce_page() || ( function_exists( 'is_wcfm_page' ) && is_wcfm_page() ) ? $object->post_content : do_shortcode( $object->post_content ) ) );
		$post_content = wp_kses( $post_content, array( 'p' => array() ) );

		// 4. Paragraph with the focus keyword.
		if ( ! empty( $keywords ) ) {
			$regex = '/<p>(.*' . str_replace( array( ',', ' ', '/' ), array( '|', '.', '\/' ), $keywords ) . '.*)<\/p>/iu';
			\preg_match_all( $regex, $post_content, $matches );
			if ( isset( $matches[1], $matches[1][0] ) ) {
				return $matches[1][0];
			}
		}

		// 5. The First paragraph of the content.
		\preg_match_all( '/<p>(.*)<\/p>/iu', $post_content, $matches );
		if ( isset( $matches[1], $matches[1][0] ) ) {
			return $matches[1][0];
		}

		return '';
	}

	/**
	 * Retrieves the robots set in the post metabox.
	 *
	 * @param object|null $object Object to retrieve the description from.
	 * @return string The robots for the specified object, or queried object if not supplied.
	 */
	private function get_post_robots( $object = null ) {
		if ( ! is_object( $object ) ) {
			return array();
		}

		$robots    = array();
		$post_type = $object->post_type;
		if ( Helper::get_settings( "titles.pt_{$post_type}_custom_robots" ) ) {
			$this->robots_merge( $robots, Helper::get_settings( "titles.pt_{$post_type}_robots" ) );
		}

		$post_robots = Post::get_meta( 'robots', $object->ID );
		if ( ! empty( $post_robots ) && is_array( $post_robots ) ) {
			$this->robots_merge( $robots, $post_robots );
		}

		// Noindex these conditions.
		$noindex_private            = 'private' === $object->post_status;
		$no_index_subpages          = is_paged() && Helper::get_settings( 'titles.noindex_paginated_pages' );
		$noindex_password_protected = ! empty( $object->post_password ) && Helper::get_settings( 'titles.noindex_password_protected' );

		if ( $noindex_private || $noindex_password_protected || $no_index_subpages ) {
			$robots['index'] = 'noindex';
		}

		return $robots;
	}

	/**
	 * Retrieves the SEO title set in the taxonomy metabox.
	 *
	 * @param object|null $object Object to retrieve the title from.
	 * @return string The SEO title for the specified object, or queried object if not supplied.
	 */
	private function get_taxonomy_title( $object = null ) {
		if ( ! is_object( $object ) ) {
			return $this->get_from_options( '404_title', array(), esc_html__( 'Page not found', 'rank-math' ) );
		}

		$title = Term::get_meta( 'title', $object, $object->taxonomy );
		if ( '' !== $title ) {
			return $title;
		}

		return $this->get_from_options( "tax_{$object->taxonomy}_title", $object );
	}

	/**
	 * Retrieves the SEO description set in the taxonomy metabox.
	 *
	 * @param object|null $object Object to retrieve the description from.
	 * @return string The SEO description for the specified object, or queried object if not supplied.
	 */
	private function get_taxonomy_description( $object = null ) {
		$description = Term::get_meta( 'description', $object, $object->taxonomy );
		if ( '' !== $description ) {
			return $description;
		}

		return $this->get_from_options( "tax_{$object->taxonomy}_description", $object );
	}

	/**
	 * Retrieves the robots set in the taxonomy metabox.
	 *
	 * @param object|null $object Object to retrieve the description from.
	 * @return string The robots for the specified object, or queried object if not supplied.
	 */
	private function get_taxonomy_robots( $object = null ) {
		$robots = array();

		if ( is_object( $object ) && Helper::get_settings( "titles.tax_{$object->taxonomy}_custom_robots" ) ) {
			$this->robots_merge( $robots, Helper::get_settings( "titles.tax_{$object->taxonomy}_robots" ) );
		}

		$term_robots = Term::get_meta( 'robots', $object );
		if ( ! empty( $term_robots ) && is_array( $term_robots ) ) {
			$this->robots_merge( $robots, $term_robots );
		}

		if ( Term::is_multiple_terms_query() ) {
			$robots['index'] = 'noindex';
		}

		if ( is_object( $object ) && 0 === $object->count && Helper::get_settings( 'titles.noindex_empty_taxonomies' ) ) {
			$robots['index'] = 'noindex';
		}

		return $robots;
	}

	/**
	 * Retrieves the SEO title set in the user metabox.
	 *
	 * @return string The SEO title for the specified object, or queried object if not supplied.
	 */
	private function get_author_title() {
		$title = User::get_meta( 'title', get_query_var( 'author' ) );
		if ( '' !== $title ) {
			return $title;
		}

		return $this->get_from_options( 'author_archive_title' );
	}

	/**
	 * Retrieves the SEO description set in the user metabox.
	 *
	 * @return string The SEO description for the specified object, or queried object if not supplied.
	 */
	private function get_author_description() {
		$description = User::get_meta( 'description', get_query_var( 'author' ) );
		if ( '' !== $description ) {
			return $description;
		}

		return $this->get_from_options( 'author_archive_description' );
	}

	/**
	 * Retrieves the robots set in the user metabox.
	 *
	 * @return string The robots for the specified object, or queried object if not supplied.
	 */
	private function get_author_robots() {
		$robots = array();

		if ( Helper::get_settings( 'titles.noindex_author_archive' ) ) {
			$robots['index'] = 'noindex';
		}

		$user_robots = User::get_meta( 'robots', get_query_var( 'author' ) );
		if ( ! empty( $user_robots ) && is_array( $user_robots ) ) {
			$this->robots_merge( $robots, $user_robots );
		}

		return $robots;
	}

	/**
	 * Builds the title for a post type archive
	 *
	 * @return string The title to use on a post type archive.
	 */
	private function get_post_type_archive_title() {
		$post_type = $this->get_queried_post_type();

		return $this->get_from_options( "pt_{$post_type}_archive_title", array(), '%pt_plural% Archive %page% %sep% %sitename%' );
	}

	/**
	 * Builds the description for a post type archive
	 *
	 * @return string The description to use on a post type archive.
	 */
	private function get_post_type_archive_description() {
		$post_type = $this->get_queried_post_type();

		return $this->get_from_options( "pt_{$post_type}_archive_description", array(), '%pt_plural% Archive %page% %sep% %sitename%' );
	}

	/**
	 * Retrieves the robots for a post type archive
	 *
	 * @return string The robots to use on a post type archive.
	 */
	private function get_post_type_archive_robots() {
		$robots    = array();
		$post_type = $this->get_queried_post_type();

		if ( Helper::get_settings( "titles.pt_{$post_type}_custom_robots" ) ) {
			$this->robots_merge( $robots, Helper::get_settings( "titles.pt_{$post_type}_robots" ) );
		}

		return $robots;
	}

	/**
	 * Simple function to use to pull data from $options.
	 *
	 * All titles pulled from options will be run through the Helper::replace_vars function.
	 *
	 * @param  string       $id      Name of the page to get the title from the settings for.
	 * @param  object|array $source  Possible object to pull variables from.
	 * @param  string       $default Default value if nothing found.
	 * @return string
	 */
	private function get_from_options( $id, $source = array(), $default = '' ) {
		$value = Helper::get_settings( "titles.$id" );
		return '' !== $value ? Helper::replace_vars( $value, $source ) : $default;
	}

	/**
	 * Retrieves the queried post type
	 *
	 * @return string The queried post type.
	 */
	private function get_queried_post_type() {
		$post_type = get_query_var( 'post_type' );
		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}

		return $post_type;
	}

	/**
	 * Do the final sanitization before output
	 *
	 * @param  string $title String to sanitize.
	 * @return string Sanitized title.
	 */
	private function sanitize_title( $title ) {
		// Remove excess whitespace.
		$title = preg_replace( '[\s\s+]', ' ', $title );

		// Capitalize Titles.
		if ( Helper::get_settings( 'titles.capitalize_titles' ) ) {
			$title = ucwords( $title );
		}

		/**
		 * Allow changing the <title> output.
		 *
		 * @param string $title The page title being put out.
		 */
		$title = $this->do_filter( 'frontend/title', $title );

		return esc_html( wp_strip_all_tags( stripslashes( $title ), true ) );
	}

	/**
	 * Do the final sanitization before output
	 *
	 * @param  string $description String to sanitize.
	 * @return string Sanitized description.
	 */
	private function sanitize_description( $description ) {
		/**
		 * Allow changing the meta description sentence.
		 *
		 * @param string $description The description sentence.
		 */
		$description = $this->do_filter( 'frontend/description', trim( $description ) );

		return esc_attr( wp_strip_all_tags( stripslashes( $description ), true ) );
	}

	/**
	 * Do the final sanitization before output
	 *
	 * @param  array $robots Array of robots to sanitize.
	 * @return array Sanitized robots.
	 */
	private function sanitize_robots( $robots ) {
		$robots = wp_parse_args( $robots, array(
			'index'  => 'index',
			'follow' => 'follow',
		));

		$this->robots_merge( $robots, Helper::get_settings( 'titles.robots_global' ) );

		// Force override to respect the WP settings.
		if ( '0' === (string) get_option( 'blog_public' ) || isset( $_GET['replytocom'] ) ) {
			$robots['index'] = 'noindex';
		}

		/**
		 * Allows filtering of the meta robots.
		 *
		 * @param string $robotsstr The meta robots directives to be echoed.
		 */
		$robots = $this->do_filter( 'frontend/robots', array_unique( $robots ) );

		return $robots;
	}

	/**
	 * Apply robots values to main instance.
	 *
	 * @param  array $robots Main instance.
	 * @param  array $apply  Apply this one.
	 * @return array
	 */
	private function robots_merge( &$robots, $apply ) {
		if ( empty( $apply ) ) {
			return;
		}

		$apply            = array_combine( $apply, $apply );
		$robots['index']  = ! empty( $apply['noindex'] ) ? 'noindex' : 'index';
		$robots['follow'] = ! empty( $apply['nofollow'] ) ? 'nofollow' : 'follow';
		foreach ( array( 'noarchive', 'nosnippet', 'noimageindex' ) as $check ) {
			if ( ! empty( $apply[ $check ] ) ) {
				$robots[ $check ] = $check;
				continue;
			}

			unset( $robots[ $check ] );
		}
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
}
