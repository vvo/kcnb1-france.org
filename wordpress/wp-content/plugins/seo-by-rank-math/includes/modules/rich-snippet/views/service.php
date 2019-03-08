<?php
/**
 * Metabox - Service Rich Snippet
 *
 * @package    RankMath
 * @subpackage RankMath\RichSnippet
 */

$service = [ [ 'rank_math_rich_snippet', 'service' ] ];

$cmb->add_field([
	'id'   => 'rank_math_snippet_service_type',
	'name' => esc_html__( 'Service Type', 'rank-math' ),
	'type' => 'text',
	'desc' => esc_html__( 'The type of service being offered, e.g. veterans\' benefits, emergency relief, etc.', 'rank-math' ),
	'dep'  => $service,
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_service_price',
	'type'       => 'text',
	'name'       => esc_html__( 'Price', 'rank-math' ),
	'dep'        => $service,
	'attributes' => [ 'type' => 'number' ],
]);

$cmb->add_field([
	'id'   => 'rank_math_snippet_service_price_currency',
	'type' => 'text',
	'name' => esc_html__( 'Price Currency', 'rank-math' ),
	'dep'  => $service,
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_service_rating_value',
	'name'       => esc_html__( 'Rating Value', 'rank-math' ),
	'type'       => 'text',
	'dep'        => $service,
	'attributes' => [
		'type' => 'number',
		'min'  => 1,
		'max'  => 5,
	],
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_service_rating_count',
	'name'       => esc_html__( 'Rating Count', 'rank-math' ),
	'type'       => 'text',
	'dep'        => $service,
	'attributes' => [ 'type' => 'number' ],
]);
