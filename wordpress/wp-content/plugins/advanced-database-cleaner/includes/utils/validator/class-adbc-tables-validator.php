<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Tables validator class.
 * 
 * This class provides functions to validate and sanitize the tables data used in the plugin.
 */
class ADBC_Tables_Validator {

	/**
	 * Validate the selected tables names list sent by the user against the existing tables and return the valid ones.
	 * 
	 * @param array $tables_names The tables names to validate.
	 * 
	 * @return array The validated tables names, empty array if invalid.
	 */
	public static function validate_tables_names_list( $tables_names ) {

		$validated_tables = [];

		if ( ! is_array( $tables_names ) )
			return $validated_tables;

		$batch_size = ADBC_Settings::instance()->get_setting( 'database_rows_batch' );
		$offset = 0;
		$valid_tables = [];

		// loop through the tables in batches and check the selected tables against the existing tables
		while ( $tables_names_batch = ADBC_Tables::get_tables_names( $batch_size, $offset ) ) {

			foreach ( $tables_names as $table_name ) {

				// if the table exists, add it to the valid tables
				if ( key_exists( $table_name, $tables_names_batch ) )
					$valid_tables[] = $table_name;

			}

			$offset += $batch_size;

		}

		return $valid_tables;

	}

}