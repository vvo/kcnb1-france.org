<?php
/**
 * The Helper class that provides easy access to accessing params from $_GET, $_POST and $_REQUEST.
 *
 * @since      1.0.0
 * @package    MyThemeShop
 * @subpackage MyThemeShop\Helpers
 * @author     MyThemeShop <admin@mythemeshop.com>
 */

namespace MyThemeShop\Helpers;

/**
 * Param class.
 */
class Param {

	/**
	 * Get field from query string.
	 *
	 * @param string $id      Field id to get.
	 * @param mixed  $default Default value to return if field is not found.
	 * @param int    $filter  The ID of the filter to apply.
	 *
	 * @return mixed
	 */
	public static function get( $id, $default = false, $filter = FILTER_DEFAULT ) {
		return filter_has_var( INPUT_GET, $id ) ? filter_input( INPUT_GET, $id, $filter ) : $default;
	}

	/**
	 * Get field from FORM post.
	 *
	 * @param string $id      Field id to get.
	 * @param mixed  $default Default value to return if field is not found.
	 * @param int    $filter  The ID of the filter to apply.
	 *
	 * @return mixed
	 */
	public static function post( $id, $default = false, $filter = FILTER_DEFAULT ) {
		return filter_has_var( INPUT_POST, $id ) ? filter_input( INPUT_POST, $id, $filter ) : $default;
	}

	/**
	 * Get field from request.
	 *
	 * @param string $id      Field id to get.
	 * @param mixed  $default Default value to return if field is not found.
	 * @param int    $filter  The ID of the filter to apply.
	 *
	 * @return mixed
	 */
	public static function request( $id, $default = false, $filter = FILTER_DEFAULT ) {
		return isset( $_REQUEST[ $id ] ) ? filter_var( $_REQUEST[ $id ], $filter ) : $default;
	}

	/**
	 * Get field from FORM server.
	 *
	 * @param string $id      Field id to get.
	 * @param mixed  $default Default value to return if field is not found.
	 * @param int    $filter  The ID of the filter to apply.
	 *
	 * @return mixed
	 */
	public static function server( $id, $default = false, $filter = FILTER_DEFAULT ) {
		return filter_has_var( INPUT_SERVER, $id ) ? filter_input( INPUT_SERVER, $id, $filter ) : $default;
	}
}
