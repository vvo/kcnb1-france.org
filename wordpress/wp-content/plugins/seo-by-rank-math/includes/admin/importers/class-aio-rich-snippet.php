<?php
/**
 * The AIO Rich Snippet Import Class
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Admin\Importers
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Admin\Importers;

use RankMath\Admin\Admin_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Import_AIO_Rich_Snippet class.
 */
class AIO_Rich_Snippet extends Plugin_Importer {

	/**
	 * The plugin name.
	 *
	 * @var string
	 */
	protected $plugin_name = 'AIO Schema Rich Snippet';

	/**
	 * Meta key, used in SQL LIKE clause for delete query.
	 *
	 * @var string
	 */
	protected $meta_key = '_bsf_post_type';

	/**
	 * Array of option keys to import and clean
	 *
	 * @var array
	 */
	protected $option_keys = array( 'bsf_', 'bsf_%' );

	/**
	 * Array of choices keys to import
	 *
	 * @var array
	 */
	protected $choices = array( 'postmeta' );

	/**
	 * Import post meta of plugin.
	 *
	 * @return array
	 */
	protected function postmeta() {
		$this->set_pagination( $this->get_post_ids( true ) );
		$snippet_posts = $this->get_post_ids();
		$snippet_types = array(
			'1'  => 'review',
			'2'  => 'event',
			'5'  => 'person',
			'6'  => 'product',
			'7'  => 'recipe',
			'8'  => 'software',
			'9'  => 'video',
			'10' => 'article',
			'11' => 'service',
		);

		foreach ( $snippet_posts as $snippet_post ) {
			$type = $snippet_post->meta_value;
			if ( ! isset( $snippet_types[ $type ] ) ) {
				continue;
			}

			$hash = array(
				'review'   => array(
					'item_reviewer' => 'name',
					'item_name'     => 'desc',
					'rating'        => 'review_rating_value',
				),
				'article'  => array(
					'article_name' => 'name',
					'article_desc' => 'desc',
				),
				'event'    => array(
					'event_title'        => 'name',
					'event_organization' => 'addressCountry',
					'event_street'       => 'streetAddress',
					'event_local'        => 'addressLocality',
					'event_region'       => 'addressRegion',
					'event_postal_code'  => 'postalCode',
					'event_desc'         => 'desc',
					'event_start_date'   => 'event_startdate',
					'event_end_date'     => 'event_enddate',
					'event_price'        => 'event_price',
					'event_cur'          => 'event_currency',
					'event_ticket_url'   => 'event_ticketurl',
				),
				'person'   => array(
					'people_fn'        => 'name',
					'people_nickname'  => 'desc',
					'people_photo'     => 'name',
					'people_job_title' => 'job_title',
					'people_street'    => 'streetAddress',
					'people_local'     => 'addressLocality',
					'people_region'    => 'addressRegion',
					'people_postal'    => 'postalCode',
				),
				'product'  => array(
					'product_brand'  => 'product_brand',
					'product_name'   => 'name',
					'product_price'  => 'product_currency',
					'product_cur'    => 'product_price',
					'product_status' => 'product_instock',
				),
				'recipe'   => array(
					'recipes_name'       => 'name',
					'recipes_preptime'   => 'recipe_preptime',
					'recipes_cooktime'   => 'recipe_cooktime',
					'recipes_totaltime'  => 'recipe_totaltime',
					'recipes_desc'       => 'desc',
					'recipes_ingredient' => 'recipe_ingredients',
				),
				'software' => array(
					'software_rating' => 'software_rating_value',
					'software_price'  => 'software_price',
					'software_cur'    => 'software_price_currency',
					'software_name'   => 'name',
					'software_os'     => 'software_operating_system',
					'software_cat'    => 'software_application_category',
				),
				'video'    => array(
					'video_title'    => 'name',
					'video_desc'     => 'desc',
					'video_thumb'    => 'rank_math_twitter_title',
					'video_url'      => 'video_url',
					'video_emb_url'  => 'video_embed_url',
					'video_duration' => 'video_duration',
				),
				'service'  => array(
					'service_type' => 'service_type',
					'service_desc' => 'desc',
				),
			);

			$type = $snippet_types[ $type ];
			if ( isset( $hash[ $type ] ) ) {
				$post_id      = $snippet_post->post_id;
				$event_array  = array( 'event_organization', 'event_street', 'event_local', 'event_region', 'event_postal_code' );
				$person_array = array( 'people_street', 'people_local', 'people_local', 'people_region', 'people_postal' );

				foreach ( $hash[ $type ] as $snippet_key => $snippet_value ) {
					$value = get_post_meta( $post_id, '_bsf_' . $snippet_key, true );

					if ( 'event' === $type && in_array( $snippet_key, $event_array ) ) {
						$event_address[ $snippet_value ] = $value;
						$value                           = $event_address;
						$snippet_value                   = 'event_address';
					}

					if ( 'person' === $type && in_array( $snippet_key, $person_array ) ) {
						$person_address[ $snippet_value ] = $value;
						$value                            = $person_address;
						$snippet_value                    = 'person_address';
					}

					update_post_meta( $post_id, 'rank_math_snippet_' . $snippet_value, $value );
				}

				update_post_meta( $post_id, 'rank_math_rich_snippet', $type );
			}
		}

		return $this->get_pagination_arg();
	}

	/**
	 * Returns array of choices of action which can be performed for plugin
	 *
	 * @return array
	 */
	public function get_choices() {
		return array(
			'postmeta' => esc_html__( 'Import Rich Snippets', 'rank-math' ) . Admin_Helper::get_tooltip( esc_html__( 'Import all Schema data for Posts, Pages, and custom post types.', 'rank-math' ) ),
		);
	}
}
