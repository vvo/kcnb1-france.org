<?php
/**
 * The WP Schema Pro Import Class
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Admin\Importers
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Admin\Importers;

use RankMath\Helper;
use RankMath\Admin\Admin_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * WP_Schema_Pro class.
 */
class WP_Schema_Pro extends Plugin_Importer {

	/**
	 * The plugin name.
	 *
	 * @var string
	 */
	protected $plugin_name = 'WP Schema Pro';

	/**
	 * Meta key, used in SQL LIKE clause for delete query.
	 *
	 * @var string
	 */
	protected $meta_key = 'bsf-aiosrs';

	/**
	 * Array of option keys to import and clean
	 *
	 * @var array
	 */
	protected $option_keys = [ 'wp-schema-pro-general-settings', 'wp-schema-pro-social-profiles', 'wp-schema-pro-global-schemas' ];

	/**
	 * Array of choices keys to import
	 *
	 * @var array
	 */
	protected $choices = [ 'settings', 'postmeta' ];

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

		$schema_general = get_option( 'wp-schema-pro-general-settings' );
		$schema_social  = get_option( 'wp-schema-pro-social-profiles' );
		$schema_global  = get_option( 'wp-schema-pro-global-schemas' );

		// Knowledge Graph Logo.
		if ( isset( $schema_general['site-logo-custom'] ) ) {
			$this->replace_image( $schema_general['site-logo-custom'], $titles, 'knowledgegraph_logo', 'knowledgegraph_logo_id' );
		}

		// General.
		$hash = [ 'site-represent' => 'knowledgegraph_type' ];

		$has_key          = 'person' === $schema_general['site-represent'] ? 'person-name' : 'site-name';
		$hash[ $has_key ] = 'knowledgegraph_name';
		$this->replace( $hash, $schema_general, $titles );

		$titles['local_seo'] = isset( $schema_general['site-represent'] ) && ! empty( $yoast_titles['site-represent'] ) ? 'on' : 'off';

		// Social.
		$hash = [
			'facebook'  => 'social_url_facebook',
			'twitter'   => 'twitter_author_names',
			'instagram' => 'social_url_instagram',
			'linkedin'  => 'social_url_linkedin',
			'youtube'   => 'social_url_youtube',
			'pinterest' => 'social_url_pinterest',
		];
		$this->replace( $hash, $schema_social, $titles );

		// About & Contact Page.
		$hash = [
			'about-page'   => 'local_seo_about_page',
			'contact-page' => 'local_seo_contact_page',
		];
		$this->replace( $hash, $schema_global, $titles );

		Helper::update_all_settings( '', $titles, '' );

