<?php
/**
 * The misc settings.
 *
 * @package    RankMath
 * @subpackage RankMath\Settings
 */

use RankMath\KB;

$cmb->add_field( array(
	'id'      => 'usage_tracking',
	'type'    => 'switch',
	'name'    => esc_html__( 'Usage Tracking', 'rank-math' ),
	'desc'    => esc_html__( 'Help make Rank Math even more powerful by allowing us to collect non-sensitive diagnostic data and usage information.', 'rank-math' ) . ' <a href="' . KB::get( 'rm-privacy' ) . '" target="_blank">' . esc_html__( 'Find out more.', 'rank-math' ) . '</a>',
	'default' => 'on',
) );

$cmb->add_field( array(
	'id'              => 'rss_before_content',
	'type'            => 'textarea_small',
	'name'            => esc_html__( 'RSS Before Content', 'rank-math' ),
	'desc'            => esc_html__( 'Add content before each post in your site feeds.', 'rank-math' ),
	'sanitization_cb' => false,
) );

$cmb->add_field( array(
	'id'              => 'rss_after_content',
	'type'            => 'textarea_small',
	'name'            => esc_html__( 'RSS After Content', 'rank-math' ),
	'desc'            => esc_html__( 'Add content after each post in your site feeds.', 'rank-math' ),
	'sanitization_cb' => false,
) );

$cmb->add_field( array(
	'id'              => 'rss_after_content',
	'type'            => 'textarea_small',
	'name'            => esc_html__( 'RSS After Content', 'rank-math' ),
	'desc'            => esc_html__( 'Add content after each post in your site feeds.', 'rank-math' ),
	'classes'         => 'nob',
	'sanitization_cb' => false,
) );

$cmb->add_field( array(
	'id'   => 'rank_math_serp_preview',
	'type' => 'raw',
	'file' => rank_math()->includes_dir() . 'settings/general/rss-vars-table.php',
) );
