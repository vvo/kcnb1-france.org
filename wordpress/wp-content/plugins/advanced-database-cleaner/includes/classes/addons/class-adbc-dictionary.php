<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Dictionary class.
 * 
 * This class provides functions for addons dictionary names.
 */
class ADBC_Dictionary {

	/**
	 * Add missing addons names to the items list from the dictionary. If the name is not found, the slug is set as the name. 
	 * 
	 * @param array $items_list The items list to add the missing addons names to.
	 * @param ADBC_Scan_Counter $scan_counter_object The scan counter object containing the categorization count.
	 * @param string $items_type The items type. "tables", "options", "cron_jobs", "transients", "posts_meta", "users_meta".
	 * @return void
	 */
	public static function add_missing_addons_names_from_dictionary( &$items_list, &$scan_counter_object, $items_type ) {

		// Prepare an array to store the slugs with missing names. The key is the type:slug and the value will be filled by the name.
		$slugs_with_missing_names = [];

		// Gather all slugs with missing names from the $items_list
		self::gather_slugs_with_missing_names_from_items_list( $items_list, $slugs_with_missing_names );

		// Gather the slugs with missing names from the $scan_counter_object
		$plugins_count = $scan_counter_object->get_plugins_count();
		$themes_count = $scan_counter_object->get_themes_count();
		self::gather_slugs_with_missing_names_from_scan_counter( $plugins_count, $themes_count, $slugs_with_missing_names );

		if ( empty( $slugs_with_missing_names ) ) // If there are no slugs with missing names, return
			return;

		// Load the names from the dictionary file for the slugs with missing names
		self::load_names_from_dictionary( $slugs_with_missing_names, $items_type );

		// Load remaining missing names from the global custom addons dictionary as a last fallback.
		self::load_names_from_custom_addons_dictionary( $slugs_with_missing_names );

		// Set the names for the slugs with missing names. If the name is not found, the slug is set as the name.
		self::update_items_list_with_names( $items_list, $slugs_with_missing_names );

		// Set the names for the slugs with missing names in the $scan_counter_object
		self::update_scan_counter_with_names( $plugins_count, $themes_count, $scan_counter_object, $slugs_with_missing_names );

	}

	/**
	 * Gather slugs with missing names from the $items_list.
	 *
	 * @param array $items_list The items list to gather the slugs with missing names from.
	 * @param array $slugs_with_missing_names The slugs with missing names to fill.
	 * @return void
	 */
	private static function gather_slugs_with_missing_names_from_items_list( &$items_list, &$slugs_with_missing_names ) {

		// Gather all slugs with missing names from the $items_list
		foreach ( $items_list as $index => $item_data ) {

			// Gather the slug with missing name for the "belongs_to" data
			$belongs_to = $item_data['belongs_to'];
			if ( ( $belongs_to['type'] === 'p' || $belongs_to['type'] === 't' ) && empty( $belongs_to['name'] ) )
				$slugs_with_missing_names[ $belongs_to['type'] . ":" . $belongs_to['slug'] ] = "";

			// Gather the slugs with missing names for the known plugins list
			foreach ( $item_data['known_plugins'] as $i => $plugin ) {
				if ( empty( $plugin['name'] ) )
					$slugs_with_missing_names[ 'p:' . $plugin['slug'] ] = "";
			}

			// Gather the slugs with missing names for the known themes list
			foreach ( $item_data['known_themes'] as $i => $theme ) {
				if ( empty( $theme['name'] ) )
					$slugs_with_missing_names[ 't:' . $theme['slug'] ] = "";
			}
		}

	}

	/**
	 * Gather slugs with missing names from the scan counter object.
	 *
	 * @param array $plugins_count The plugins count from the scan counter object.
	 * @param array $themes_count The themes count from the scan counter object.
	 * @param array $slugs_with_missing_names The slugs with missing names to fill.
	 * @return void
	 */
	private static function gather_slugs_with_missing_names_from_scan_counter( &$plugins_count, &$themes_count, &$slugs_with_missing_names ) {

		foreach ( $plugins_count as $plugin_slug => $plugin_data ) {
			if ( empty( $plugin_data['name'] ) )
				$slugs_with_missing_names[ 'p:' . $plugin_slug ] = "";
		}

		foreach ( $themes_count as $theme_slug => $theme_data ) {
			if ( empty( $theme_data['name'] ) )
				$slugs_with_missing_names[ 't:' . $theme_slug ] = "";
		}

	}

	/**
	 * Load names from the dictionary file.
	 *
	 * @param array $slugs_with_missing_names The slugs with missing names.
	 * @param string $items_type The items type.
	 * @return void
	 */
	private static function load_names_from_dictionary( &$slugs_with_missing_names, $items_type ) {

		$dict_file_path = ADBC_Scan_Paths::get_addons_dictionary_file_path( $items_type );
		self::load_names_from_dictionary_file( $slugs_with_missing_names, $dict_file_path );

	}

