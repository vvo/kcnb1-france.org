<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Common validator class.
 * 
 * This class provides functions to validate and sanitize general data used in the plugin.
 */
class ADBC_Common_Validator {

	/**
	 * Sanitize the DataTable filters sent by the user.
	 *
	 * @param WP_REST_Request $filters_request The filters request.
	 * 
	 * @return array The sanitized filters.
	 */
	public static function sanitize_filters( WP_REST_Request $filters_request ) {

		$sanitized_filters = [ 
			'size' => sanitize_key( $filters_request->get_param( 'size' ) ),
			'size_unit' => sanitize_text_field( $filters_request->get_param( 'sizeUnit' ) ), // KB, MB, GB
			'table_status' => sanitize_key( $filters_request->get_param( 'tableStatus' ) ),
			'prefix_status' => sanitize_key( $filters_request->get_param( 'prefixStatus' ) ),
			'belongs_to' => sanitize_key( $filters_request->get_param( 'belongsTo' ) ),
			'current_page' => sanitize_key( $filters_request->get_param( 'currentPage' ) ),
			'items_per_page' => sanitize_key( $filters_request->get_param( 'itemsPerPage' ) ),
			'sort_by' => sanitize_text_field( $filters_request->get_param( 'sortBy' ) ),
			'sort_order' => sanitize_text_field( $filters_request->get_param( 'sortOrder' ) ),
			'items_type' => $filters_request->get_param( 'itemsType' ),
			'expired' => sanitize_key( $filters_request->get_param( 'expired' ) ),
			'duplicated' => sanitize_key( $filters_request->get_param( 'duplicated' ) ),
			'unused' => sanitize_key( $filters_request->get_param( 'unused' ) ),
			'has_action' => sanitize_key( $filters_request->get_param( 'hasAction' ) ),
			'search_for' => $filters_request->get_param( 'search' ), // Don't sanitize this to not break the search query. We assure security later.
			'search_in' => sanitize_key( $filters_request->get_param( 'searchIn' ) ),
			'site_id' => sanitize_key( $filters_request->get_param( 'site' ) ),
			'belongs_to_plugin_slug' => sanitize_text_field( $filters_request->get_param( 'belongsToPluginSlug' ) ),
			'belongs_to_theme_slug' => sanitize_text_field( $filters_request->get_param( 'belongsToThemeSlug' ) ),
			'show_manual_corrections_only' => $filters_request->get_param( 'showManualCorrectionsOnly' ),
			'autoload' => sanitize_key( $filters_request->get_param( 'autoload' ) ),
			'start_date' => $filters_request->get_param( 'startDate' ),
			'end_date' => $filters_request->get_param( 'endDate' ),
			'frequency' => sanitize_key( $filters_request->get_param( 'frequency' ) ),
			'interval' => $filters_request->get_param( 'interval' ),
			'post_types_posts_count' => sanitize_key( $filters_request->get_param( 'postTypesPostsCount' ) ),
			'post_types_visibility' => sanitize_key( $filters_request->get_param( 'postTypesVisibility' ) ),
		];

		// Sanitize and validate common filters
		$sanitized_filters['size'] = absint( $sanitized_filters['size'] );
		$sanitized_filters['size_unit'] = in_array( $sanitized_filters['size_unit'], [ 'B', 'KB', 'MB', 'GB' ] ) ? $sanitized_filters['size_unit'] : 'KB';
		$sanitized_filters['table_status'] = in_array( $sanitized_filters['table_status'], [ 'all', 'to_optimize', 'to_repair' ] ) ? $sanitized_filters['table_status'] : 'all';
		$sanitized_filters['prefix_status'] = in_array( $sanitized_filters['prefix_status'], [ 'all', 'valid_prefix', 'invalid_prefix' ] ) ? $sanitized_filters['prefix_status'] : 'all';
		$sanitized_filters['belongs_to'] =
			in_array( $sanitized_filters['belongs_to'], [ 'all', 'not_scanned', 'plugins', 'themes', 'wordpress', 'orphans', 'unknown' ] ) ? $sanitized_filters['belongs_to'] : 'all';
		$sanitized_filters['current_page'] = self::sanitize_validate_current_page( $sanitized_filters['current_page'] );
		$sanitized_filters['items_per_page'] = self::sanitize_validate_limit( $sanitized_filters['items_per_page'] );
		// $sanitized_filters['sort_by'] SQL queries will check if the column exists.
		$sanitized_filters['sort_order'] = in_array( $sanitized_filters['sort_order'], [ 'ASC', 'DESC' ] ) ? $sanitized_filters['sort_order'] : 'ASC';
		$sanitized_filters['items_type'] = self::sanitize_items_type( $sanitized_filters['items_type'] );
		$sanitized_filters['expired'] = in_array( $sanitized_filters['expired'], [ 'all', 'yes', 'no' ], true ) ? $sanitized_filters['expired'] : 'all';
		$sanitized_filters['duplicated'] = in_array( $sanitized_filters['duplicated'], [ 'all', 'yes', 'no' ], true ) ? $sanitized_filters['duplicated'] : 'all';
		$sanitized_filters['unused'] = in_array( $sanitized_filters['unused'], [ 'all', 'yes', 'no' ], true ) ? $sanitized_filters['unused'] : 'all';
		$sanitized_filters['has_action'] = in_array( $sanitized_filters['has_action'], [ 'all', 'yes', 'no' ], true ) ? $sanitized_filters['has_action'] : 'all';
		$sanitized_filters['autoload'] = in_array( $sanitized_filters['autoload'], [ 'all', 'yes', 'no' ] ) ? $sanitized_filters['autoload'] : 'all';

		// In free set premium filters to default values since they are not supported in free version.
		if ( ADBC_VERSION_TYPE === "FREE" ) {
			$sanitized_filters['search_for'] = '';
			$sanitized_filters['search_in'] = 'name';
			$sanitized_filters['site_id'] = 'all';
			$sanitized_filters['belongs_to_plugin_slug'] = '';
			$sanitized_filters['belongs_to_theme_slug'] = '';
			$sanitized_filters['show_manual_corrections_only'] = false;
			$sanitized_filters['start_date'] = null;
			$sanitized_filters['end_date'] = null;
			$sanitized_filters['frequency'] = 'all';
			$sanitized_filters['interval'] = 'all';
			$sanitized_filters['post_types_posts_count'] = 0;
			$sanitized_filters['post_types_visibility'] = 'all';
		} else {
			// In premium, sanitize the premium filters.
			ADBC_Premium_Common_Validator::sanitize_filters( $sanitized_filters );
		}

		return $sanitized_filters;

	}

