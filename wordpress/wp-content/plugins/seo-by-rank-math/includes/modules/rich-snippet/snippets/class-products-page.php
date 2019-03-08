<?php
/**
 * The Products Page Class
 *
 * @since      1.0.13
 * @package    RankMath
 * @subpackage RankMath\RichSnippet
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\RichSnippet;

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Products_Page class.
 */
class Products_Page implements Snippet {

	/**
	 * Outputs code to allow recognition of the CollectionPage.
	 *
	 * @link https://schema.org/CollectionPage
	 *
	 * @param array  $data   Array of json-ld data.
	 * @param JsonLD $jsonld JsonLD Instance.
	 *
	 * @return array
	 */
	public function process( $data, $jsonld ) {
		$data['ProductsPage'] = [
			'@context' => 'https://schema.org/',
			'@graph'   => [],
		];

		while ( have_posts() ) {
			the_post();

			$post_id = get_the_ID();
			$url     = $jsonld->get_post_url( $post_id );

			$part = [
				'@type'       => 'Product',
				'name'        => $jsonld->get_post_title( $post_id ),
				'url'         => $url,
				'@id'         => $url,
				'description' => $jsonld->get_product_desc( $post_id ),
			];

			$data['ProductsPage']['@graph'][] = $part;
		}

		wp_reset_query();

		return $data;
	}
}