	/**
	 * Load names from the custom addons dictionary file.
	 *
	 * @param array $slugs_with_missing_names The slugs with missing names.
	 * @return void
	 */
	private static function load_names_from_custom_addons_dictionary( &$slugs_with_missing_names ) {

		if ( ! self::has_missing_addon_names( $slugs_with_missing_names ) )
			return;

		$dict_file_path = ADBC_Scan_Paths::get_custom_addons_dictionary_file_path();
		self::load_names_from_dictionary_file( $slugs_with_missing_names, $dict_file_path );

	}

	/**
	 * Load names from a dictionary file.
	 *
	 * @param array  $slugs_with_missing_names The slugs with missing names.
	 * @param string $dict_file_path The dictionary file path.
	 * @return void
	 */
	private static function load_names_from_dictionary_file( &$slugs_with_missing_names, $dict_file_path ) {

		$total_slugs_with_missing_names = self::count_missing_addon_names( $slugs_with_missing_names );

		if ( $total_slugs_with_missing_names === 0 )
			return;

		$dict_file_handle = ADBC_Files::instance()->get_file_handle( $dict_file_path, 'r' );

		if ( $dict_file_handle !== false ) {
			while ( ( $line = fgets( $dict_file_handle ) ) !== false ) {
				$line = rtrim( $line, "\r\n" );
				[ $typed_slug, $addon_name ] = self::split_slug_name_dictionary_line( $line );

				if ( $typed_slug === false || $addon_name === false )
					continue;

				if ( isset( $slugs_with_missing_names[ $typed_slug ] ) && $slugs_with_missing_names[ $typed_slug ] === '' ) {
					$slugs_with_missing_names[ $typed_slug ] = $addon_name;
					if ( --$total_slugs_with_missing_names === 0 )
						break;
				}
			}

			fclose( $dict_file_handle );
		}

	}

	/**
	 * Check if the slugs names dictionary still contains missing names.
	 *
	 * @param array $slugs_names_dictionary The slugs names dictionary.
	 * @return bool True if a missing name still exists.
	 */
	private static function has_missing_addon_names( &$slugs_names_dictionary ) {

		return self::count_missing_addon_names( $slugs_names_dictionary ) > 0;

	}

	/**
	 * Count missing addon names in a slugs names dictionary.
	 *
	 * @param array $slugs_names_dictionary The slugs names dictionary.
	 * @return int The number of missing names.
	 */
	private static function count_missing_addon_names( &$slugs_names_dictionary ) {

		$missing = 0;

		foreach ( $slugs_names_dictionary as $addon_name ) {
			if ( $addon_name === '' )
				$missing++;
		}

		return $missing;

	}

	/**
	 * Update the missing addons names in the items list with the names from the dictionary.
	 *
	 * @param array $items_list The items list to update the missing addons names in.
	 * @param array $slugs_names_dictionary The slugs names dictionary to update the items list with.
	 * @return void
	 */
	private static function update_items_list_with_names( &$items_list, &$slugs_names_dictionary ) {

		foreach ( $items_list as $index => $item_data ) {

			// Update the name for the "belongs_to" data
			$belongs_to = $item_data['belongs_to'];
			$belongs_to_slug = $belongs_to['slug'];
			$typed_belongs_to_slug = $belongs_to['type'] . ':' . $belongs_to_slug;

			if ( ( $belongs_to['type'] === 'p' || $belongs_to['type'] === 't' ) && empty( $belongs_to['name'] ) )
				$items_list[ $index ]['belongs_to']['name'] = $slugs_names_dictionary[ $typed_belongs_to_slug ] ?: $belongs_to_slug;

			// Update the names for the known plugins list
			foreach ( $item_data['known_plugins'] as $i => $plugin ) {
				if ( empty( $plugin['name'] ) )
					$items_list[ $index ]['known_plugins'][ $i ]['name'] = $slugs_names_dictionary[ 'p:' . $plugin['slug'] ] ?: $plugin['slug'];
			}

			// Update the names for the known themes list
			foreach ( $item_data['known_themes'] as $i => $theme ) {
				if ( empty( $theme['name'] ) )
					$items_list[ $index ]['known_themes'][ $i ]['name'] = $slugs_names_dictionary[ 't:' . $theme['slug'] ] ?: $theme['slug'];
			}
		}

	}

