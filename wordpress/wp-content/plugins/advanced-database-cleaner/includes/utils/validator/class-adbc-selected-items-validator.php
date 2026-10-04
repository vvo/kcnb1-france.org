<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Selected Items validator class.
 * 
 * This class provides functions to validate and sanitize the selected items sent by the user to the endpoints.
 */
class ADBC_Selected_Items_Validator {

	private const MAX_ITEMS_TO_PROCESS = 1000;

	/**
	 * Removes invalid selected items by checking their structure and checking if they exist in the database for tables and cron jobs.
	 * 
	 * @param string $items_type The type of items to validate.
	 * @param array $selected_items The selected items in this format [["items_type":"value", "site_id":"value", "name":"value", "cron_args":"value"],...].
	 * @param bool $keep_prefix Whether to keep the table prefix or not for tables.
	 * 
	 * @return array The validated selected items in the same format.
	 */
	public static function remove_invalid_selected_items( $items_type, $selected_items, $keep_prefix = true ) {

		// validate items type
		$items_type = ADBC_Common_Validator::sanitize_items_type( $items_type );
		if ( empty( $items_type ) || ! is_array( $selected_items ) )
			return [];

		// Do not process if too many items are selected.
		if ( count( $selected_items ) > self::MAX_ITEMS_TO_PROCESS )
			return [];

		$final_valid_selected_items = [];

		// remove invalid selected items by checking their structure
		$valid_structure_selected_items = self::remove_invalid_structure_selected_items( $items_type, $selected_items );

		// remove invalid selected items by checking their names against the database for tables and cron jobs
		if ( $items_type === 'tables' ) {

			$final_valid_selected_items = self::remove_inexistent_tables_names( $valid_structure_selected_items, $keep_prefix );

		} else if ( $items_type === 'cron_jobs' ) {

			$final_valid_selected_items = self::remove_inexistent_cron_jobs( $valid_structure_selected_items );

		} else if ( in_array( $items_type, [ 'transients', 'expired_transients' ], true ) ) {

			$final_valid_selected_items = self::remove_inexistant_transients( $valid_structure_selected_items );

		} else if ( in_array( $items_type, [ 'options', 'posts_meta', 'users_meta' ], true ) ) {

			$final_valid_selected_items = self::remove_inexistent_items_names( $valid_structure_selected_items, $items_type );

		} else if ( $items_type === 'post_types' ) {

			$final_valid_selected_items = self::remove_inexistent_post_types( $valid_structure_selected_items );

		} else {

			// For all other types (in General Cleanup), just return the valid structure selected items because we are working with IDs not names.
			$final_valid_selected_items = $valid_structure_selected_items;
		}

		return $final_valid_selected_items;
	}

	/**
	 * Remove invalid selected items by checking their structure.
	 *
	 * @param string $items_type The type of items to validate.
	 * @param array $selected_items The selected items in this format [["items_type":"value", "site_id":"value", "name":"value", "cron_args":"value"..],...].
	 * 
	 * @return array The validated selected items in the same format.
	 */
	private static function remove_invalid_structure_selected_items( $items_type, $selected_items ) {

		$is_not_empty = function ($v) {
			return ! empty( $v );
		};
		$is_in_array = function ($v) {
			return in_array( $v, [ 'options', 'sitemeta' ], true );
		};
		$is_numeric = function ($v) {
			return is_numeric( $v ) && $v > 0;
		};
		$is_array = function ($v) {
			return is_array( $v );
		};

		// Key => validation-callback map for every item type
		$schema = [ 

			'tables' => [ 'name' => $is_not_empty ],

			'cron_jobs' => [ 'site_id' => $is_numeric, 'name' => $is_not_empty, 'args' => $is_array, 'timestamp' => $is_numeric ],

			'options' => [ 'site_id' => $is_numeric, 'id' => $is_numeric, 'name' => $is_not_empty ],

			'post_types' => [ 'site_id' => $is_numeric, 'name' => $is_not_empty ],

			'posts_meta' => [ 'site_id' => $is_numeric, 'id' => $is_numeric, 'name' => $is_not_empty ],
			'users_meta' => [ 'site_id' => $is_numeric, 'id' => $is_numeric, 'name' => $is_not_empty ],

			'transients' => [ 
				'site_id' => $is_numeric,
				'id' => $is_numeric,
				'name' => $is_not_empty,
				'found_in' => $is_in_array,
			],

			'expired_transients' => [ 
				'site_id' => $is_numeric,
				'id' => $is_numeric,
				'name' => $is_not_empty,
				'found_in' => $is_in_array,
			],

			'unused_relationships' => [ 
				'site_id' => $is_numeric,
				'id' => $is_numeric,
				'term_taxonomy_id' => $is_numeric,
			],

			// fallback: all “general-cleanup” types
			'default' => [ 'site_id' => $is_numeric, 'id' => $is_numeric,],
		];

		$rules = $schema[ $items_type ] ?? $schema['default'];

		/*********************************************************************
		 * Build a new array that keeps only the rows whose structure is valid
		 * ******************************************************************/
		$validRows = [];

		foreach ( $selected_items as $row ) {

			// 1. items_type must be present and equal to the context
			if ( ! isset( $row['items_type'] ) || $row['items_type'] !== $items_type )
				continue;

			// 2. every required key must exist and pass its validator
			$isValid = true;
			foreach ( $rules as $key => $validator ) {
				if ( ! isset( $row[ $key ] ) || ! $validator( $row[ $key ] ) ) {
					$isValid = false;
					break;
				}
			}

			// 3. keep the row only if it passed every test
			if ( $isValid )
				$validRows[] = $row;
		}

		return $validRows;
	}

