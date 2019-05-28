<?php
/**
 * Metabox - Product Rich Snippet
 *
 * @package    RankMath
 * @subpackage RankMath\RichSnippet
 */

$product = [ [ 'rank_math_rich_snippet', 'product' ] ];

$cmb->add_field([
	'id'   => 'rank_math_snippet_product_sku',
	'type' => 'text',
	'name' => esc_html__( 'Product SKU', 'rank-math' ),
	'dep'  => $product,
]);

$cmb->add_field([
	'id'   => 'rank_math_snippet_product_brand',
	'type' => 'text',
	'name' => esc_html__( 'Product Brand', 'rank-math' ),
	'dep'  => $product,
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_product_currency',
	'type'       => 'text',
	'name'       => esc_html__( 'Product Currency', 'rank-math' ),
	'desc'       => esc_html__( 'ISO 4217 Currency Code', 'rank-math' ),
	'classes'    => 'rank-math-validate-field',
	'attributes' => [
		'data-rule-regex'       => 'true',
		'data-validate-pattern' => '^[A-Z]{3}$',
		'data-msg-regex'        => esc_html__( 'Please use the correct format. Example: EUR', 'rank-math' ),
	],
	'dep'        => $product,
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_product_price',
	'type'       => 'text',
	'name'       => esc_html__( 'Product Price', 'rank-math' ),
	'dep'        => $product,
	'attributes' => [ 'type' => 'number' ],
]);

$cmb->add_field([
	'id'          => 'rank_math_snippet_product_price_valid',
	'type'        => 'text_date',
	'date_format' => 'Y-m-d',
	'name'        => esc_html__( 'Price Valid Until', 'rank-math' ),
	'desc'        => esc_html__( 'The date after which the price will no longer be available.', 'rank-math' ),
	'dep'         => $product,
]);

$cmb->add_field([
	'id'      => 'rank_math_snippet_product_instock',
	'type'    => 'switch',
	'name'    => esc_html__( 'Product In-Stock', 'rank-math' ),
	'dep'     => $product,
	'classes' => 'nob',
	'default' => 'on',
]);
