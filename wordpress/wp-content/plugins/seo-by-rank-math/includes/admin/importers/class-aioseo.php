<?php
/**
 * The AIO SEO Import Class
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Admin\Importers
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Admin\Importers;

use RankMath\Helper;
use MyThemeShop\Helpers\Str;
use RankMath\Admin\Admin_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * AIOSEO class.
 */
class AIOSEO extends Plugin_Importer {

	/**
	 * The plugin name.
	 *
	 * @var string
	 */
	protected $plugin_name = 'All In One SEO Pack';

	/**
	 * Meta key, used in SQL LIKE clause for delete query.
	 *
	 * @var string
	 */
	protected $meta_key = '_aioseop_';

	/**
	 * Array of option keys to import and clean
	 *
	 * @var array
	 */
	protected $option_keys = array( '_aioseop_%', 'aioseop_options' );

	/**
	 * Array of choices keys to import
	 *
	 * @var array
	 */
	protected $choices = array( 'settings', 'postmeta' );

	/**
	 * Import settings of plugin.
	 *
	 * @return bool
	 */
	protected function settings() {

		$all_opts = rank_math()->settings->all_raw();
		$settings = $all_opts['general'];
		$titles   = $all_opts['titles'];
		$sitemap  = $all_opts['sitemap'];
		$aioseo   = get_option( 'aioseop_options' );

		// Titles & Descriptions.
		if ( ! empty( $aioseo['aiosp_home_title'] ) ) {
			$aioseo['aiosp_home_page_title_format'] = $aioseo['aiosp_home_title'];
		}
		$hash = array(
			'aiosp_home_page_title_format' => 'homepage_title',
			'aiosp_home_description'       => 'homepage_description',
			'aiosp_author_title_format'    => 'author_archive_title',
			'aiosp_date_title_format'      => 'date_archive_title',
			'aiosp_search_title_format'    => 'search_title',
			'aiosp_404_title_format'       => '404_title',
		);

		$aiosp_cpostnoindex  = isset( $aioseo['aiosp_cpostnoindex'] ) && is_array( $aioseo['aiosp_cpostnoindex'] ) ? $aioseo['aiosp_cpostnoindex'] : array();
		$aiosp_cpostnofollow = isset( $aioseo['aiosp_cpostnofollow'] ) && is_array( $aioseo['aiosp_cpostnofollow'] ) ? $aioseo['aiosp_cpostnofollow'] : array();
		if ( ! empty( $aiosp_cpostnoindex ) || ! empty( $aiosp_cpostnofollow ) ) {
			foreach ( Helper::get_accessible_post_types() as $post_type ) {
				$hash[ "aiosp_{$post_type}_title_format" ] = "pt_{$post_type}_title";

				// NOINDEX.
				$is_noindex  = in_array( $post_type, $aiosp_cpostnoindex );
				$is_nofollow = in_array( $post_type, $aiosp_cpostnofollow );
				if ( $is_noindex || $is_nofollow ) {
					$titles[ "pt_{$post_type}_custom_robots" ] = 'on';
					if ( $is_noindex ) {
						$titles[ "pt_{$post_type}_robots" ][] = 'noindex';
					}
					if ( $is_nofollow ) {
						$titles[ "pt_{$post_type}_robots" ][] = 'nofollow';
					}
					$titles[ "pt_{$post_type}_robots" ] = \array_unique( $titles[ "pt_{$post_type}_robots" ] );
				}
			}
		}

		foreach ( Helper::get_accessible_taxonomies() as $taxonomy => $object ) {
			$convert = 'post_tag' === $taxonomy ? 'tag' : $taxonomy;

			$hash[ "aiosp_{$convert}_title_format" ] = "tax_{$taxonomy}_title";

			// NOINDEX.
			if ( ! empty( $aioseo[ "aiosp_{$taxonomy}_noindex" ] ) ) {
				$titles[ "tax_{$taxonomy}_custom_robots" ] = 'on';
				$titles[ "tax_{$taxonomy}_robots" ][]      = 'noindex';
				$titles[ "tax_{$taxonomy}_robots" ]        = \array_unique( $titles[ "tax_{$taxonomy}_robots" ] );
			}
		}
		$this->replace( $hash, $aioseo, $titles, 'convert_variables' );

		// Verification Codes.
		$hash = array(
			'aiosp_google_verify'    => 'google_verify',
			'aiosp_bing_verify'      => 'bing_verify',
			'aiosp_pinterest_verify' => 'pinterest_verify',
		);
		$this->replace( $hash, $aioseo, $settings );

		// OpenGraph.
		if ( ! empty( $aioseo['modules']['aiosp_opengraph_options'] ) && is_array( $aioseo['modules']['aiosp_opengraph_options'] ) ) {

			$opengraph_settings = $aioseo['modules']['aiosp_opengraph_options'];
			$set_meta           = 'on' === $opengraph_settings['aiosp_opengraph_setmeta'];

			$titles['homepage_facebook_title']       = $set_meta ? $titles['homepage_title'] : $this->convert_variables( $opengraph_settings['aiosp_opengraph_hometitle'] );
			$titles['homepage_facebook_description'] = $set_meta ? $titles['homepage_description'] : $this->convert_variables( $opengraph_settings['aiosp_opengraph_description'] );

			if ( isset( $opengraph_settings['aiosp_opengraph_homeimage'] ) ) {
				$this->replace_image( $opengraph_settings['aiosp_opengraph_homeimage'], $titles, 'homepage_facebook_image', 'homepage_facebook_image_id' );
			}

			$titles['facebook_admin_id'] = $opengraph_settings['aiosp_opengraph_key'];
			$titles['facebook_app_id']   = $opengraph_settings['aiosp_opengraph_appid'];

			if ( isset( $opengraph_settings['aiosp_opengraph_person_or_org'] ) && ! empty( $opengraph_settings['aiosp_opengraph_person_or_org'] ) ) {
				Helper::update_modules( array( 'local-seo' => 'on' ) );

				$titles['knowledgegraph_name'] = $opengraph_settings['aiosp_opengraph_social_name'];
				$titles['knowledgegraph_type'] = 'org' === $opengraph_settings['aiosp_opengraph_person_or_org'] ? 'company' : 'person';
			}

			if ( isset( $opengraph_settings['aiosp_opengraph_profile_links'] ) && ! empty( $opengraph_settings['aiosp_opengraph_profile_links'] ) ) {
				$social_links = explode( "\n", $opengraph_settings['aiosp_opengraph_profile_links'] );
				$social_links = array_filter( $social_links );
				if ( ! empty( $social_links ) ) {
					$services = array( 'facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'pinterest', 'soundcloud', 'tumblr', 'myspace' );
					foreach ( $social_links as $social_link ) {
						foreach ( $services as $service ) {
							if ( Str::contains( $service, $social_link ) ) {
								$titles[ 'social_url_' . $service ] = $social_link;
							}
						}
					}
				}
			}
		}

		if ( ! empty( $aioseo['modules']['aiosp_sitemap_options'] ) && is_array( $aioseo['modules']['aiosp_sitemap_options'] ) ) {

			$sitemap_settings = $aioseo['modules']['aiosp_sitemap_options'];

			// Sitemap.
			if ( isset( $sitemap_settings['enablexmlsitemap'] ) ) {
				Helper::update_modules( array( 'sitemap' => 'on' ) );
			}
			$hash = array(
				'aiosp_sitemap_max_posts'  => 'items_per_page',
				'aiosp_sitemap_excl_pages' => 'exclude_posts',
			);
			$this->replace( $hash, $sitemap_settings, $sitemap );

			// Sitemap - Post Types.
			$all = in_array( 'all', $sitemap_settings['aiosp_sitemap_posttypes'] );
			foreach ( Helper::get_accessible_post_types() as $post_type ) {
				$sitemap[ "pt_{$post_type}_sitemap" ] = $all || in_array( $post_type, $sitemap_settings['aiosp_sitemap_posttypes'] ) ? 'on' : 'off';
			}

			// Sitemap - Taxonomies.
			$all = in_array( 'all', $sitemap_settings['aiosp_sitemap_taxonomies'] );
			foreach ( Helper::get_accessible_taxonomies() as $taxonomy => $object ) {
				$sitemap[ "tax_{$taxonomy}_sitemap" ] = $all || in_array( $taxonomy, $sitemap_settings['aiosp_sitemap_taxonomies'] ) ? 'on' : 'off';
			}

			// Sitemap - Exclude Terms.
			if ( ! empty( $sitemap_settings['aiosp_sitemap_excl_categories'] ) ) {
				$sitemap['exclude_terms'] = implode( ',', $sitemap_settings['aiosp_sitemap_excl_categories'] );
			}

			// Sitemap - Author / User.
			$titles['disable_author_archives'] = isset( $sitemap_settings['aiosp_sitemap_archive'] ) ? 'on' : 'off';
		}

		Helper::update_all_settings( $settings, $titles, $sitemap );

		return true;
	}

	/**
	 * Import post meta of plugin.
	 *
	 * @return array
	 */
	protected function postmeta() {
		$this->set_pagination( $this->get_post_ids( true ) );
		$post_ids = $this->get_post_ids();

		$hash = array(
			'_aioseop_title'       => 'rank_math_title',
			'_aioseop_keywords'    => 'rank_math_focus_keyword',
			'_aioseop_description' => 'rank_math_description',
			'_aioseop_custom_link' => 'rank_math_canonical_url',
		);
		foreach ( $post_ids as $post ) {
			$post_id = $post->ID;
			$this->replace_meta( $hash, null, $post_id, 'post' );
			$this->set_post_robots( $post_id );

			// OpenGraph.
			$opengraph_meta = get_post_meta( $post_id, '_aioseop_opengraph_settings', true );
			if ( ! empty( $opengraph_meta ) && is_array( $opengraph_meta ) ) {
				if ( ! empty( $opengraph_meta['aioseop_opengraph_settings_title'] ) ) {
					update_post_meta( $post_id, 'rank_math_facebook_title', $opengraph_meta['aioseop_opengraph_settings_title'] );
					update_post_meta( $post_id, 'rank_math_twitter_title', $opengraph_meta['aioseop_opengraph_settings_title'] );
				}
				if ( ! empty( $opengraph_meta['aioseop_opengraph_settings_desc'] ) ) {
					update_post_meta( $post_id, 'rank_math_facebook_description', $opengraph_meta['aioseop_opengraph_settings_desc'] );
					update_post_meta( $post_id, 'rank_math_twitter_description', $opengraph_meta['aioseop_opengraph_settings_desc'] );
				}

				$func = 'update_post_meta';

				// Facebook Image.
				$og_thumb = ! empty( $opengraph_meta['aioseop_opengraph_settings_customimg'] ) ? $opengraph_meta['aioseop_opengraph_settings_customimg'] : $opengraph_meta['aioseop_opengraph_settings_image'];
				if ( ! empty( $og_thumb ) ) {
					$this->replace_image( $og_thumb, $func, 'rank_math_facebook_image', 'rank_math_facebook_image_id', $post_id );
				}

				// Twitter Card Type.
				if ( ! empty( $opengraph_meta['aioseop_opengraph_settings_setcard'] ) ) {
					$twitter_card_type = 'summary' === $opengraph_meta['aioseop_opengraph_settings_setcard'] ? 'summary_card' : 'summary_large_image';
					update_post_meta( $post_id, 'rank_math_twitter_card_type', $twitter_card_type );
				}
			}
		}

		return $this->get_pagination_arg();
	}

	/**
	 * Set post robots
	 *
	 * @param int $post_id Post id.
	 */
	private function set_post_robots( $post_id ) {

		// ROBOTS.
		$robots_nofollow = get_post_meta( $post_id, '_aioseop_nofollow', true );
		$robots_noindex  = get_post_meta( $post_id, '_aioseop_noindex', true );

		// Sitemap.
		$exclude_sitemap = get_post_meta( $post_id, '_aioseop_sitemap_exclude', true );
		$exclude_sitemap = 'on' === $exclude_sitemap ? true : false;

		// If all are empty, then keep default robots.
		if ( empty( $robots_nofollow ) && empty( $robots_noindex ) ) {
			$robots = $exclude_sitemap ? array( 'noindex' ) : array();
			update_post_meta( $post_id, 'rank_math_robots', $robots );
			return;
		}

		$robots = (array) get_post_meta( $post_id, 'rank_math_robots', true );
		if ( 'on' == $robots_nofollow ) {
			$robots[] = 'nofollow';
		}

		if ( 'on' == $robots_noindex || $exclude_sitemap ) {
			$robots[] = 'noindex';
		}

		update_post_meta( $post_id, 'rank_math_robots', array_unique( $robots ) );
	}

	/**
	 * Returns array of choices of action which can be performed for plugin
	 *
	 * @return array
	 */
	public function get_choices() {
		return array(
			'settings' => esc_html__( 'Import Settings', 'rank-math' ) . Admin_Helper::get_tooltip( esc_html__( 'Import AIO SEO plugin settings, global meta, sitemap settings, etc.', 'rank-math' ) ),
			'postmeta' => esc_html__( 'Import Post Meta', 'rank-math' ) . Admin_Helper::get_tooltip( esc_html__( 'Import meta information of your posts/pages like the titles, descriptions, robots meta, OpenGraph info, etc.', 'rank-math' ) ),
		);
	}
}
