<?php
/**
 * Metabox - Software Application Rich Snippet
 *
 * @package    RankMath
 * @subpackage RankMath\RichSnippet
 */

$software = [ [ 'rank_math_rich_snippet', 'software' ] ];

$cmb->add_field([
	'id'         => 'rank_math_snippet_software_price',
	'type'       => 'text',
	'name'       => esc_html__( 'Price', 'rank-math' ),
	'dep'        => $software,
	'attributes' => [ 'type' => 'number' ],
]);

$cmb->add_field([
	'id'   => 'rank_math_snippet_software_price_currency',
	'type' => 'text',
	'name' => esc_html__( 'Price Currency', 'rank-math' ),
	'dep'  => $software,
]);

$cmb->add_field([
	'id'   => 'rank_math_snippet_software_operating_system',
	'name' => esc_html__( 'Operating System', 'rank-math' ),
	'type' => 'text',
	'desc' => esc_html__( 'For example, "Windows 7", "OSX 10.6", "Android 1.6"', 'rank-math' ),
	'dep'  => $software,
]);

$cmb->add_field([
	'id'   => 'rank_math_snippet_software_application_category',
	'name' => esc_html__( 'Application Category', 'rank-math' ),
	'type' => 'text',
	'desc' => esc_html__( 'For example, "Game", "Multimedia"', 'rank-math' ),
	'dep'  => $software,
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_software_rating_value',
	'name'       => esc_html__( 'Rating Value', 'rank-math' ),
	'type'       => 'text',
	'dep'        => $software,
	'attributes' => [
		'type' => 'number',
		'min'  => 1,
		'max'  => 5,
	],
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_software_rating_count',
	'name'       => esc_html__( 'Rating Count', 'rank-math' ),
	'type'       => 'text',
	'dep'        => $software,
	'attributes' => [ 'type' => 'number' ],
]);
