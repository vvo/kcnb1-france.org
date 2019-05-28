<?php
/**
 * The Singular Class
 *
 * @since      1.0.22
 * @package    RankMath
 * @subpackage RankMath\Paper
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Paper;

use RankMath\Post;
use RankMath\Helper;
use MyThemeShop\Helpers\WordPress;

defined( 'ABSPATH' ) || exit;

/**
 * Singular class.
 */
class Singular implements IPaper {

	/**
	 * Post object.
	 *
	 * @var WP_Post
	 */
	private $object;

	/**
	 * Retrieves the SEO title.
	 *
	 * @return string
	 */
	public function title() {
		return $this->get_post_title( $this->object );
	}

	/**
	 * Retrieves the SEO description.
	 *
	 * @return string
	 */
	public function description() {
		return $this->get_post_description( $this->object );
	}

	/**
	 * Retrieves the robots.
	 *
	 * @return string
	 */
	public function robots() {
		return $this->get_post_robots( $this->object );
	}

	/**
	 * Retrieves the canonical URL.
	 *
	 * @return array
	 */
	public function canonical() {
		$object_id          = Post::get_simple_page_id();
		$canonical          = get_permalink( $object_id );
		$canonical_unpaged  = $canonical;
		$canonical_override = Post::get_meta( 'canonical_url', $object_id );

		// Fix paginated pages canonical, but only if the page is truly paginated.
		if ( get_query_var( 'page' ) > 1 ) {
			$num_pages = ( substr_count( get_queried_object()->post_content, '<!--nextpage-->' ) + 1 );
			if ( $num_pages && get_query_var( 'page' ) <= $num_pages ) {
				global $wp_rewrite;
				$canonical = ! $wp_rewrite->using_permalinks() ? add_query_arg( 'page', get_query_var( 'page' ), $canonical ) :
					user_trailingslashit( trailingslashit( $canonical ) . get_query_var( 'page' ) );
			}
		}

		return [
			'canonical'          => $canonical,
			'canonical_unpaged'  => $canonical,
			'canonical_override' => $canonical_override,
		];
	}

	/**
	 * Set post object.
	 *
	 * @param WP_Post $object Current post object.
	 */
	public function set_object( $object ) {
		$this->object = $object;
	}

	/**
	 * Retrieves the SEO title set in the post metabox.
	 *
	 * @param object|null $object Object to retrieve the title from.
	 *
	 * @return string The SEO title for the specified object, or queried object if not supplied.
	 */
	protected function get_post_title( $object = null ) {
		if ( ! is_object( $object ) ) {
			return Paper::get_from_options( '404_title', [], esc_html__( 'Page not found', 'rank-math' ) );
		}

		$title = Post::get_meta( 'title', $object->ID );
		if ( '' !== $title ) {
			return $title;
		}

		$post_type = isset( $object->post_type ) ? $object->post_type : $object->query_var;
		return Paper::get_from_options( "pt_{$post_type}_title", $object, '%title% %sep% %sitename%' );
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
	 * @param object|null $object Object to retrieve the description from.
	 *
	 * @return string The SEO description for the specified object, or queried object if not supplied.
	 */
	protected function get_post_description( $object = null ) {
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
		$description = Paper::get_from_options( "pt_{$post_type}_description", $object );

		return '' !== $description ? $description : $this->get_post_description_auto_generated( $object );
	}

	/**
	 * Auto-generate description for metadesc
	 *
	 * @param object|null $object Object to retrieve the description from.
	 *
	 * @return string
	 */
	protected function get_post_description_auto_generated( $object ) {
		// Early Bail!
		if ( empty( $object ) || empty( $object->post_content ) ) {
			return '';
		}

		$keywords     = Post::get_meta( 'focus_keyword', $object->ID );
		$post_content = $this->should_apply_shortcode() ? do_shortcode( $object->post_content ) : $object->post_content;
		$post_content = wpautop( WordPress::strip_shortcodes( $post_content ) );
		$post_content = wp_kses( $post_content, [ 'p' => [] ] );

		// 4. Paragraph with the focus keyword.
		if ( ! empty( $keywords ) ) {
			$regex = '/<p>(.*' . str_replace( [ ',', ' ', '/' ], [ '|', '.', '\/' ], $keywords ) . '.*)<\/p>/iu';
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
	 *
	 * @return string The robots for the specified object, or queried object if not supplied.
	 */
	protected function get_post_robots( $object = null ) {
		if ( ! is_object( $object ) ) {
			return [];
		}

		$post_type = $object->post_type;
		$robots    = Paper::robots_combine( Post::get_meta( 'robots', $object->ID ) );
		if ( empty( $robots ) && Helper::get_settings( "titles.pt_{$post_type}_custom_robots" ) ) {
			$robots = Paper::robots_combine( Helper::get_settings( "titles.pt_{$post_type}_robots" ) );
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
	 * Should apply shortcode on content.
	 *
	 * @return bool
	 */
	private function should_apply_shortcode() {
		$is_woocommerce_page = Post::is_woocommerce_page();
		$is_wcfm_page        = function_exists( 'is_wcfm_page' ) && is_wcfm_page();
		if ( $is_woocommerce_page || $is_wcfm_page ) {
			return false;
		}

		return apply_filters( 'rank_math/paper/auto_generated_description/apply_shortcode', false );
	}
}
