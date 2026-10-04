<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC automation validator class.
 * 
 * This class provides functions to validate and sanitize the automation data sent by the user to the endpoints.
 */
class ADBC_Automation_Validator {

	/**
	 * The required keys for the automation task structure.
	 *
	 * @var array<string, string>
	 */
	private static $required_keys = [ 
		'name' => 'is_valid_name',
		'frequency' => 'is_valid_frequency',
		'start_datetime' => 'is_valid_timestamp',
		'type' => 'is_valid_automation_type',
		'operations' => 'is_valid_operations',
		'active' => 'is_valid_active_status',
	];

	/**
	 * Validates the structure of the automation task details.
	 *
	 * @param array $task_details The task details to validate.
	 * 
	 * @return bool Returns true if the task structure is valid, false otherwise.
	 */
	public static function validate_task_structure( $task_details ) {

		if ( ! is_array( $task_details ) || empty( $task_details ) ) {
			return false;
		}

		foreach ( self::$required_keys as $key => $validation_method ) {
			if ( ! array_key_exists( $key, $task_details ) || ! call_user_func( [ self::class, $validation_method ], $task_details[ $key ] ) ) {
				return false;
			}
		}

		return true;

	}

	/**
	 * Validates if the provided name is a valid non empty string.
	 * 
	 * @param mixed $name
	 * 
	 * @return bool
	 */
	private static function is_valid_name( $name ) {
		return is_string( $name ) && ! empty( $name );
	}

	/**
	 * Validates if the provided frequency is a valid ADBC schedule frequency.
	 * 
	 * @param mixed $frequency
	 * 
	 * @return bool
	 */
	private static function is_valid_frequency( $frequency ) {
		$adbc_frequencies = ADBC_Common_Utils::get_adbc_schedule_frequencies();
		$adbc_frequencies[] = 'adbc_once'; // Include adbc_once as a valid frequency
		return is_string( $frequency ) && in_array( $frequency, $adbc_frequencies );
	}

	/**
	 * Validates if the provided value is a valid UNIX timestamp (seconds).
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	private static function is_valid_timestamp( $value ) {

		if ( is_int( $value ) ) {
			return $value > 0;
		}

		return false;

	}

	/**
	 * Validates if the provided automation type is valid.
	 * 
	 * @param mixed $type
	 * 
	 * @return bool
	 */
	private static function is_valid_automation_type( $type ) {
		$valid_types = [ 'general_cleanup' ];
		return is_string( $type ) && in_array( $type, $valid_types );
	}

	/**
	 * Validates if the provided operations are valid general_cleanup operations.
	 * Validate the keep_last_config structure and values.
	 * 
	 * @param mixed $operations
	 * 
	 * @return bool
	 */
	private static function is_valid_operations( $operations ) {

		if ( ! is_array( $operations ) || empty( $operations ) )
			return false;

		foreach ( $operations as $items_type => $keep_last_config ) {

			// Validate the general_cleanup items type
			if ( ! ADBC_Cleanup_Type_Registry::is_valid_items_type( $items_type ) )
				return false;

			// Validate the keep_last_config value type
			if ( ! is_string( $keep_last_config ) && ! is_array( $keep_last_config ) ) {
				return false;
			}

			// Validate the keep_last_config in case of string
			if ( is_string( $keep_last_config ) && ! in_array( $keep_last_config, [ 'no_keep_last', 'default' ], true ) ) {
				return false;
			}

			// Validate the keep_last_config in case of array (custom keep_last value)
			if ( is_array( $keep_last_config ) ) {

				if ( count( $keep_last_config ) !== 2 )
					return false;
				if ( ! array_key_exists( 'type', $keep_last_config ) || ! array_key_exists( 'value', $keep_last_config ) )
					return false;
				if ( ! in_array( $keep_last_config['type'], [ 'days', 'items' ], true ) )
					return false;
				if ( ! is_int( $keep_last_config['value'] ) || $keep_last_config['value'] <= 0 )
					return false;

			}
		}

		return true;

	}

	/**
	 * Validates if the provided active status is a boolean.
	 * 
	 * @param mixed $active
	 * 
	 * @return bool
	 */
	private static function is_valid_active_status( $active ) {
		return is_bool( $active );
	}

}