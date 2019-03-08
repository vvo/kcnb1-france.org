<?php
/**
 * Metabox - Advance Tab
 *
 * @package    RankMath
 * @subpackage RankMath\Metaboxes
 */

use RankMath\Helper;

$cmb->add_field( array(
	'id'                => 'rank_math_robots',
	'type'              => 'multicheck',
	'name'              => esc_html__( 'Robots Meta', 'rank-math' ),
	'desc'              => esc_html__( 'Custom values for robots meta tag.', 'rank-math' ),
	'options'           => Helper::choices_robots(),
	'select_all_button' => false,
) );

$cmb->add_field( array(
	'id'   => 'rank_math_canonical_url',
	'type' => 'text',
	'name' => esc_html__( 'Canonical URL', 'rank-math' ),
	'desc' => esc_html__( 'The canonical URL informs search crawlers which page is the main page if you have double content.', 'rank-math' ),
) );

if ( Helper::get_settings( 'general.breadcrumbs' ) ) {
	$cmb->add_field( array(
		'id'   => 'rank_math_breadcrumb_title',
		'type' => 'text',
		'name' => esc_html__( 'Breadcrumb Title', 'rank-math' ),
		'desc' => esc_html__( 'Breadcrumb Title to use for this post', 'rank-math' ),
	) );
}
