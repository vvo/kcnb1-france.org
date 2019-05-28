<?php
/**
 * The authors settings.
 *
 * @package    RankMath
 * @subpackage RankMath\Settings
 */

use RankMath\Helper;

$dep = [ [ 'disable_author_archives', 'off' ] ];

$cmb->add_field([
	'id'      => 'disable_author_archives',
	'type'    => 'switch',
	'name'    => esc_html__( 'Author Archives', 'rank-math' ),
	'desc'    => esc_html__( 'Redirect author archives to homepage. Useful for single-author blogs, where the author archive shows the same posts as the homepage/blog page. Alternatively, you can set the author archives to noindex.', 'rank-math' ),
	'options' => [
		'on'  => esc_html__( 'Disabled', 'rank-math' ),
		'off' => esc_html__( 'Enabled', 'rank-math' ),
	],
	'default' => $this->do_filter( 'settings/titles/disable_author_archives', 'off' ),
]);

$cmb->add_field([
	'id'      => 'url_author_base',
	'type'    => 'text',
	'name'    => esc_html__( 'Author Base', 'rank-math' ),
	'desc'    => wp_kses_post( __( 'Change the <code>/author/</code> part in author archive URLs.', 'rank-math' ) ),
	'default' => 'author',
	'dep'     => $dep,
]);

$cmb->add_field([
	'id'      => 'author_custom_robots',
	'type'    => 'switch',
	'name'    => esc_html__( 'Author Robots Meta', 'rank-math' ),
	'desc'    => wp_kses_post( __( 'Select custom robots meta for author page, such as <code>nofollow</code>, <code>noarchive</code>, etc. Otherwise the default meta will be used, as set in the Global Meta tab.', 'rank-math' ) ),
	'options' => [
		'off' => esc_html__( 'Default', 'rank-math' ),
		'on'  => esc_html__( 'Custom', 'rank-math' ),
	],
	'default' => 'on',
	'dep'     => $dep,
]);

$cmb->add_field([
	'id'                => 'author_robots',
	'type'              => 'multicheck',
	/* translators: post type name */
	'name'              => esc_html__( 'Author Robots Meta', 'rank-math' ),
	'desc'              => esc_html__( 'Custom values for robots meta tag on author page.', 'rank-math' ),
	'options'           => Helper::choices_robots(),
	'select_all_button' => false,
	'dep'               => [
		'relation' => 'and',
		[ 'author_custom_robots', 'on' ],
		[ 'disable_author_archives', 'off' ],
	],
]);

$cmb->add_field([
	'id'              => 'author_archive_title',
	'type'            => 'text',
	'name'            => esc_html__( 'Author Archive Title', 'rank-math' ),
	'desc'            => esc_html__( 'Title tag on author archives. SEO options for specific authors can be set with the meta box available in the user profiles.', 'rank-math' ),
	'classes'         => 'rank-math-supports-variables rank-math-title',
	'default'         => '%name% %sep% %sitename% %page%',
	'dep'             => $dep,
	'sanitization_cb' => false,
]);

$cmb->add_field([
	'id'              => 'author_archive_description',
	'type'            => 'textarea_small',
	'name'            => esc_html__( 'Author Archive Description', 'rank-math' ),
	'desc'            => esc_html__( 'Author archive meta description. SEO options for specific author archives can be set with the meta box in the user profiles.', 'rank-math' ),
	'classes'         => 'rank-math-supports-variables rank-math-description',
	'dep'             => $dep,
	'sanitization_cb' => false,
]);

$cmb->add_field([
	'id'      => 'author_add_meta_box',
	'type'    => 'switch',
	'name'    => esc_html__( 'Add SEO Meta Box for Users', 'rank-math' ),
	'desc'    => esc_html__( 'Add SEO Meta Box for user profile pages. Access to the Meta Box can be fine tuned with code, using a special filter hook.', 'rank-math' ),
	'default' => 'on',
	'dep'     => $dep,
]);