	/**
	 * Update the missing addons names in the scan counter object with the names from the dictionary.
	 *
	 * @param array $plugins_count The plugins count from the scan counter object.
	 * @param array $themes_count The themes count from the scan counter object.
	 * @param ADBC_Scan_Counter $scan_counter_object The scan counter object to update the missing addons names in.
	 * @param array $slugs_names_dictionary The slugs names dictionary to update the items list with.
	 * @return void
	 */
	private static function update_scan_counter_with_names( &$plugins_count, &$themes_count, &$scan_counter_object, &$slugs_names_dictionary ) {

		foreach ( $plugins_count as $plugin_slug => $plugin_data ) {
			if ( empty( $plugin_data['name'] ) )
				$scan_counter_object->set_plugin_name( $plugin_slug, $slugs_names_dictionary[ 'p:' . $plugin_slug ] ?: $plugin_slug );
		}

		foreach ( $themes_count as $theme_slug => $theme_data ) {
			if ( empty( $theme_data['name'] ) )
				$scan_counter_object->set_theme_name( $theme_slug, $slugs_names_dictionary[ 't:' . $theme_slug ] ?: $theme_slug );
		}

	}

	/**
	 * Update the slug name dictionary file for the manual categorization.
	 * This function is used to add the slug name pair to the dictionary file when the user manually categorizes an item.
	 * 
	 * @param array $manual_categorization The manual categorization data containing the slug and type.
	 * @param string $items_type The type of items to update the dictionary for. "tables", "options", "cron_jobs", etc.
	 * 
	 * @return void
	 */
	public static function update_slug_name_dictionary_for_manual( $manual_categorization, $items_type ) {

		// We suppose that $manual_categorization format has been already validated before calling this function.

		$slug_type = $manual_categorization['type'];
		$new_slug = $manual_categorization['slug'];

		if ( $slug_type !== 'p' && $slug_type !== 't' ) // We update the dictionary only for plugins and themes.
			return;

		$is_custom = ! empty( $manual_categorization['is_custom'] );
		if ( $is_custom ) {
			self::update_custom_addons_names_dictionary_for_manual( $manual_categorization );
			return;
		}

		$new_addon_name = ADBC_Addons::get_addon_name( $new_slug, $slug_type );
		$new_slug = $slug_type . ':' . $new_slug;

		$dict_file_path = ADBC_Scan_Paths::get_addons_dictionary_file_path( $items_type );
		$temp_dict_file_path = ADBC_Scan_Paths::get_addons_dictionary_temp_file_path( $items_type );

		// If the dictionary file does not exist, create it and add the addon name.
		if ( ADBC_Files::instance()->exists( $dict_file_path ) === false ) {

			$line = $new_slug . "|" . $new_addon_name . "\n";
			$success = ADBC_Files::instance()->put_contents( $dict_file_path, $line );

			if ( $success === false ) {
				ADBC_Logging::log_error( "Failed to create the dictionary file.", __METHOD__, __LINE__ );
				return;
			}
		}

		$dict_file_handle = ADBC_Files::instance()->get_file_handle( $dict_file_path, 'r' );
		$temp_dict_file_handle = ADBC_Files::instance()->get_file_handle( $temp_dict_file_path, 'w' );

		if ( $dict_file_handle === false || $temp_dict_file_handle === false ) {
			ADBC_Logging::log_error( "Failed to open the dictionary file.", __METHOD__, __LINE__ );
			return;
		}

		// Loop over the dictionary file lines and add/update the slug name pair.
		$found_slug = false;

		while ( ( $line = fgets( $dict_file_handle ) ) !== false ) {

			$line = rtrim( $line, "\r\n" );
			[ $slug, $addon_name ] = self::split_slug_name_dictionary_line( $line );

			if ( $slug === false || $addon_name === false )
				continue;

			// if the addon is not the one we are looking for, write it to the temp file.
			if ( $slug !== $new_slug ) {
				fwrite( $temp_dict_file_handle, $line . "\n" );
				continue;
			}

			// if the addon is the one we are looking for, flag it as found.
			$found_slug = true;

			// Write the current name or replace it with the newly submitted custom addon name.
			$line = $addon_name === $new_addon_name
				? $line . "\n"
				: $new_slug . "|" . $new_addon_name . "\n";

			fwrite( $temp_dict_file_handle, $line );

		}

		// If the slug was not found, add it to the dictionary file.
		if ( ! $found_slug ) {
			$line = $new_slug . "|" . $new_addon_name . "\n";
			fwrite( $temp_dict_file_handle, $line );
		}

		fclose( $dict_file_handle );
		fclose( $temp_dict_file_handle );

		// Rename the temp file to the dictionary file.
		if ( ADBC_Files::instance()->exists( $temp_dict_file_path ) && ! rename( $temp_dict_file_path, $dict_file_path ) ) {
			ADBC_Logging::log_error( "Failed to rename the dictionary file.", __METHOD__, __LINE__ );
			return;
		}

	}

