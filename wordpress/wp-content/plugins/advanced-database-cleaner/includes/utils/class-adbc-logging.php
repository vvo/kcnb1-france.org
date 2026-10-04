<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC Logging class.
 * 
 * This class provides logging methods.
 */
class ADBC_Logging {

	public const DEBUG_LOG_FILE_PATH = ADBC_UPLOADS_DIR_PATH . '/debug.log';
	private const DEBUG_LOG_FILE_MAX_SIZE = 100 * 1024; // 100 KB.
	private const WP_DEBUG_FILE_MAX_SIZE = 1 * 1024 * 1024; // 1 MB.
	private const ERROR_MESSAGE_MAX_LENGTH = 300;
	private const EXCEPTION_MESSAGE_MAX_LENGTH = 500;

	/**
	 * Log an error to the debug.log file.
	 * 
	 * @param string $message The error message.
	 * @param string $method The method name where the error occurred (optional).
	 * @param int $line The line number where the error occurred (optional).
	 * @return void
	 */
	public static function log_error( $message, $method = "", $line = "" ) {

		if ( ! empty( $method ) )
			$message .= " in {$method}";

		if ( ! empty( $line ) )
			$message .= " on line {$line}";

		self::log( $message );
	}

	/**
	 * Log an exception to the debug.log file.
	 * 
	 * @param string $method_name The method name where the exception occurred.
	 * @param object $exception The exception object.
	 * @return void
	 */
	public static function log_exception( $method_name, $exception ) {
		self::log( "Exception in {$method_name}", $exception );
	}

	/**
	 * Log a message to the debug.log file.
	 * 
	 * @param string $message The message to log.
	 * @param object $exception The exception object if any.
	 * @return boolean True if successful, false otherwise.
	 */
	private static function log( $message, $exception = null ) {

		if ( ! ADBC_Files::instance()->is_readable_and_writable( self::DEBUG_LOG_FILE_PATH ) )
			return false;

		$debug_log_file_size = ADBC_Files::instance()->size( self::DEBUG_LOG_FILE_PATH );
		if ( $debug_log_file_size !== false && $debug_log_file_size > self::DEBUG_LOG_FILE_MAX_SIZE ) {
			if ( ! self::clear_log() )
				return false;
		}

		$message = ADBC_Common_Utils::truncate_string( $message, self::ERROR_MESSAGE_MAX_LENGTH );
		$exception_msg = '';

		if ( $exception !== null && $exception instanceof Throwable )
			$exception_msg = self::prepare_exception_message( $exception );

		if ( ! error_log( '[' . date( 'Y-m-d H:i:s' ) . '] ' . $message . ' ' . $exception_msg . PHP_EOL, 3, self::DEBUG_LOG_FILE_PATH ) )
			return false;

		return true;
	}

	/**
	 * Get the log file content.
	 * 
	 * @param string $file_type The file type to get ('debug' or 'wp-debug').
	 * @return array The log content.
	 */
	public static function get_log_content( $file_type = '' ) {

		if ( $file_type === 'debug' ) {

			$file_path = self::DEBUG_LOG_FILE_PATH;
			$printable_path = ADBC_WP_UPLOADS_DIR_PATH . "/" . ADBC_UPLOADS_DIR_PREFIX . "******/" . basename( $file_path );
			$max_file_size = self::DEBUG_LOG_FILE_MAX_SIZE;

		} else {

			$file_path = ADBC_WP_DEBUG_LOG_FILE_PATH;
			$printable_path = ADBC_WP_DEBUG_LOG_FILE_PATH;
			$max_file_size = self::WP_DEBUG_FILE_MAX_SIZE;

		}

		if ( ! ADBC_Files::instance()->is_readable( $file_path ) )
			return [ 'success' => false, 'message' => __( 'The Log file is not readable or does not exist.', 'advanced-database-cleaner' ) ];

		$debug_log_file_size = ADBC_Files::instance()->size( $file_path );
		if ( $debug_log_file_size !== false && $debug_log_file_size > $max_file_size ) {
			return [ 
				'success' => false,
				'message' => sprintf(
					/* translators: 1: Log file path */
					__( 'The log file cannot be loaded since its size exceeds the limit. You can access it directly via: %s', 'advanced-database-cleaner' ),
					"\n" . $printable_path
				)
			];
		}

		$content = ADBC_Files::instance()->get_contents( $file_path );

		if ( $content === false )
			return [ 'success' => false, 'message' => __( 'Failed to get the file content.', 'advanced-database-cleaner' ) ];

		return [ 'success' => true, 'content' => $content ];
	}

	/**
	 * Clear the debug.log file.
	 * 
	 * @return boolean True if successful, false otherwise.
	 */
	public static function clear_log() {

		// Clear the debug.log file.
		if ( ! ADBC_Files::instance()->put_contents( self::DEBUG_LOG_FILE_PATH, '' ) )
			return false;

		return true;
	}

	/**
	 * Prepare the exception message to be logged.
	 * 
	 * @param object $exception The exception object.
	 * @return string The exception message.
	 */
	private static function prepare_exception_message( $exception ) {

		// Prepare the message.
		$message = $exception->getMessage();
		$message = ADBC_Common_Utils::truncate_string( $message, self::EXCEPTION_MESSAGE_MAX_LENGTH );

		// Prepare the path.
		$full_path = $exception->getFile();
		$position = strpos( $full_path, ADBC_PLUGIN_DIR_NAME );

		if ( $position !== false ) {
			$secured_path = substr( $full_path, $position ); // Trim the path to start from the plugin dir name
		} else {
			$secured_path = basename( $full_path ); // If plugin dir not found, use the file name only.
		}

		$exception_msg = '=> ' . $message . ". In file '" . $secured_path . "' on line " . $exception->getLine();

		return $exception_msg;
	}

}