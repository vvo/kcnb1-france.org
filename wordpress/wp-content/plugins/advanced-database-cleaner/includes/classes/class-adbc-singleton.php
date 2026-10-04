<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Singleton class.
 * 
 * This class provides the singleton pattern to be used by other classes.
 */
abstract class ADBC_Singleton {

	/**
	 * Holds the instance of each subclass.
	 *
	 * @var array
	 */
	private static $_instances = [];

	/**
	 * Constructor.
	 */
	protected function __construct( ...$args ) {
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return object The singleton instance.
	 */
	public static function instance( ...$args ) {

		$cls = static::class;

		if ( ! isset( self::$_instances[ $cls ] ) ) {
			self::$_instances[ $cls ] = new static( ...$args );
		}

		return self::$_instances[ $cls ];
	}

	/**
	 * Prevent cloning.
	 */
	final public function __clone() {
		throw new Exception( "Cannot clone a singleton." );
	}

	/**
	 * Prevent unserialization for PHP < 7.4
	 */
	final public function __wakeup() {
		throw new Exception( "Cannot unserialize a singleton." );
	}

	/**
	 * Prevent serialization for PHP 7.4+
	 */
	final public function __serialize() {
		throw new Exception( "Cannot serialize a singleton." );
	}

	/**
	 * Prevent unserialization for PHP 7.4+
	 */
	final public function __unserialize( $data ) {
		throw new Exception( "Cannot unserialize a singleton." );
	}

}