	/**
	 * Update the custom addons names dictionary file for the manual categorization.
	 *
	 * @param array $manual_categorization The manual categorization data containing type, slug and name.
	 * @return void
	 */
	private static function update_custom_addons_names_dictionary_for_manual( $manual_categorization ) {

		$slug_type = $manual_categorization['type'];
		$new_slug = $slug_type . ':' . $manual_categorization['slug'];
		$new_addon_name = trim( $manual_categorization['name'] );

		$dict_file_path = ADBC_Scan_Paths::get_custom_addons_dictionary_file_path();
		$temp_dict_file_path = ADBC_Scan_Paths::get_custom_addons_dictionary_temp_file_path();

		// If the dictionary file does not exist, create it and add the custom addon name.
		if ( ADBC_Files::instance()->exists( $dict_file_path ) === false ) {

			$line = $new_slug . "|" . $new_addon_name . "\n";
			$success = ADBC_Files::instance()->put_contents( $dict_file_path, $line );

			if ( $success === false ) {
				ADBC_Logging::log_error( "Failed to create the custom addons dictionary file.", __METHOD__, __LINE__ );
				return;
			}
		}

		$dict_file_handle = ADBC_Files::instance()->get_file_handle( $dict_file_path, 'r' );
		$temp_dict_file_handle = ADBC_Files::instance()->get_file_handle( $temp_dict_file_path, 'w' );

		if ( $dict_file_handle === false || $temp_dict_file_handle === false ) {
			ADBC_Logging::log_error( "Failed to open the custom addons dictionary file.", __METHOD__, __LINE__ );
			return;
		}

		$found_slug = false;

		while ( ( $line = fgets( $dict_file_handle ) ) !== false ) {

			$line = rtrim( $line, "\r\n" );
			[ $slug, $addon_name ] = self::split_slug_name_dictionary_line( $line );

			if ( $slug === false || $addon_name === false )
				continue;

			if ( $slug !== $new_slug ) {
				fwrite( $temp_dict_file_handle, $line . "\n" );
				continue;
			}

			$found_slug = true;
			$line = $addon_name === $new_addon_name
				? $line . "\n"
				: $new_slug . "|" . $new_addon_name . "\n";

			fwrite( $temp_dict_file_handle, $line );

		}

		if ( ! $found_slug ) {
			$line = $new_slug . "|" . $new_addon_name . "\n";
			fwrite( $temp_dict_file_handle, $line );
		}

		fclose( $dict_file_handle );
		fclose( $temp_dict_file_handle );

		if ( ADBC_Files::instance()->exists( $temp_dict_file_path ) && ! rename( $temp_dict_file_path, $dict_file_path ) ) {
			ADBC_Logging::log_error( "Failed to rename the custom addons dictionary file.", __METHOD__, __LINE__ );
			return;
		}

	}

	/**
	 * Get the custom addons names dictionary.
	 *
	 * @return array The custom addons dictionary list.
	 */
	public static function get_custom_addons_names_dictionary() {

		$dict_file_path = ADBC_Scan_Paths::get_custom_addons_dictionary_file_path();
		$dict_file_handle = ADBC_Files::instance()->get_file_handle( $dict_file_path, 'r' );

		if ( $dict_file_handle === false )
			return [];

		$custom_addons = [];

		while ( ( $line = fgets( $dict_file_handle ) ) !== false ) {

			$line = rtrim( $line, "\r\n" );
			[ $typed_slug, $addon_name ] = self::split_slug_name_dictionary_line( $line );

			if ( $typed_slug === false || $addon_name === false )
				continue;

			$parts = explode( ':', $typed_slug, 2 );
			if ( count( $parts ) !== 2 || ! in_array( $parts[0], [ 'p', 't' ], true ) || $parts[1] === '' )
				continue;

			$custom_addons[] = [ 
				'type' => $parts[0],
				'slug' => $parts[1],
				'name' => $addon_name,
			];

		}

		fclose( $dict_file_handle );

		usort( $custom_addons, function ($a, $b) {
			return strcasecmp( $a['name'], $b['name'] );
		} );

		return $custom_addons;

	}

	/**
	 * Split a line from the addons dictionary file into the typed slug and the addon name.
	 * 
	 * @param string $line The line to split.
	 * @return array An array containing the typed slug and the addon name. An array of false values if the line is not valid.
	 */
	public static function split_slug_name_dictionary_line( $line ) {

		$first_separator_position = strpos( $line, '|' );

		if ( $first_separator_position === false )
			return [ false, false ];

		$typed_slug = substr( $line, 0, $first_separator_position );
		$addon_name = substr( $line, $first_separator_position + 1 ); // +1 to skip the delimiter itself

		if ( $typed_slug === "" || $addon_name === "" )
			return [ false, false ];

		return [ $typed_slug, $addon_name ];

	}

}