	/**
	 * Checks if the given value equals "0" or "1".
	 *
	 * @param string $key The key of the value to check (not used in this method).
	 * @param string $value The value to check.
	 * @return bool True if the value equals "0" or "1", false otherwise.
	 */
	public static function is_string_equals_0_or_1( $key, $value ) {
		return ( $value === "0" || $value === "1" );
	}

	/**
	 * Checks if the given value is a valid number that is between the specified min and max values.
	 *
	 * @param string $value The value to check.
	 * @param int $min The minimum value.
	 * @param int $max The maximum value.
	 * @return bool True if the value is a number between min and max, false otherwise.
	 */
	public static function is_number_between_min_and_max( $value, $min, $max ) {
		return ( is_numeric( $value ) && $value >= $min && $value <= $max );
	}

	/**
	 * Validates the action data for the given action and items type sent by the user.
	 *
	 * @param string $action The action to validate (e.g., 'optimize_tables', 'delete_options'...).
	 * @param string $items_type The type of items (e.g., 'tables', 'options'...).
	 * @param WP_REST_Request $request_data The request data containing the action and selected items, etc. sent by the user to the endpoint.
	 * @param bool $keep_prefix Whether to keep the prefix in the returned table names (used only for tables).
	 * 
	 * @return array|string An array of valid items or an error message if validation fails.
	 */
	public static function validate_endpoint_action_data( $action, $items_type, $request_data, $keep_prefix = true ) {

		// Get params
		$action_type = $request_data->get_param( 'actionType' );
		$selected_items = $request_data->get_param( 'selectedItems' );

		// Check action is valid
		if ( $action_type !== $action )
			return "Invalid action.";

		// Delete invalid items from the selected tables and return valid ones.
		$selected_items = ADBC_Selected_Items_Validator::remove_invalid_selected_items( $items_type, $selected_items, $keep_prefix );

		// Check empty.
		if ( empty( $selected_items ) )
			return "No items to process";

		return $selected_items;
	}

