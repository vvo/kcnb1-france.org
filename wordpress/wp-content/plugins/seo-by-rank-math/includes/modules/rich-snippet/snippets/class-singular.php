<?php
/**
 * The Singular Class
 *
 * @since      1.0.13
 * @package    RankMath
 * @subpackage RankMath\RichSnippet
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\RichSnippet;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Conditional;

defined( 'ABSPATH' ) || exit;

/**
 * Singular class.
 */
class Singular implements Snippet {

	use Hooker;

	/**
	 * Generate rich snippet.
	 *
	 * @param array  $data   Array of json-ld data.
	 * @param JsonLD $jsonld JsonLD Instance.
	 *
	 * @return array
	 */
	public function process( $data, $jsonld ) {
		$schema = $this->can_add_schema( $jsonld );
		if ( false === $schema ) {
			return $data;
		}

		$hook = 'snippet/rich_snippet_' . $schema;
		/**
		 * Short-circuit if 3rd party is interested generating his own data.
		 */
		$pre = $this->do_filter( $hook, false, $jsonld->parts, $data );
		if ( false !== $pre ) {
			$data['richSnippet'] = $this->do_filter( $hook . '_entity', $pre );
			return $data;
		}

		$object = $this->get_schema_class( $schema );
		if ( false === $object ) {
			return $data;
		}

		$entity = $object->process( $data, $jsonld );

		// Images.
		$jsonld->add_prop( 'thumbnail', $entity );
		if ( ! empty( $entity['image'] ) && 'video' === $schema ) {
			$entity['thumbnailUrl'] = $entity['image']['url'];
			unset( $entity['image'] );
		}

		$data['richSnippet'] = $this->do_filter( $hook . '_entity', $entity );

		return $data;
	}

	/**
	 * Can add schema.
	 *
	 * @param JsonLD $jsonld JsonLD Instance.
	 *
	 * @return boolean|string
	 */
	private function can_add_schema( $jsonld ) {
		$schema = Helper::get_post_meta( 'rich_snippet' );
		if (
			! $schema &&
			! metadata_exists( 'post', $jsonld->post_id, 'rank_math_rich_snippet' ) &&
			$schema = Helper::get_settings( "titles.pt_{$jsonld->post->post_type}_default_rich_snippet" ) // phpcs:ignore
		) {
			$schema = Conditional::is_woocommerce_active() && is_product() ? $schema : ( 'article' === $schema ? $schema : '' );
		}

		return $schema;
	}

	/**
	 * Get Schema Class.
	 *
	 * @param string $schema Schema type.
	 * @return bool|Class
	 */
	private function get_schema_class( $schema ) {
		$data = [
			'article'    => '\\RankMath\\RichSnippet\\Article',
			'book'       => '\\RankMath\\RichSnippet\\Book',
			'course'     => '\\RankMath\\RichSnippet\\Course',
			'event'      => '\\RankMath\\RichSnippet\\Event',
			'jobposting' => '\\RankMath\\RichSnippet\\JobPosting',
			'music'      => '\\RankMath\\RichSnippet\\Music',
			'recipe'     => '\\RankMath\\RichSnippet\\Recipe',
			'restaurant' => '\\RankMath\\RichSnippet\\Restaurant',
			'video'      => '\\RankMath\\RichSnippet\\Video',
			'person'     => '\\RankMath\\RichSnippet\\Person',
			'review'     => '\\RankMath\\RichSnippet\\Review',
			'service'    => '\\RankMath\\RichSnippet\\Service',
			'software'   => '\\RankMath\\RichSnippet\\Software',
			'product'    => '\\RankMath\\RichSnippet\\Product',
		];

		if ( isset( $data[ $schema ] ) && class_exists( $data[ $schema ] ) ) {
			return new $data[ $schema ];
		}

		return false;
	}
}
