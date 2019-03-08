<?php
/**
 * The Search Console Client
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Search_Console;

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Client class.
 */
class Client {

	/**
	 * Is client authorized the oAuth2.
	 *
	 * @var boolean
	 */
	public $is_authorized = null;

	/**
	 * Hold data.
	 *
	 * @var array
	 */
	public $data = array();

	/**
	 * Hold selected profile.
	 *
	 * @var string
	 */
	public $profile;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->set_data();
	}

	/**
	 * Make an HTTP GET request - for retrieving data.
	 *
	 * @param  string $url     URL to do request.
	 * @param  array  $args    Assoc array of arguments (usually your data).
	 * @param  int    $timeout Timeout limit for request in seconds.
	 * @return array|false     Assoc array of API response, decoded from JSON.
	 */
	public function get( $url, $args = array(), $timeout = 10 ) {
		return $this->make_request( 'get', $url, $args, $timeout );
	}

	/**
	 * Make an HTTP POST request - for creating and updating items.
	 *
	 * @param  string $url     URL to do request.
	 * @param  array  $args    Assoc array of arguments (usually your data).
	 * @param  int    $timeout Timeout limit for request in seconds.
	 * @return array|false     Assoc array of API response, decoded from JSON.
	 */
	public function post( $url, $args = array(), $timeout = 10 ) {
		return $this->make_request( 'post', $url, $args, $timeout );
	}

	/**
	 * Make an HTTP PUT request - for creating new items.
	 *
	 * @param  string $url     URL to do request.
	 * @param  array  $args    Assoc array of arguments (usually your data).
	 * @param  int    $timeout Timeout limit for request in seconds.
	 * @return array|false     Assoc array of API response, decoded from JSON.
	 */
	public function put( $url, $args = array(), $timeout = 10 ) {
		return $this->make_request( 'put', $url, $args, $timeout );
	}

	/**
	 * Make an HTTP DELETE request - for deleting data.
	 *
	 * @param  string $url     URL to do request.
	 * @param  array  $args    Assoc array of arguments (usually your data).
	 * @param  int    $timeout Timeout limit for request in seconds.
	 * @return array|false     Assoc array of API response, decoded from JSON.
	 */
	public function delete( $url, $args = array(), $timeout = 10 ) {
		return $this->make_request( 'delete', $url, $args, $timeout );
	}

	/**
	 * Performs the underlying HTTP request. Not very exciting.
	 *
	 * @param string $http_verb The HTTP verb to use: get, post, put, patch, delete.
	 * @param string $url       URL to do request.
	 * @param array  $args       Assoc array of parameters to be passed.
	 * @param int    $timeout    Timeout limit for request in seconds.
	 * @return array|false Assoc array of decoded result.
	 */
	private function make_request( $http_verb, $url, $args = array(), $timeout = 10 ) {

		if ( ! isset( $this->data['access_token'] ) ) {
			return false;
		}

		$params = array(
			'timeout' => $timeout,
			'method'  => $http_verb,
			'headers' => array( 'Authorization' => 'Bearer ' . $this->data['access_token'] ),
		);

		if ( 'DELETE' === $http_verb || 'PUT' === $http_verb ) {
			$params['headers']['Content-Length'] = '0';
		} elseif ( 'post' === $http_verb && ! empty( $args ) && is_array( $args ) ) {
			$params['body']                    = wp_json_encode( $args );
			$params['headers']['Content-Type'] = 'application/json';
		}

		$response = wp_remote_request( $url, $params );

		return $this->process_response( $response );
	}

	/**
	 * Process api response.
	 *
	 * @param  array $response Api response array.
	 * @return array
	 */
	public function process_response( $response ) {
		if ( ! is_wp_error( $response ) ) {
			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			if ( ! empty( $body ) ) {
				$body = json_decode( $body, true );
			}

			if ( 200 === $code || 204 === $code ) {
				return array(
					'status' => 'success',
					'code'   => '200',
					'body'   => $body,
				);
			}

			if ( isset( $body['error_description'] ) && 'Bad Request' === $body['error_description'] ) {
				$body['error_description'] = esc_html__( 'Bad request. Please check the code.', 'rank-math' );
			}

			return array(
				'status' => 'fail',
				'code'   => $code,
				'body'   => $body,
			);
		}

		return array(
			'status' => 'fail',
			'code'   => $response->get_error_code(),
			'body'   => array( 'error_description' => 'WP_Error: ' . $response->get_error_message() ),
		);
	}

	/**
	 * Fetch profiles api wrapper.
	 *
	 * @return array
	 */
	public function fetch_profiles() {
		$profiles = array();

		if ( ! $this->is_authorized ) {
			return $profiles;
		}

		$response = $this->get( 'https://www.googleapis.com/webmasters/v3/sites' );
		if ( 'success' === $response['status'] ) {
			foreach ( $response['body']['siteEntry'] as $site ) {
				$profiles[ $site['siteUrl'] ] = $site['siteUrl'];
			}
			Helper::search_console_data( array(
				'profiles' => $profiles,
			));
		} else {
			$this->error_notice( $response, true );
		}

		return $profiles;
	}

	/**
	 * Fetch access token
	 *
	 * @param string $code oAuth token.
	 * @return array
	 */
	public function fetch_access_token( $code ) {
		$config = Helper::get_console_api_config();

		$response = wp_remote_post( $config['token_url'], array(
			'body'    => array(
				'code'          => $code,
				'client_id'     => $config['client_id'],
				'client_secret' => $config['client_secret'],
				'redirect_uri'  => $config['redirect_uri'],
				'grant_type'    => 'authorization_code',
			),
			'timeout' => 15,
		) );

		$data = $this->process_response( $response );
		if ( 'success' === $data['status'] ) {
			Helper::search_console_data( array(
				'authorized'    => true,
				'expire'        => time() + $data['body']['expires_in'],
				'access_token'  => $data['body']['access_token'],
				'refresh_token' => $data['body']['refresh_token'],
			));
		}

		$this->set_data();

		return $data;
	}

	/**
	 * Maybe we need to refresh the token before processing api request.
	 */
	public function maybe_refresh_token() {
		if ( ! isset( $this->data['expire'] ) ) {
			return;
		}

		$expire = $this->data['expire'];

		// If it has expired or does so in the next 30 seconds then refresh token.
		if ( $expire && time() > ( $expire - 120 ) ) {
			$new_token = $this->refresh_token();
			if ( 'success' !== $new_token['status'] ) {
				$this->error_notice( $new_token, true );
			}
		}
	}

	/**
	 * Refresh token using saved data.
	 *
	 * @return array
	 */
	public function refresh_token() {
		$config = Helper::get_console_api_config();

		$response = wp_remote_post( $config['token_url'], array(
			'body'    => array(
				'refresh_token' => $this->data['refresh_token'],
				'client_id'     => $config['client_id'],
				'client_secret' => $config['client_secret'],
				'grant_type'    => 'refresh_token',
			),
			'timeout' => 15,
		) );

		$data = $this->process_response( $response );
		if ( 'success' === $data['status'] ) {
			Helper::search_console_data( array(
				'expire'       => time() + $data['body']['expires_in'],
				'access_token' => $data['body']['access_token'],
			));
		}

		return $data;
	}

	/**
	 * Disconnect client connection.
	 */
	public function disconnect() {

		Helper::search_console_data( false );
		add_option( 'rank_math_search_console_data', array(
			'authorized' => false,
			'profiles'   => array(),
		) );

		$this->set_data();
	}

	/**
	 * Fetch sitemaps.
	 *
	 * @param  boolean $with_index With index data.
	 * @param  boolean $force      Purge cache and fetch new data.
	 * @return array
	 */
	public function fetch_sitemaps( $with_index = false, $force = false ) {

		if ( empty( $this->profile ) ) {
			return array();
		}

		$key      = $this->generate_key( 'sitemaps', ( $with_index ? 'index' : '' ) );
		$sitemaps = get_transient( $key );
		if ( $force || false === $sitemaps ) {
			$with_index = $with_index ? '?sitemapIndex=' . urlencode( trailingslashit( $this->profile ) . 'sitemap_index.xml' ) : '';
			$response   = $this->get( 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode( $this->profile ) . '/sitemaps' . $with_index );

			if ( 'success' === $response['status'] ) {
				$sitemaps = $response['body']['sitemap'];
				set_transient( $key, $sitemaps, DAY_IN_SECONDS );
			} else {
				Helper::add_notification( $response['body']['error']['message'] );
			}
		}

		return $sitemaps ? $sitemaps : array();
	}

	/**
	 * Submit sitemap to search console.
	 *
	 * @param  string $sitemap Sitemap url.
	 * @return array
	 */
	public function submit_sitemap( $sitemap ) {
		return $this->put( 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode( $this->profile ) . '/sitemaps/' . urlencode( $sitemap ) );
	}

	/**
	 * Delete sitemap from search console.
	 *
	 * @param  string $sitemap Sitemap url.
	 * @return array
	 */
	public function delete_sitemap( $sitemap ) {
		return $this->delete( 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode( $this->profile ) . '/sitemaps/' . urlencode( $sitemap ) );
	}

	/**
	 * Generate deferred error notice.
	 *
	 * @param array|string $response   Request response which contain error as well.
	 * @param boolean      $disconnect Either disconnect client.
	 */
	public function error_notice( $response, $disconnect = false ) {

		return;
		if ( $disconnect ) {
			$this->disconnect();
		}

		$message = false;
		if ( is_string( $response ) ) {
			$message = $response;
		} elseif ( is_array( $response ) ) {
			if ( isset( $response['body']['error']['message'] ) ) {
				$message = $response['body']['error']['message'];
			} elseif ( isset( $response['body']['error_description'] ) ) {
				$message = $response['body']['error_description'];
			}
		}

		if ( false === $message ) {
			return;
		}

		Helper::add_notification( $message, [ 'type' => 'error' ] );
	}

	/**
	 * Set data.
	 */
	private function set_data() {
		$this->data          = Helper::search_console_data();
		$this->is_authorized = $this->data['authorized'] && $this->data['access_token'] && $this->data['refresh_token'];
		$this->profile       = Helper::get_settings( 'general.console_profile' );

		if ( ! $this->profile && ! empty( $this->data['profiles'] ) ) {
			$this->profile = key( $this->data['profiles'] );
		}
		$this->profile_salt = $this->profile ? md5( $this->profile ) : '';
	}

	/**
	 * Generate Cache Keys.
	 *
	 * @param  string $what What for you need the key.
	 * @param  mixed  $args more salt to add into key.
	 * @return string
	 */
	public function generate_key( $what, $args = array() ) {
		$key = '_rank_math_' . $this->profile_salt . '_sc_' . $what;

		if ( ! empty( $args ) ) {
			$key .= '_' . join( '_', (array) $args );
		}

		return $key;
	}
}
