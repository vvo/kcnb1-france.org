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
	'classes'    => 'cmb-row-50',
	'dep'        => $service,
	'attributes' => [ 'type' => 'number' ],
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_service_price_currency',
	'type'       => 'text',
	'name'       => esc_html__( 'Price Currency', 'rank-math' ),
	'desc'       => esc_html__( 'ISO 4217 Currency code. Example: EUR', 'rank-math' ),
	'classes'    => 'cmb-row-50 rank-math-validate-field',
	'attributes' => [
		'data-rule-regex'       => 'true',
		'data-validate-pattern' => '^[A-Z]{3}$',
		'data-msg-regex'        => esc_html__( 'Please use the correct format. Example: EUR', 'rank-math' ),
	],
	'dep'        => $service,
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_service_rating_value',
	'name'       => esc_html__( 'Rating', 'rank-math' ),
	'desc'       => esc_html__( 'Average of all ratings (1-5). Example: 4.7', 'rank-math' ),
	'type'       => 'text',
	'dep'        => $service,
	'classes'    => 'cmb-row-50',
	'attributes' => [
		'type' => 'number',
		'min'  => 1,
		'max'  => 5,
	],
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_service_rating_count',
	'name'       => esc_html__( 'Rating Count', 'rank-math' ),
	'desc'       => esc_html__( 'Number of ratings', 'rank-math' ),
	'type'       => 'text',
	'dep'        => $service,
	'classes'    => 'cmb-row-50',
	'attributes' => [ 'type' => 'number' ],
]);
