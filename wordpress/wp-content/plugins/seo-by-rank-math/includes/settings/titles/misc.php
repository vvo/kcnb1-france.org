<?php
/**
 * The misc settings.
 *
 * @package    RankMath
 * @subpackage RankMath\Settings
 */

$dep = array( array( 'disable_date_archives', 'off' ) );

$cmb->add_field( array(
	'id'      => 'disable_date_archives',
	'type'    => 'switch',
	'name'    => esc_html__( 'Date Archives', 'rank-math' ),
	'desc'    => esc_html__( 'Redirect date archives to homepage.', 'rank-math' ),
	'options' => array(
		'off' => esc_html__( 'Enabled', 'rank-math' ),
		'on'  => esc_html__( 'Disabled', 'rank-math' ),
	),
	'default' => 'off',
) );

$cmb->add_field( array(
	'id'              => 'date_archive_title',
	'type'            => 'text',
	'name'            => esc_html__( 'Date Archive Title', 'rank-math' ),
	'desc'            => esc_html__( 'Title tag on day/month/year based archives.', 'rank-math' ),
	'classes'         => 'rank-math-supports-variables rank-math-title',
	'default'         => '%date% %page% %sep% %sitename%',
	'dep'             => $dep,
	'sanitization_cb' => false,
) );

$cmb->add_field( array(
	'id'              => 'date_archive_description',
	'type'            => 'textarea_small',
	'name'            => esc_html__( 'Date Archive Description', 'rank-math' ),
	'desc'            => esc_html__( 'Date archive description.', 'rank-math' ),
	'classes'         => 'rank-math-supports-variables rank-math-description',
	'dep'             => $dep,
	'sanitization_cb' => false,
) );

$cmb->add_field( array(
	'id'              => 'search_title',
	'type'            => 'text',
	'name'            => esc_html__( 'Search Results Title', 'rank-math' ),
	'desc'            => esc_html__( 'Title tag on search results page.', 'rank-math' ),
	'classes'         => 'rank-math-supports-variables rank-math-title',
	'default'         => '%search_query% %page% %sep% %sitename%',
	'sanitization_cb' => false,
) );

$cmb->add_field( array(
	'id'              => '404_title',
	'type'            => 'text',
	'name'            => esc_html__( '404 Title', 'rank-math' ),
	'desc'            => esc_html__( 'Title tag on 404 Not Found error page.', 'rank-math' ),
	'classes'         => 'rank-math-supports-variables rank-math-title',
	'default'         => 'Page Not Found %sep% %sitename%',
	'sanitization_cb' => false,
) );

$cmb->add_field( array(
	'id'      => 'noindex_date',
	'type'    => 'switch',
	'name'    => esc_html__( 'Noindex Date Archives', 'rank-math' ),
	'desc'    => esc_html__( 'Prevent date archives from getting indexed by search engines.', 'rank-math' ),
	'default' => 'on',
) );

$cmb->add_field( array(
	'id'      => 'noindex_search',
	'type'    => 'switch',
	'name'    => esc_html__( 'Noindex Search Results', 'rank-math' ),
	'desc'    => esc_html__( 'Prevent search results pages from getting indexed by search engines. Search results could be considered to be thin content and prone to duplicate content issues.', 'rank-math' ),
	'default' => 'on',
) );

$cmb->add_field( array(
	'id'      => 'noindex_paginated_pages',
	'type'    => 'switch',
	'name'    => esc_html__( 'Noindex Paginated Pages', 'rank-math' ),
	'desc'    => wp_kses_post( __( 'Set this to on to prevent /page/2 and further of any archive to show up in the search results.', 'rank-math' ) ),
	'default' => 'off',
) );

$cmb->add_field( array(
	'id'      => 'noindex_archive_subpages',
	'type'    => 'switch',
	'name'    => esc_html__( 'Noindex Archive Subpages', 'rank-math' ),
	'desc'    => esc_html__( 'Prevent paginated archive pages from getting indexed by search engines.', 'rank-math' ),
	'default' => 'off',
) );

$cmb->add_field( array(
	'id'      => 'noindex_password_protected',
	'type'    => 'switch',
	'name'    => esc_html__( 'Noindex Password Protected Pages', 'rank-math' ),
	'desc'    => esc_html__( 'Prevent password protected pages & posts from getting indexed by search engines.', 'rank-math' ),
	'default' => 'off',
) );
