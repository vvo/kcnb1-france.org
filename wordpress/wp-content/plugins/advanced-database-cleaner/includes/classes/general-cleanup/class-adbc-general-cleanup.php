<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC General Cleanup Class
 * 
 * This class provides methods for managing general cleanup operations
 */
class ADBC_General_Cleanup {

	/**
	 * Get the general data for all items types, a specific type, or an array of types.
	 *
	 * @param string|array|null $items_type The type(s) of items to get data for:
	 *                                      - Empty string or null for all types
	 *                                      - String for a single type
	 *                                      - Array of strings for multiple types
	 * 
	 * @return array An array containing the items data, total size, and total count.
	 */
	public static function get_general_data( $items_type = '' ) {

		$keep_last = self::get_keep_last();

		$items = [];
		$total_size = 0;
		$total_count = 0;

		// Determine which handlers to process.
		if ( $items_type === '' || $items_type === null ) {
			// Get all handlers
			$types_to_process = ADBC_Cleanup_Type_Registry::all_handlers();
		} elseif ( is_array( $items_type ) ) {
			// Get handlers for the specified array of types
			$types_to_process = [];
			foreach ( $items_type as $type ) {
				$handler = ADBC_Cleanup_Type_Registry::handler( $type );
				if ( $handler ) {
					$types_to_process[ $type ] = $handler;
				}
			}
		} else {
			// Single type (string) - backward compatibility
			$types_to_process = [ $items_type => ADBC_Cleanup_Type_Registry::handler( $items_type ) ];
		}

		foreach ( $types_to_process as $type => $handler ) {

			if ( ! $handler ) {
				continue;
			}

			$count_data = $handler->count();
			$count_data['keep_last'] = $keep_last[ $type ] ?? [];
			// $count_data['has_automation'] = ADBC_Automation::has_automation( $type );

			$items[ $type ] = $count_data;

			$total_size += $count_data['size'];
			$total_count += $count_data['count'];

		}

		return [ 
			'items' => $items,
			'total_size' => $total_size,
			'total_count' => $total_count,
		];

	}

	/**
	 * Get a list of items for a specific type with pagination and filtering.
	 *
	 * @param string $items_type The type of items to retrieve.
	 * 
	 * @param array $args Arguments for pagination and filtering.
	 * 
	 * @return array An array containing the items, total items count, total size, and the real current page.
	 */
	public static function get_items( $items_type, $args ) {

		$handler = ADBC_Cleanup_Type_Registry::handler( $items_type );

		if ( ! $handler ) {
			return [ 
				"items" => [],
				"total_items" => 0,
				"total_size" => 0,
				"real_current_page" => 1,
			];
		}

		$items = $handler->list( $args );
		$total = $handler->count_filtered( $args );

		$total_pages = max( 1, ceil( $total['count'] / $args['items_per_page'] ) );
		$real_current_page = min( $args['current_page'], $total_pages );

		return [ 
			"items" => $items,
			"total_items" => $total['count'],
			"total_size" => $total['size'],
			"real_current_page" => $real_current_page,
		];

	}

	/**
	 * Delete items of a specific type.
	 *
	 * @param string $items_type The type of items to delete.
	 * @param array $items An array of items to delete.
	 * 
	 * @return int The number of items deleted.
	 */
	public static function delete_items( $items_type, $items ) {

		$handler = ADBC_Cleanup_Type_Registry::handler( $items_type );

		if ( ! $handler ) {
			return 0;
		}

		return $handler->delete( $items );

	}

	/**
	 * Purge all items of a specific type.
	 *
	 * @param string $items_type The type of items to purge.
	 * 
	 * @return int The number of items purged.
	 */
	public static function purge_items( $items_type ) {

		$handler = ADBC_Cleanup_Type_Registry::handler( $items_type );

		if ( ! $handler ) {
			return 0;
		}

		return $handler->purge();

	}

