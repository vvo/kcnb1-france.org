<?php
/**
 * The Content Processor.
 *
 * It will extract links from the content and saves them for the given post id.
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Links
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Links;

use MyThemeShop\Helpers\Str;
use RankMath\Sitemap\Classifier;

defined( 'ABSPATH' ) || exit;

/**
 * ContentProcessor class.
 */
class ContentProcessor {

	/**
	 * Link storage.
	 *
	 * @var Storage
	 */
	public $storage;

	/**
	 * Link classifier.
	 *
	 * @var Classifier
	 */
	protected $classifier;

	/**
	 * [__construct description]
	 */
	public function __construct() {
		$this->storage    = new Storage;
		$this->classifier = new Classifier( home_url() );
	}

	/**
	 * Process the content for the given post id.
	 *
	 * @param int    $post_id The post id.
	 * @param string $content The content to process.
	 */
	public function process( $post_id, $content ) {
		$links  = $this->extract( $content );
		$counts = array(
			'internal_link_count' => 0,
			'external_link_count' => 0,
		);

		$new_links = array();
		foreach ( $links as $link ) {
			$link_type = $this->is_valid_link_type( $link );
			if ( empty( $link_type ) ) {
				continue;
			}

			$target_post_id = 0;
			if ( Classifier::TYPE_INTERNAL === $link_type ) {
				$counts['internal_link_count'] += 1;
				$target_post_id                 = url_to_postid( $link );
			} else {
				$counts['external_link_count'] += 1;
			}

			$new_links[] = new Link( $link, $target_post_id, $link_type );
		}

		// Start processing.
		$old_links = $this->get_stored_internal_links( $post_id );
		$this->storage->cleanup( $post_id );
		$this->storage->save_links( $post_id, $new_links );
		$this->storage->update_link_counts( $post_id, $counts, array_merge( $new_links, $old_links ) );
	}

	/**
	 * Extracts the hrefs from the content and returns them as an array.
	 *
	 * @param  string $content Content to extract links from.
	 * @return array All the extracted links
	 */
	public function extract( $content ) {
		$links = array();
		if ( false === Str::contains( 'href', $content ) ) {
			return $links;
		}

		$regexp = '<a\s[^>]*href=("??)([^" >]*?)\\1[^>]*>';

		// Used modifiers iU to match case insensitive and make greedy quantifiers lazy.
		if ( preg_match_all( "/$regexp/iU", $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$links[] = trim( $match[2], "'" );
			}
		}

		return $links;
	}

	/**
	 * Retrieves the stored internal links for the supplied post.
	 *
	 * @param int $post_id The post to fetch links for.
	 *
	 * @return Link[] List of internal links connected to the post.
	 */
	public function get_stored_internal_links( $post_id ) {
		$links = $this->storage->get_links( $post_id );
		return array_filter( $links, array( $this, 'filter_internal_link' ) );
	}

	/**
	 * Filters on INTERNAL links.
	 *
	 * @param  Link $link Link to test type of.
	 * @return bool True for internal link, false for external link.
	 */
	protected function filter_internal_link( Link $link ) {
		return $link->get_type() === Classifier::TYPE_INTERNAL;
	}

	/**
	 * Check if link is valid
	 *
	 * @param  string $link Link to evaluate.
	 * @return boolean
	 */
	private function is_valid_link_type( $link ) {
		if ( empty( $link ) || '#' === $link[0] ) {
			return false;
		}

		return $this->classifier->classify( $link );
	}
}