	/**
	 * Remove tables names that do not exist in the database.
	 *
	 * @param array $selected_tables The tables names in this format [["items_type":"tables", "name": "value"],...].
	 * @param bool $keep_prefix Whether to keep the table prefix or not.
	 * 
	 * @return array The validated tables names selected by the user in the same format.
	 */
	private static function remove_inexistent_tables_names( $selected_tables, $keep_prefix = true ) {

		$batch_size = ADBC_Settings::instance()->get_setting( 'database_rows_batch' );
		$offset = 0;
		$valid_selected_tables = [];

		// loop through the tables in batches and check the selected tables against the existing tables
		while ( $tables_names_batch = ADBC_Tables::get_tables_names( $batch_size, $offset, $keep_prefix ) ) {

			foreach ( $selected_tables as $selected_table ) {

				if ( $keep_prefix === false )
					$selected_table['name'] = ADBC_Tables::remove_prefix_from_table_name( $selected_table['name'] );

				// if the table exists, add it to the valid tables
				if ( key_exists( $selected_table['name'], $tables_names_batch ) )
					$valid_selected_tables[] = $selected_table;

			}

			$offset += $batch_size;

		}

		return $valid_selected_tables;
	}

	/**
	 * Filter invalid cron jobs by checking them against the database.
	 * 
	 * @param array $selected_cron_jobs The cron jobs names in this format [["items_type":"cron_jobs", "site_id":"value", "name":"value","cron_args":"value"],...].
	 * 
	 * @return array The validated cron jobs selected by the user in the same format.
	 */
	private static function remove_inexistent_cron_jobs( $selected_cron_jobs ) {

		$valid_cron_jobs = [];

		// create an associative array with the site id as the key and their selected cron jobs as the value
		$siteid_keyed_cron_jobs = self::group_selected_items_by_site_id( $selected_cron_jobs );

		// loop through the sites id selected cron jobs and check if they exist in the database
		foreach ( $siteid_keyed_cron_jobs as $site_id => $site_selected_cron_jobs ) {

			$site_cron_jobs = ADBC_Cron_Jobs::get_site_cron_jobs( $site_id );

			// loop through the selected cron jobs and check if they exist in the database
			foreach ( $site_selected_cron_jobs as $selected_cron_job ) {

				foreach ( $site_cron_jobs as $site_cron_job ) {
					if ( $site_cron_job->name === $selected_cron_job['name'] && $site_cron_job->args === $selected_cron_job['args'] && $site_cron_job->timestamp === $selected_cron_job['timestamp'] ) {
						$valid_cron_jobs[] = $selected_cron_job;
						break;
					}
				}

			}

		}

		return $valid_cron_jobs;

	}