		return true;
	}

	/**
	 * Import post meta of plugin.
	 *
	 * @return array
	 */
	protected function postmeta() {
		$hash = $this->get_schema_types();
		$this->set_pagination( $this->get_post_ids( true ) );

		foreach ( $this->get_post_ids() as $snippet_post ) {
			$post_id = $snippet_post->ID;
			$snippet = $this->get_snippet_details( $post_id );
			if ( ! $snippet ) {
				continue;
			}

			$type    = $snippet['type'];
			$details = $snippet['details'];

			if ( ! isset( $hash[ $type ] ) ) {
				continue;
			}

			foreach ( $hash[ $type ] as $snippet_key => $snippet_value ) {
				$value = $this->get_schema_meta( $details, $snippet_key, $post_id );
				update_post_meta( $post_id, 'rank_math_snippet_' . $snippet_value, $value );
			}

			update_post_meta( $post_id, 'rank_math_rich_snippet', $this->sanitize_schema_type( $type ) );
		}

		return $this->get_pagination_arg();
	}

	/**
	 * Get post meta for schema plugin
	 *
	 * @param  array  $details     Array of details.
	 * @param  string $snippet_key Snippet key.
	 * @param  string $post_id     Post id.
	 * @return string
	 */
	private function get_schema_meta( $details, $snippet_key, $post_id ) {
		$value = isset( $details[ $snippet_key ] ) ? $details[ $snippet_key ] : '';
		if ( 'custom-text' === $value ) {
			return isset( $details[ $snippet_key . '-custom-text' ] ) ? $details[ $snippet_key . '-custom-text' ] : '';
		}

		if ( 'create-field' === $value ) {
			return get_post_meta( $post_id, $type . '-' . $snippet['id'] . '-' . $snippet_key, true );
		}

		if ( 'specific-field' === $value ) {
			$key = isset( $details[ $snippet_key . '-specific-field' ] ) ? $details[ $snippet_key . '-specific-field' ] : '';
			return get_post_meta( $post_id, $key, true );
		}

		return $value;
	}

	/**
	 * Sanitize schema type before saving
	 *
	 * @param  string $type Schema type to sanitize.
	 * @return string
	 */
	private function sanitize_schema_type( $type ) {
		$hash = [
			'job-posting'          => 'jobposting',
			'video-object'         => 'video',
			'software-application' => 'software',
		];

		return isset( $hash[ $type ] ) ? $hash[ $type ] : $type;
	}

	/**
	 * Get Snippet Details stored in aiosrs-schema posts
	 *
	 * @param int $post_id Post id.
	 * @return array
	 */
	private function get_snippet_details( $post_id ) {
		global $wpdb;

		$post_type = get_post_type( $post_id );
		$query     = "SELECT p.ID, pm.meta_value FROM {$wpdb->postmeta} as pm
		INNER JOIN {$wpdb->posts} as p ON pm.post_id = p.ID
		WHERE pm.meta_key = 'bsf-aiosrs-schema-location'
		AND p.post_type = 'aiosrs-schema'
		AND p.post_status = 'publish'";

		$orderby    = ' ORDER BY p.post_date DESC LIMIT 1';
		$meta_args  = "pm.meta_value LIKE '%\"basic-global\"%'";
		$meta_args .= " OR pm.meta_value LIKE '%\"basic-singulars\"%'";
		$meta_args .= " OR pm.meta_value LIKE '%\"{$post_type}|all\"%'";
		$meta_args .= " OR pm.meta_value LIKE '%\"post-{$post_id}\"%'";

		$local_posts = $wpdb->get_col( $query . ' AND (' . $meta_args . ')' . $orderby ); // phpcs:ignore
		if ( empty( $local_posts ) ) {
			return false;
		}

		$current_page_data = [];
		foreach ( $local_posts as $local_post ) {
			$snippet_type = get_post_meta( $local_post, 'bsf-aiosrs-schema-type', true );

			return [
				'id'      => $local_post,
				'type'    => $snippet_type,
				'details' => get_post_meta( $local_post, 'bsf-aiosrs-' . $snippet_type, true ),
			];
		}
	}

	/**
	 * Returns array of choices of action which can be performed for plugin
	 *
	 * @return array
	 */
	public function get_choices() {
		return [
			'settings' => esc_html__( 'Import Settings', 'rank-math' ) . Admin_Helper::get_tooltip( esc_html__( 'Plugin settings and site-wide meta data.', 'rank-math' ) ),
			'postmeta' => esc_html__( 'Import Rich Snippets', 'rank-math' ) . Admin_Helper::get_tooltip( esc_html__( 'Import all Schema data for Posts, Pages, and custom post types.', 'rank-math' ) ),
		];
	}

	/**
	 * Get schema types
	 *
	 * @return array
	 */
	private function get_schema_types() {
		return [
			'article'              => [
				'name'        => 'name',
				'description' => 'desc',
				'schema-type' => 'article_type',
			],
			'book'                 => [
				'name'         => 'name',
				'url'          => 'url',
				'author'       => 'author',
				'work-example' => 'book_editions',
			],
			'course'               => [
				'name'             => 'name',
				'description'      => 'desc',
				'orgnization-name' => 'provider',
			],
			'event'                => [
				'name'           => 'name',
				'description'    => 'desc',
				'ticket-buy-url' => 'event_ticketurl',
				'location'       => 'event_venue',
				'start-date'     => 'event_startdate',
				'end-date'       => 'event_enddate',
				'price'          => 'event_price',
				'currency'       => 'event_currency',
				'avail'          => 'event_availability',
			],
			'job-posting'          => [
				'title'                   => 'name',
				'description'             => 'desc',
				'salary'                  => 'jobposting_salary',
				'salary-currency'         => 'jobposting_currency',
				'salary-unit'             => 'jobposting_payroll',
				'job-type'                => 'jobposting_employment_type',
				'jobposting_organization' => 'orgnization-name',
				'jobposting_url'          => 'jobposting_url',
			],
			'product'              => [
				'brand-name' => 'product_brand',
				'name'       => 'name',
				'price'      => 'product_currency',
				'currency'   => 'product_price',
				'avail'      => 'product_instock',
			],
			'recipe'               => [
				'name'              => 'name',
				'description'       => 'desc',
				'recipe-category'   => 'recipe_type',
				'recipe-cuisine'    => 'recipe_cuisine',
				'recipe-keywords'   => 'recipe_keywords',
				'nutrition'         => 'recipe_calories',
				'preperation-time'  => 'recipe_preptime',
				'cook-time'         => 'recipe_cooktime',
				'recipes_totaltime' => 'recipe_totaltime',
				'ingredients'       => 'recipe_ingredients',
			],
			'video-object'         => [
				'name'              => 'name',
				'description'       => 'desc',
				'content-url'       => 'video_url',
				'embed-url'         => 'video_embed_url',
				'duration'          => 'video_duration',
				'interaction-count' => 'video_views',
			],
			'review'               => [
				'item'        => 'name',
				'description' => 'desc',
				'rating'      => 'review_rating_value',
			],
			'person'               => [
				'name'      => 'name',
				'email'     => 'person_email',
				'gender'    => 'person_gender',
				'job-title' => 'job_title',
			],
			'service'              => [
				'name'         => 'name',
				'description'  => 'desc',
				'type'         => 'service_type',
				'price-range'  => 'price',
				'rating'       => 'service_rating_value',
				'review-count' => 'service_rating_count',
			],
			'software-application' => [
				'name'             => 'name',
				'rating'           => 'software_rating_value',
				'review-count'     => 'software_rating_count',
				'price'            => 'software_price',
				'currency'         => 'software_price_currency',
				'operating-system' => 'software_operating_system',
				'category'         => 'software_application_category',
			],
		];
	}
}