	/**
	 * Validates the data sent by the user to get_column_value_from_table endpoint.
	 *
	 * @param string $items_type The type of items (e.g., 'options', 'transients').
	 * @param int $site_id The site ID.
	 * @param int $row_id The row ID to get the value from.
	 * @param string $transient_found_in The transient found in, if applicable (e.g., 'options', 'sitemeta').
	 * 
	 * @return array|string An array with success status, message, and data or an error message if validation fails.
	 */
	public static function validate_get_column_value_endpoint_data( $items_type, $site_id, $row_id, $transient_found_in ) {

		$answer = [ "success" => false, "message" => "", "data" => []];

		// Check items type is valid
		if ( ! in_array( $items_type, [ 'options', 'transients', 'posts_meta', 'users_meta', 'revisions', 'auto_drafts', 'trashed_posts', 'unapproved_comments', 'spam_comments', 'trashed_comments', 'pingbacks', 'trackbacks', 'unused_postmeta', 'duplicated_postmeta', 'unused_commentmeta', 'duplicated_commentmeta', 'unused_usermeta', 'duplicated_usermeta', 'unused_termmeta', 'duplicated_termmeta', 'unused_relationships', 'expired_transients', 'oembed_caches', 'actionscheduler_completed_actions', 'actionscheduler_failed_actions', 'actionscheduler_canceled_actions', 'actionscheduler_completed_logs', 'actionscheduler_failed_logs', 'actionscheduler_canceled_logs', 'actionscheduler_orphan_logs', 'woocommerce_orphaned_customer_analytics', 'woocommerce_orphaned_product_variations' ], true ) )
			$answer['message'] = "Invalid items type.";

		// Check site ID is valid
		if ( ! is_numeric( $site_id ) || $site_id < 0 )
			$answer['message'] = "Invalid site ID.";

		// Check row ID is valid
		if ( ! is_numeric( $row_id ) || $row_id < 0 )
			$answer['message'] = "Invalid row ID.";

		// Check if transient_found_in is valid
		if ( $items_type === 'expired_transients' && ! in_array( $transient_found_in, [ 'options', 'sitemeta' ], true ) )
			$answer['message'] = "Invalid transient_found_in parameter.";

		// Get the site prefix
		$site_prefix = ADBC_Sites::instance()->get_prefix_from_site_id( $site_id );
		if ( $site_prefix === null )
			$answer['message'] = "Cannot find the site prefix.";

		// Check if there is an error
		if ( ! empty( $answer['message'] ) )
			return $answer;

		// Prepare the table name and column names based on the items type
		switch ( $items_type ) {
			case 'options':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'options',
					'column_id' => "option_id",
					'column_name' => "option_value"
				];
				break;

			case 'posts_meta':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'postmeta',
					'column_id' => "meta_id",
					'column_name' => "meta_value"
				];
				break;