	/**
	 * Remove inexistent transients names from the selected items.
	 * 
	 * @param array $selected_transients The selected transients in this format [["items_type":"transients", "site_id":"value", "name":"value", "found_in":"value"],...].
	 * 
	 * @return array The validated selected transients in the same format.
	 */
	private static function remove_inexistant_transients( $selected_transients ) {

		$valid_transients = [];
		$siteid_grouped_transients = self::group_selected_items_by_site_id( $selected_transients );

		foreach ( $siteid_grouped_transients as $site_id => $site_group_transients ) {

			// group the site grouped selected transients by their found_in value
			$found_in_grouped_transients = [];
			foreach ( $site_group_transients as $selected_transient ) {
				$found_in_grouped_transients[ $selected_transient['found_in'] ][] = $selected_transient;
			}

			// loop through the found_in grouped transients to execute only one query per found_in value
			foreach ( $found_in_grouped_transients as $found_in => $selected_transients ) {

				// Prepare ids list to check against the database (Ids have already been validated in the structure validation step)
				$ids = array_column( $selected_transients, 'id' );
				$table_name = $found_in === 'sitemeta' ? 'sitemeta' : 'options';
				$column_name = $found_in === 'sitemeta' ? 'meta_key' : 'option_name';
				$id_column_name = $found_in === 'sitemeta' ? 'meta_id' : 'option_id';
				$transients_names_in_db = ADBC_Common_Model::get_column_rows_from_table( $site_id, $table_name, $column_name, $id_column_name, $ids );

				if ( empty( $transients_names_in_db ) )
					continue;

				// loop through the found_in grouped transients for the current site_id and check if they exist
				foreach ( $selected_transients as $selected ) {
					// If the transient name exists in the database, add it to the valid transients
					if ( in_array( $selected['name'], $transients_names_in_db, true ) ) {
						$valid_transients[] = $selected;
					}
				}

			}

		}

		return $valid_transients;

	}

	/**
	 * Remove inexistent items names from the selected items. Works for options, posts_meta, users_meta, transients and expired_transients.
	 *
	 * @param array $selected_items The selected items in this format [["items_type":"value", "site_id":"value", "name":"value",...].
	 * @param string $items_type The type of items to validate. Can be 'options', 'posts_meta', 'users_meta', 'transients' or 'expired_transients'.
	 * 
	 * @return array The validated selected items in the same format.
	 */
	private static function remove_inexistent_items_names( $selected_items, $items_type ) {

		switch ( $items_type ) {
			case 'options':
				$table_name = 'options';
				$column_name = 'option_name';
				$id_column_name = 'option_id';
				break;
			case 'posts_meta':
				$table_name = 'postmeta';
				$column_name = 'meta_key';
				$id_column_name = 'meta_id';
				break;
			case 'users_meta':
				$table_name = 'usermeta';
				$column_name = 'meta_key';
				$id_column_name = 'umeta_id';
				break;
		}

		$valid_items = [];
		$siteid_grouped_items = self::group_selected_items_by_site_id( $selected_items );

		// loop through the sites id selected items and check if they exist in the database
		foreach ( $siteid_grouped_items as $site_id => $group_selected ) {

			// Prepare ids list to check against the database (Ids have already been validated in the structure validation step)
			$ids = array_column( $group_selected, 'id' );

			$items_names_in_db = ADBC_Common_Model::get_column_rows_from_table( $site_id, $table_name, $column_name, $id_column_name, $ids );

			if ( empty( $items_names_in_db ) )
				continue;

			// loop through the selected items for the current site_id and check if they exist in the retrieved items names
			foreach ( $group_selected as $selected ) {

				// If the item name exists in the database, add it to the valid items
				if ( in_array( $selected['name'], $items_names_in_db, true ) )
					$valid_items[] = $selected;

			}
		}

		return $valid_items;
	}

	/**
	 * Remove post types that do not exist in the posts table of the given site.
	 *
	 * @param array $selected_post_types [["items_type":"post_types", "site_id":"value", "name":"value"], ...].
	 *
	 * @return array The validated selected post types.
	 */
	private static function remove_inexistent_post_types( $selected_post_types ) {

		$valid = [];
		$grouped = self::group_selected_items_by_site_id( $selected_post_types );

		foreach ( $grouped as $site_id => $group ) {

			$site_prefix = ADBC_Sites::instance()->get_prefix_from_site_id( $site_id );

			if ( $site_prefix === null )
				continue;

			$existing = ADBC_Post_Types::get_all_post_type_names_for_site( $site_prefix );
			$existing_map = array_flip( $existing );

			foreach ( $group as $selected ) {
				if ( isset( $existing_map[ $selected['name'] ] ) )
					$valid[] = $selected;
			}
		}

		return $valid;
	}

	/**
	 * Group selected items by site id as key.
	 *
	 * @param array $selected_items The selected items in this format [["items_type":"value", "site_id":"value", "name":"value", "cron_args":"value"],...].
	 * 
	 * @return array The grouped selected items by site id in the same format.
	 */
	public static function group_selected_items_by_site_id( $selected_items ) {

		$grouped = [];
		foreach ( $selected_items as $selected_item ) {
			$grouped[ $selected_item['site_id'] ][] = $selected_item;
		}

		return $grouped;
	}

}