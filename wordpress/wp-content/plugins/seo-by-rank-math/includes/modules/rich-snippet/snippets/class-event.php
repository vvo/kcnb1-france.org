<?php
/**
 * The Event Class
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
 * Event class.
 */
class Event implements Snippet {

	/**
	 * Event rich snippet.
	 *
	 * @param array  $data   Array of json-ld data.
	 * @param JsonLD $jsonld JsonLD Instance.
	 *
	 * @return array
	 */
	public function process( $data, $jsonld ) {
		$entity = [
			'@context'    => 'https://schema.org',
			'@type'       => Helper::get_post_meta( 'snippet_event_type' ),
			'name'        => $jsonld->parts['title'],
			'description' => $jsonld->parts['desc'],
			'url'         => $jsonld->parts['url'],
			'eventStatus' => Helper::get_post_meta( 'snippet_event_status' ),
			'location'    => [
				'@type' => 'Place',
				'name'  => Helper::get_post_meta( 'snippet_event_venue' ),
				'url'   => Helper::get_post_meta( 'snippet_event_venueurl' ),
			],
			'offers'      => [
				'@type'    => 'Offer',
				'name'     => 'General Admission',
				'category' => 'primary',
			],
		];

		if ( $start_date = Helper::get_post_meta( 'snippet_event_startdate' ) ) { // phpcs:ignore
			$entity['startDate'] = str_replace( ' ', 'T', date( 'Y-m-d H:i', $start_date ) );
		}
		if ( $end_date = Helper::get_post_meta( 'snippet_event_enddate' ) ) { // phpcs:ignore
			$entity['endDate'] = str_replace( ' ', 'T', date( 'Y-m-d H:i', $end_date ) );
		}

		$jsonld->set_address( 'event', $entity['location'] );

		$jsonld->set_data([
			'snippet_event_price'               => 'price',
			'snippet_event_currency'            => 'priceCurrency',
			'snippet_event_ticketurl'           => 'url',
			'snippet_event_inventory'           => 'inventoryLevel',
			'snippet_event_availability'        => 'availability',
			'snippet_event_availability_starts' => 'validFrom',
		], $entity['offers'] );

		if ( ! empty( $entity['offers']['validFrom'] ) ) {
			$entity['offers']['validFrom'] = str_replace( ' ', 'T', date( 'Y-m-d H:i', $entity['offers']['validFrom'] ) );
		}
		if ( $performer = Helper::get_post_meta( 'snippet_event_performer' ) ) { // phpcs:ignore
			$entity['performer'] = [
				'@type' => 'Person',
				'name'  => $performer,
			];
		}

		return $entity;
	}
}