			case 'users_meta':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'usermeta',
					'column_id' => "umeta_id",
					'column_name' => "meta_value"
				];
				break;

			case 'revisions':
			case 'auto_drafts':
			case 'trashed_posts':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'posts',
					'column_id' => "ID",
					'column_name' => "post_content"
				];
				break;

			case 'unapproved_comments':
			case 'spam_comments':
			case 'trashed_comments':
			case 'pingbacks':
			case 'trackbacks':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'comments',
					'column_id' => "comment_ID",
					'column_name' => "comment_content"
				];
				break;

			case 'unused_postmeta':
			case 'duplicated_postmeta':
			case 'oembed_caches':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'postmeta',
					'column_id' => "meta_id",
					'column_name' => "meta_value"
				];
				break;

			case 'unused_commentmeta':
			case 'duplicated_commentmeta':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'commentmeta',
					'column_id' => "meta_id",
					'column_name' => "meta_value"
				];
				break;

			case 'unused_termmeta':
			case 'duplicated_termmeta':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'termmeta',
					'column_id' => "meta_id",
					'column_name' => "meta_value"
				];
				break;

			case 'unused_usermeta':
			case 'duplicated_usermeta':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'usermeta',
					'column_id' => "umeta_id",
					'column_name' => "meta_value"
				];
				break;

			case 'unused_relationships':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'term_relationships',
					'column_id' => "object_id",
					'column_name' => "term_order"
				];
				break;

			case 'expired_transients':
			case 'transients':
				switch ( $transient_found_in ) {
					case 'sitemeta':
						$answer['data'] = [ 
							'table_name' => $site_prefix . 'sitemeta',
							'column_id' => "meta_id",
							'column_name' => "meta_value"
						];
						break;
					case 'options':
						$answer['data'] = [ 
							'table_name' => $site_prefix . 'options',
							'column_id' => "option_id",
							'column_name' => "option_value"
						];
						break;
				}
				break;

			case 'actionscheduler_completed_actions':
			case 'actionscheduler_failed_actions':
			case 'actionscheduler_canceled_actions':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'actionscheduler_actions',
					'column_id' => "action_id",
					'column_name' => "args"
				];
				break;
			case 'actionscheduler_completed_logs':
			case 'actionscheduler_failed_logs':
			case 'actionscheduler_canceled_logs':
			case 'actionscheduler_orphan_logs':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'actionscheduler_logs',
					'column_id' => "log_id",
					'column_name' => "message"
				];
				break;
			case 'woocommerce_orphaned_customer_analytics':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'wc_customer_lookup',
					'column_id' => "customer_id",
					'column_name' => "email"
				];
				break;
			case 'woocommerce_orphaned_product_variations':
				$answer['data'] = [ 
					'table_name' => $site_prefix . 'posts',
					'column_id' => "ID",
					'column_name' => "post_content"
				];
				break;
		}

		$answer['success'] = true;
		return $answer;
	}

	/**
	 * Validates two optional dates for use as REST filter parameters.
	 *
	 * If only one date is valid, it is returned while the other slot is `null`.
	 * If both dates are present they must be chronological and, when `$max_days`
	 * is > 0, their span must not exceed that limit.
	 *
	 * @param string|null $start_date The start date in the specified format.
	 * @param string|null $end_date The end date in the specified format.
	 * @param string $date_format The date format to use for parsing the dates.
	 * @param int $max_days The maximum number of days allowed between the start and end dates.
	 * 
	 * @return array [ start|null, end|null ]
	 */
	public static function validate_filter_date_range( $start_date = null, $end_date = null, $date_format = 'Y-m-d', $max_days = 0 ) {

		$result = [ null, null ];
		$start_obj = null;
		$end_obj = null;

		/*— individually validate ------------------------------------------------*/
		if ( ! empty( $start_date ) ) {
			$start_obj = ADBC_Common_Utils::parse_date( $start_date, $date_format );
			if ( $start_obj ) {
				$result[0] = $start_obj->format( $date_format );
			}
		}
		if ( ! empty( $end_date ) ) {
			$end_obj = ADBC_Common_Utils::parse_date( $end_date, $date_format );
			if ( $end_obj ) {
				$result[1] = $end_obj->format( $date_format );
			}
		}

		/*— both dates supplied? apply cross checks ------------------------------*/
		if ( $start_obj && $end_obj ) {
			// chronology
			if ( $start_obj > $end_obj ) {
				return [ null, null ];
			}
			// length restriction
			if ( $max_days > 0 && $start_obj->diff( $end_obj )->days > $max_days ) {
				return [ null, null ];
			}
		}

		return $result;

	}

	/**
	 * Validates a *complete* date range. Anything off → `[null, null]`.
	 *
	 * Use when the caller must provide both dates and (optionally) stay within
	 * `$max_days`.
	 *
	 * @param string $start_date The start date in the specified format.
	 * @param string $end_date The end date in the specified format.
	 * @param string $date_format The date format to use for parsing the dates.
	 * @param int $max_days The maximum number of days allowed between the start and end dates.
	 * 
	 * @return array [ start|null, end|null ]
	 */
	public static function validate_strict_date_range( $start_date, $end_date, $date_format = 'Y-m-d', $max_days = 0 ) {

		$invalid = [ null, null ];

		/*— both dates must be present ------------------------------------------*/
		if ( empty( $start_date ) || empty( $end_date ) ) {
			return $invalid;
		}

		$start_obj = ADBC_Common_Utils::parse_date( $start_date, $date_format );
		$end_obj = ADBC_Common_Utils::parse_date( $end_date, $date_format );

		if ( ! $start_obj || ! $end_obj ) {
			return $invalid;
		}

		/*— chronology and (optional) length ------------------------------------*/
		if ( $start_obj > $end_obj ) {
			return $invalid;
		}
		if ( $max_days > 0 && $start_obj->diff( $end_obj )->days > $max_days ) {
			return $invalid;
		}

		return [ 
			$start_obj->format( $date_format ),
			$end_obj->format( $date_format ),
		];

	}

	/**
	 * Sanitize the items type sent by the user.
	 * 
	 * @param string $items_type The items type.
	 * 
	 * @return string The sanitized items type, empty string if invalid.
	 */
	public static function sanitize_items_type( $items_type ) {

		$items_type = sanitize_key( $items_type );

		$valid_items = array_merge(
			[ 
				'options',
				'tables',
				'cron_jobs',
				'transients',
				'posts_meta',
				'users_meta',
				'post_types',
			],
			ADBC_Cleanup_Type_Registry::get_all_items_type()
		);

		$valid_items_type = in_array( $items_type, $valid_items, true );

		return $valid_items_type ? $items_type : '';

	}

	/**
	 * Sanitize an array of items types sent by the user.
	 * 
	 * @param array $items_types The array of items types.
	 * 
	 * @return array The sanitized array of items types, empty array if all invalid.
	 */
	public static function sanitize_items_types( $items_types ) {

		if ( ! is_array( $items_types ) ) {
			return [];
		}

		$validated_items_types = [];

		foreach ( $items_types as $item_type ) {
			$sanitized = self::sanitize_items_type( $item_type );
			if ( $sanitized !== '' ) {
				$validated_items_types[] = $sanitized;
			}
		}

		return $validated_items_types;

	}

	/**
	 * Validate the manual categorization sent by the user.
	 *
	 * @param string $manual_categorization The manual categorization.
	 * @return string|bool The error message if the manual categorization is invalid, true otherwise.
	 */
	public static function is_manual_categorization_valid( $manual_categorization ) {

		$generic_error_msg = "Invalid manual correction.";

		// Check if three keys exist in the manual categorization associative array: type, slug and send_to_server
		if ( ! is_array( $manual_categorization ) ||
			! key_exists( 'type', $manual_categorization ) ||
			! key_exists( 'slug', $manual_categorization ) ||
			! key_exists( 'send_to_server', $manual_categorization ) )
			return $generic_error_msg . ' #1';

		$correction_category = $manual_categorization['type'];
		if ( ! is_string( $correction_category ) || ! in_array( $correction_category, [ 'p', 't', 'w', 'o', 'u' ], true ) )
			return $generic_error_msg . ' #2';

		$slug = $manual_categorization['slug'];
		if ( ! is_string( $slug ) )
			return $generic_error_msg . ' #3';

		$is_custom = key_exists( 'is_custom', $manual_categorization );
		if ( $is_custom && ! in_array( $manual_categorization['is_custom'], [ true, 1, '1' ], true ) )
			return $generic_error_msg . ' #7';

		if ( $is_custom ) {

			// Custom addons are stored as regular plugin/theme manual corrections, but do not need to be installed.
			if ( ! in_array( $correction_category, [ 'p', 't' ], true ) )
				return $generic_error_msg . ' #8';

			// WordPress-compatible slugs: lowercase ASCII letters, numbers, hyphens and underscores.
			if ( strlen( $slug ) > 200 || preg_match( '/^[a-z0-9_-]+$/', $slug ) !== 1 )
				return $generic_error_msg . ' #9';

			if ( ! key_exists( 'name', $manual_categorization ) || ! is_string( $manual_categorization['name'] ) )
				return $generic_error_msg . ' #10';

			$addon_name = trim( $manual_categorization['name'] );
			$addon_name_length = preg_match_all( '/./us', $addon_name ); // Count the number of Unicode characters in the string.
			if ( $addon_name === '' || $addon_name_length === false || $addon_name_length > 200 || preg_match( '/[\x00-\x1F\x7F]/', $addon_name ) )
				return $generic_error_msg . ' #11';

		} else {

			if ( in_array( $correction_category, [ 'w', 'o', 'u' ], true ) && ! in_array( $slug, [ 'w', 'o', 'u' ], true ) )
				return $generic_error_msg . ' #3';

			if ( $correction_category === 'p' && ! ADBC_Plugins::instance()->is_plugin_slug_currently_installed( $slug ) )
				return $generic_error_msg . ' #4';

			if ( $correction_category === 't' && ! ADBC_Themes::instance()->is_theme_slug_currently_installed( $slug ) )
				return $generic_error_msg . ' #5';

		}

		$send_correction_to_server = $manual_categorization['send_to_server'];

		if ( ! in_array( $send_correction_to_server, [ '0', '1' ], true ) )
			return $generic_error_msg . ' #6';

		return true;
	}

	/**
	 * Validate the offset sent by the user.
	 * 
	 * @param int $offset The offset.
	 * 
	 * @return int The sanitized offset, 0 if invalid.
	 */
	public static function sanitize_validate_offset( $offset ) {

		// Sanitize the offset
		$offset = absint( $offset );

		if ( $offset < 0 )
			return 0;

		return $offset;

	}

	/**
	 * Validate the limit sent by the user.
	 * 
	 * @param int $limit The limit.
	 * 
	 * @return int The sanitized limit, 50 if invalid and 1000 max.
	 */
	public static function sanitize_validate_limit( $limit ) {

		// Sanitize the limit
		$limit = absint( $limit );

		// Limit the limit to 50 if less than 1
		if ( $limit < 1 )
			return 50;

		// Limit the limit to 1000
		if ( $limit > 1000 )
			return 1000;

		return $limit;

	}

	/**
	 * Sanitize and validate the current page sent by the user.
	 * 
	 * @param int $current_page The current page.
	 * 
	 * @return int The sanitized current page, 1 if invalid.
	 */
	public static function sanitize_validate_current_page( $current_page ) {

		// Sanitize the current page
		$current_page = absint( $current_page );

		// If current page is less than 1, set it to 1
		if ( $current_page < 1 )
			return 1;

		return $current_page;

	}

}