	/**
	 * Run cleanup for a specific type of items with a given keep_last config.
	 * Can be called from other services like automation.
	 *
	 * @param string $items_type The type of items to clean up.
	 * @param string $keep_last_config The keep_last config of cleanup ('default', 'no_keep_last', 'custom_keep_last').
	 * @param array $custom_keep_last_value Custom keep last settings if config is 'custom_keep_last'.
	 * 
	 * @return int The number of items purged.
	 */
	public static function run_cleanup( $items_type, $keep_last_config = 'default', $custom_keep_last_value = [] ) {

		$handler = ADBC_Cleanup_Type_Registry::handler( $items_type );

		if ( ! $handler ) {
			return 0;
		}

		switch ( $keep_last_config ) {
			case 'no_keep_last': // Do not keep any items.
				$handler->set_keep_last_config( false );
				break;

			case 'custom_keep_last': // Override the keep_last setting with a custom value.
				$handler->set_keep_last_config( $custom_keep_last_value );
				break;

			default: // 'default' Use the default keep_last setting.
				$handler->set_keep_last_config( null );
		}

		return $handler->purge();

	}

	/**
	 * Set or update the keep_last settings for different items types.
	 *
	 * @param array $new An associative array where keys are items types and values are the keep_last settings.
	 * 
	 * @return array The updated keep_last settings.
	 */
	public static function set_keep_last( $new ) {

		$old = ADBC_Settings::instance()->get_setting( 'keep_last' );

		if ( $old === $new ) {
			return $old;
		}

		// Update old or add new keep_last setting with the given values.
		foreach ( $new as $items_type => $keep_last_setting ) {
			$old[ $items_type ] = $keep_last_setting;
		}

		ADBC_Settings::instance()->update_settings( [ 'keep_last' => $old ] );

		return $old;

	}

	/**
	 * Get the keep_last settings for all items types or a specific type.
	 *
	 * @param string $items_type The type of items to get the keep_last settings for, or an empty string for all types.
	 * 
	 * @return array|false An array of keep_last settings for the specified type, or false if not found.
	 */
	public static function get_keep_last( $items_type = '' ) {

		$keep_last = ADBC_Settings::instance()->get_setting( 'keep_last' );

		if ( $items_type === '' ) {
			return $keep_last;
		}

		if ( isset( $keep_last[ $items_type ] ) ) {
			return $keep_last[ $items_type ];
		}

		return false;

	}

	/**
	 * Delete the keep_last settings for specified items types.
	 *
	 * @param array $items_types An array of items types whose keep_last settings should be deleted.
	 * 
	 * @return array The updated keep_last settings after deletion.
	 */
	public static function delete_keep_last( $items_types ) {

		$old = ADBC_Settings::instance()->get_setting( 'keep_last' );

		foreach ( $items_types as $items_type ) {
			if ( isset( $old[ $items_type ] ) ) {
				unset( $old[ $items_type ] );
			}
		}

		ADBC_Settings::instance()->update_settings( [ 'keep_last' => $old ] );

		return $old;

	}

	/**
	 * Activate auto count for specified items types.
	 *
	 * @param array $items_types An array of items types to activate auto count for.
	 * 
	 * @return array The updated items types with auto count activated.
	 */
	public static function activate_auto_count( $items_types ) {

		$existing_items_types = ADBC_Settings::instance()->get_setting( 'general_cleanup_auto_count' );

		$new_items_types = $existing_items_types;

		// Add items types that do not exist in the old setting.
		foreach ( $items_types as $items_type ) {

			// If the items type already exists in the old setting, skip it.
			if ( in_array( $items_type, $existing_items_types, true ) )
				continue;

			$new_items_types[] = $items_type;

		}

		$new_items_types = array_values( array_unique( $new_items_types ) );

		ADBC_Settings::instance()->update_settings( [ 'general_cleanup_auto_count' => $new_items_types ] );

		return $new_items_types;

	}

	/**
	 * Deactivate auto count for specified items types.
	 *
	 * @param array $items_types An array of items types to deactivate auto count for.
	 * 
	 * @return array The updated items types with auto count deactivated.
	 */
	public static function deactivate_auto_count( $items_types ) {

		$existing_items_types = ADBC_Settings::instance()->get_setting( 'general_cleanup_auto_count' );

		$new_items_types = [];

		// remove items types that exist in the old setting
		foreach ( $existing_items_types as $existing_items_type ) {

			// if the items type does not exist in the items types to deactivate, add it to the new items types
			if ( ! in_array( $existing_items_type, $items_types, true ) )
				$new_items_types[] = $existing_items_type;

		}

		ADBC_Settings::instance()->update_settings( [ 'general_cleanup_auto_count' => $new_items_types ] );

		return $new_items_types;

	}

}
