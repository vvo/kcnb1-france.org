<?php
/**
 * The public-facing template tags.
 *
 * @package    RankMath
 * @subpackage RankMath\Frontend
 */

/**
 * Get breadcrumbs.
 *
 * @param array $args Array of arguments.
 * @return string Breadcrumbs HTML output
 */
function rank_math_get_breadcrumbs( $args = array() ) {
	return isset( rank_math()->breadcrumbs ) ? rank_math()->breadcrumbs->get_breadcrumb( $args ) : '';
}

/**
 * Output breadcrumbs.
 *
 * @param array $args Array of arguments.
 */
function rank_math_the_breadcrumbs( $args = array() ) {
	echo rank_math_get_breadcrumbs( $args );
}

/**
 * Get sitemap url.
 *
 * @return string
 */
function rank_math_get_sitemap_url() {
	return \RankMath\Sitemap\Router::get_base_url( 'sitemap_index.xml' );
}
