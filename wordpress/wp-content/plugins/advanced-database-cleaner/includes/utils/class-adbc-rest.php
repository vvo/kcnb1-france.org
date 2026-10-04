<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC response class.
 * 
 * This class provides REST methods.
 */
class ADBC_Rest {

	public const OK = 200;
	public const BAD_REQUEST = 400;
	public const UNAUTHORIZED = 403;
	public const INTERNAL_SERVER_ERROR = 500;
	public const NOT_FOUND = 404;
	public const UNPROCESSABLE_ENTITY = 422;

	/**
	 * Return REST success response.
	 * 
	 * @param string $message Message to return.
	 * @param mixed $data Data to return.
	 * @param array $extra_data Extra data to return.
	 * 
	 * @return WP_REST_Response Response object.
	 */
	public static function success( $message = "", $data = [], $extra_data = [] ) {

		$response = [ 
			'success' => true,
			'message' => $message,
			'data' => $data,
		];

		if ( ! empty( $extra_data ) )
			$response['extra_data'] = $extra_data;

		return new WP_REST_Response(
			$response,
			self::OK
		);

	}

	/**
	 * Return REST error response.
	 * 
	 * @param string $message Message to return.
	 * @param int $status_code Status code to return.
	 * @param int $failure_code Failure code to return.
	 * @param array $extra_data Extra data to return.
	 * 
	 * @return WP_REST_Response Response object.
	 */
	public static function error( $message, $status_code, $failure_code = 0, $extra_data = [] ) {

		$response = [ 
			'success' => false,
			'message' => $message,
		];

		if ( $failure_code !== 0 )
			$response['failure_code'] = $failure_code;

		if ( ! empty( $extra_data ) )
			$response['extra_data'] = $extra_data;

		return new WP_REST_Response(
			$response,
			$status_code
		);

	}

	/**
	 * Return REST error response when an exception occurs.
	 * 
	 * @param string $method_name Method name where the exception occurred.
	 * @param object $exception Exception object.
	 * @return WP_REST_Response Response object.
	 */
	public static function error_for_uncaught_exception( $method_name = "", $exception = null ) {

		// Log exception if exists
		if ( $exception !== null )
			ADBC_Logging::log_exception( $method_name, $exception );

		$failure_code = $exception->getCode(); // Returns 0 if no code is set

		// Attach a default "Check the logs" link.
		$extra_data = [ 
			'message_links' => [ 
				[ 
					'text' => __( 'Check the logs', 'advanced-database-cleaner' ),
					'tab_id' => 'info_and_logs',
					'sub_tab_id' => 'debug',
				],
			],
		];

		return self::error(
			sprintf( 'Uncaught exception in %s.', $method_name ),
			self::INTERNAL_SERVER_ERROR,
			$failure_code,
			$extra_data
		);

	}

	/**
	 * Return REST scan heartbeat response.
	 */
	public static function heartbeat( $message = "", $heartbeat_code = "", $data = [] ) {

		return new WP_REST_Response(
			[ 
				'success' => true,
				'message' => $message,
				'heartbeat_code' => $heartbeat_code,
				'data' => $data,
				"extra_data" => [ 
					'message_links' => [ 
						[ 
							'text' => __( 'Check the logs', 'advanced-database-cleaner' ),
							'tab_id' => 'info_and_logs',
							'sub_tab_id' => 'debug',
						],
					],
				],
			],
			self::OK
		);

	}

}