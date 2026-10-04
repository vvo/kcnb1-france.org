<?php

namespace WordfenceLS;

use WordfenceLS\Crypto\Model_JWT;
use WordfenceLS\Settings\Model_DB;

class Utility_URL {
	const PUBLIC_SUFFIX_LIST_URL = 'https://publicsuffix.org/list/public_suffix_list.dat';
	const PUBLIC_SUFFIX_LIST_VALIDATION_ENTRY = 'co\\.uk';
	const PUBLIC_SUFFIX_LIST_UPDATE_STATUS_UPDATED = 'updated';
	const PUBLIC_SUFFIX_LIST_UPDATE_STATUS_UP_TO_DATE = 'up_to_date';
	const PUBLIC_SUFFIX_LIST_UPDATE_STATUS_RP_CHANGED = 'rp_changed';
	const PUBLIC_SUFFIX_LIST_UPDATE_STATUS_INVALID = 'invalid';
	const PUBLIC_SUFFIX_LIST_UPDATE_STATUS_ERROR = 'error';
	const PUBLIC_SUFFIX_LIST_PENDING_UPDATE_TRANSIENT_PREFIX = 'wfls-public-suffix-update-';
	const PUBLIC_SUFFIX_LIST_PENDING_UPDATE_DURATION = 600;
	const PUBLIC_SUFFIX_LIST_FALLBACK = array(
		'com',
		'org',
		'net',
		'edu',
		'gov',
		'mil',
		'int',
		'app',
		'dev',
		'page',
		'blog',
		'shop',
		'store',
		'site',
		'online',
		'info',
		'biz',
		'io',
		'ai',
		'me',
		'co',
		'xyz',
		'cloud',
		'tech',
		'us',
		'uk',
		'ca',
		'au',
		'nz',
		'jp',
		'kr',
		'in',
		'cn',
		'hk',
		'sg',
		'mx',
		'br',
		'za',
		'de',
		'fr',
		'it',
		'es',
		'nl',
		'se',
		'no',
		'dk',
		'fi',
		'ie',
		'ch',
		'at',
		'be',
		'pl',
		'pt',
		'cz',
		'gr',
		'il',
		'tr',
		'ua',
		'ro',
		'hu',
		'ac.uk',
		'co.uk',
		'gov.uk',
		'ltd.uk',
		'me.uk',
		'net.uk',
		'org.uk',
		'plc.uk',
		'sch.uk',
		'asn.au',
		'com.au',
		'edu.au',
		'gov.au',
		'id.au',
		'net.au',
		'org.au',
		'ac.jp',
		'co.jp',
		'ed.jp',
		'go.jp',
		'gr.jp',
		'lg.jp',
		'ne.jp',
		'or.jp',
		'ac.nz',
		'co.nz',
		'geek.nz',
		'gen.nz',
		'govt.nz',
		'iwi.nz',
		'kiwi.nz',
		'maori.nz',
		'net.nz',
		'org.nz',
		'school.nz',
		'ac.kr',
		'co.kr',
		'go.kr',
		'ne.kr',
		'or.kr',
		'pe.kr',
		're.kr',
		'ac.in',
		'co.in',
		'edu.in',
		'firm.in',
		'gen.in',
		'gov.in',
		'ind.in',
		'mil.in',
		'net.in',
		'nic.in',
		'org.in',
		'res.in',
		'co.za',
		'edu.za',
		'gov.za',
		'net.za',
		'org.za',
		'school.za',
		'web.za',
		'ac.cn',
		'com.cn',
		'edu.cn',
		'gov.cn',
		'net.cn',
		'org.cn',
		'com.hk',
		'edu.hk',
		'gov.hk',
		'idv.hk',
		'net.hk',
		'org.hk',
		'com.mx',
		'edu.mx',
		'gob.mx',
		'net.mx',
		'org.mx',
		'com.sg',
		'edu.sg',
		'gov.sg',
		'net.sg',
		'org.sg',
		'per.sg',
		'com.br',
		'edu.br',
		'gov.br',
		'mil.br',
		'net.br',
		'org.br',
	);

	private static $_defaultPublicSuffixList = null;
	private static $_publicSuffixList = null;
	private static $_publicSuffixListEtag = false;
	
	/**
	 * Similar to WordPress' `admin_url`, this returns a host-relative URL for the given path. It may be used to avoid
	 * canonicalization issues with CORS (e.g., the site is configured for the www. variant of the URL but doesn't forward
	 * the other).
	 * 
	 * @param string $path
	 * @return string
	 */
	public static function relative_admin_url($path = '') {
		$url = admin_url($path);
		$components = parse_url($url);
		$s = $components['path'];
		if (!empty($components['query'])) {
			$s .= '?' . $components['query'];
		}
		if (!empty($components['fragment'])) {
			$s .= '#' . $components['fragment'];
		}
		return $s;
	}
	
	/**
	 * Convenience function to generate an admin URL using the appropriate function depending on multisite status.
	 *
	 * @param string $path
	 * @return string
	 */
	public static function maybe_network_admin_url($path) {
		return function_exists('network_admin_url') && is_multisite() ? network_admin_url($path) : admin_url($path);
	}

	/**
	 * Returns the URL's registrable domain by reducing it to the first component left of the most-specific public
	 * suffix.
	 *
	 * @param string $url
	 * @param string[]|null $publicSuffixList
	 * @return string
	 */
	public static function reduce_to_public_suffix_plus_one($url, $publicSuffixList = null) {
		$host = self::_extract_host($url);
		if ($host === '') {
			return '';
		}

		if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
			return $host;
		}

		$labels = explode('.', $host);
		if (count($labels) <= 1) {
			return $host;
		}

		$publicSuffix = self::_get_most_specific_public_suffix($host, $publicSuffixList);
		if ($publicSuffix === '') {
			$publicSuffix = end($labels);
		}

		$suffixLabels = explode('.', $publicSuffix);
		if (count($labels) <= count($suffixLabels)) {
			return $host;
		}

		return implode('.', array_slice($labels, -(count($suffixLabels) + 1)));
	}

	/**
	 * Returns the built-in fallback public suffix seed in the same normalized format as the fetched PSL data.
	 *
	 * @return string[]
	 */
	public static function get_default_public_suffix_list() {
		if (self::$_defaultPublicSuffixList === null) {
			self::$_defaultPublicSuffixList = self::_normalize_public_suffix_entries(self::PUBLIC_SUFFIX_LIST_FALLBACK);
		}
		return self::$_defaultPublicSuffixList;
	}

	/**
	 * Returns the cached public suffix list if present and valid, otherwise the built-in fallback list.
	 *
	 * @return string[]
	 */
	public static function get_public_suffix_list() {
		if (self::$_publicSuffixList !== null) {
			return self::$_publicSuffixList;
		}

		$cached = self::_get_cached_public_suffix_list();
		if ($cached !== null) {
			self::$_publicSuffixList = $cached;
			return self::$_publicSuffixList;
		}

		self::$_publicSuffixList = self::get_default_public_suffix_list();
		return self::$_publicSuffixList;
	}

	/**
	 * Returns whether a fetched public suffix list is present in settings storage and passes validation.
	 *
	 * @return bool
	 */
	public static function has_cached_public_suffix_list() {
		return self::_get_cached_public_suffix_list() !== null;
	}

	/**
	 * Fetches and caches the public suffix list only when there is no valid cached copy.
	 *
	 * @return array|false The normalized suffix list on success, or false on failure.
	 */
	public static function fetch_and_cache_public_suffix_list_if_missing() {
		$cached = self::_get_cached_public_suffix_list();
		if ($cached !== null) {
			self::$_publicSuffixList = $cached;
			return $cached;
		}

		return self::fetch_and_cache_public_suffix_list();
	}

	/**
	 * Fetches, normalizes, validates, and caches the public suffix list in plugin settings storage.
	 *
	 * A new value is only written if the fetch succeeds and the processed list passes a basic validation check.
	 *
	 * @return array|false The normalized suffix list on success, or false on failure.
	 */
	public static function fetch_and_cache_public_suffix_list() {
		$result = self::_fetch_and_cache_public_suffix_list(false, true);
		if (
			($result['status'] === self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_UPDATED || $result['status'] === self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_UP_TO_DATE)
			&& isset($result['data'])
			&& is_array($result['data'])
		) {
			return $result['data'];
		}

		return false;
	}

	/**
	 * Fetches and caches the public suffix list, returning a structured status for manual update flows.
	 *
	 * @return array
	 */
	public static function fetch_public_suffix_list_update() {
		return self::_fetch_and_cache_public_suffix_list(true, false);
	}

	/**
	 * Saves a pending public suffix list update that was blocked because it would change the default passkey RP.
	 *
	 * @param string $token
	 * @return array
	 */
	public static function save_pending_public_suffix_list_update($token) {
		if (!is_string($token) || !preg_match('/^[A-Za-z0-9_-]{32,}$/', $token)) {
			return array('status' => self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_ERROR);
		}

		$key = self::PUBLIC_SUFFIX_LIST_PENDING_UPDATE_TRANSIENT_PREFIX . $token;
		$pending = get_transient($key);
		if (!is_array($pending) || empty($pending['data']) || !is_array($pending['data'])) {
			return array('status' => self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_ERROR);
		}

		$data = $pending['data'];
		if (empty($data) || !in_array(self::PUBLIC_SUFFIX_LIST_VALIDATION_ENTRY, $data, true)) {
			delete_transient($key);
			return array('status' => self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_INVALID);
		}

		$etag = isset($pending['etag']) && is_string($pending['etag']) ? $pending['etag'] : '';
		if (!self::_cache_public_suffix_list($data, $etag)) {
			return array('status' => self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_ERROR);
		}

		delete_transient($key);
		return array(
			'status' => self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_UPDATED,
			'data' => $data,
		);
	}

	/**
	 * Fetches, normalizes, validates, and caches the public suffix list with structured result metadata.
	 *
	 * @param bool $createPendingUpdate
	 * @param bool $sendEtagCacheHeader
	 * @return array
	 */
	private static function _fetch_and_cache_public_suffix_list($createPendingUpdate, $sendEtagCacheHeader) {
		$settings = Controller_Settings::shared();
		$cached = self::_get_cached_public_suffix_list();
		$hasValidCachedList = $cached !== null;
		$etag = self::_get_public_suffix_list_etag();
		$headers = array();
		if ($sendEtagCacheHeader && $hasValidCachedList && $etag !== '') {
			$headers['If-None-Match'] = $etag;
		}
		else if (!$hasValidCachedList && $etag !== '') {
			$settings->remove(Controller_Settings::OPTION_PUBLIC_SUFFIX_LIST_ETAG);
			self::$_publicSuffixListEtag = '';
		}

		$response = wp_remote_get(self::PUBLIC_SUFFIX_LIST_URL, array(
			'headers' => $headers,
			'timeout' => 15,
		));
		if (is_wp_error($response)) {
			return array('status' => self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_ERROR);
		}

		$status = wp_remote_retrieve_response_code($response);
		if ($status === 304) {
			return array(
				'status' => self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_UP_TO_DATE,
				'data' => self::get_public_suffix_list(),
			);
		}
		if ($status !== 200) {
			return array('status' => self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_ERROR);
		}

		$data = self::_normalize_public_suffix_list(wp_remote_retrieve_body($response));
		if (empty($data) || !in_array(self::PUBLIC_SUFFIX_LIST_VALIDATION_ENTRY, $data, true)) {
			return array('status' => self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_INVALID);
		}
		if (self::_would_public_suffix_update_change_last_passkey_rp($data)) {
			if (!$createPendingUpdate) {
				self::_store_public_suffix_list_etag($response);
			}
			$result = array('status' => self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_RP_CHANGED);
			if ($createPendingUpdate) {
				$token = self::_store_pending_public_suffix_list_update($data, $response);
				if ($token === false) {
					return array('status' => self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_ERROR);
				}
				$result['token'] = $token;
			}
			return $result;
		}

		if (!self::_cache_public_suffix_list($data, wp_remote_retrieve_header($response, 'etag'))) {
			return array('status' => self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_ERROR);
		}

		return array(
			'status' => self::PUBLIC_SUFFIX_LIST_UPDATE_STATUS_UPDATED,
			'data' => $data,
		);
	}

	/**
	 * Removes comments and excess whitespace, lowercases entries, escapes them for regex use, and sorts more specific
	 * suffixes first.
	 *
	 * @param string $data
	 * @return string[]
	 */
	private static function _normalize_public_suffix_list($data) {
		if (!is_string($data) || $data === '') {
			return array();
		}

		$data = preg_replace('/^\s*\/\/.*$/m', '', $data);
		$data = preg_split('/\s+/', $data);
		return self::_normalize_public_suffix_entries($data);
	}

	/**
	 * Lowercases entries, escapes them for regex use, removes empties and duplicates, and sorts more specific suffixes
	 * first.
	 *
	 * @param array $data
	 * @return string[]
	 */
	private static function _normalize_public_suffix_entries($data) {
		if (!is_array($data)) {
			return array();
		}

		$data = array_filter(array_map(function($suffix) {
			return is_string($suffix) ? trim($suffix) : '';
		}, $data));
		$data = array_map(function($suffix) {
			return strtolower(preg_quote($suffix));
		}, $data);
		$data = str_replace('\\*', '[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?', $data);
		$data = array_values(array_unique($data));
		usort($data, function($a, $b) {
			$countA = substr_count($a, '\\.');
			$countB = substr_count($b, '\\.');
			if ($countA > $countB) {
				return -1;
			}
			if ($countA < $countB) {
				return 1;
			}
			return strcmp($a, $b);
		});

		return $data;
	}

	/**
	 * Returns the stored public suffix list only when it passes the current validation check.
	 *
	 * @return string[]|null
	 */
	private static function _get_cached_public_suffix_list() {
		$cached = Controller_Settings::shared()->get_array(Controller_Settings::OPTION_PUBLIC_SUFFIX_LIST, array());
		if (!empty($cached) && in_array(self::PUBLIC_SUFFIX_LIST_VALIDATION_ENTRY, $cached, true)) {
			return $cached;
		}
		return null;
	}

	/**
	 * Stores a pending PSL update server-side until an administrator confirms saving an RP-changing update.
	 *
	 * @param string[] $data
	 * @param array $response
	 * @return string|false
	 */
	private static function _store_pending_public_suffix_list_update($data, $response) {
		$token = Model_JWT::base64url_encode(Model_Crypto::random_bytes(32));
		$etag = wp_remote_retrieve_header($response, 'etag');
		$pending = array(
			'data' => $data,
			'etag' => is_string($etag) ? $etag : '',
		);

		if (!set_transient(self::PUBLIC_SUFFIX_LIST_PENDING_UPDATE_TRANSIENT_PREFIX . $token, $pending, self::PUBLIC_SUFFIX_LIST_PENDING_UPDATE_DURATION)) {
			return false;
		}

		return $token;
	}

	/**
	 * Stores the validated public suffix list and the associated ETag.
	 *
	 * @param string[] $data
	 * @param string $etag
	 * @return bool
	 */
	private static function _cache_public_suffix_list($data, $etag) {
		$json = json_encode($data);
		if ($json === false) {
			return false;
		}

		$storage = new Model_DB();
		$storage->set(
			Controller_Settings::OPTION_PUBLIC_SUFFIX_LIST,
			$json,
			Model_Settings::AUTOLOAD_NO
		);
		self::_store_public_suffix_list_etag_value($etag, $storage);
		self::$_publicSuffixList = $data;

		return true;
	}

	/**
	 * Extracts and normalizes a hostname from a URL or host-like string.
	 *
	 * @param string $url
	 * @return string
	 */
	private static function _extract_host($url) {
		if (!is_string($url)) {
			return '';
		}

		$url = trim($url);
		if ($url === '') {
			return '';
		}

		$parts = wp_parse_url($url);
		if (is_array($parts) && !empty($parts['host'])) {
			return self::_normalize_host($parts['host']);
		}

		if (is_array($parts) && !empty($parts['path']) && preg_match('/^[^\/\s:?#]+$/', $parts['path'])) {
			return self::_normalize_host($parts['path']);
		}

		return '';
	}

	/**
	 * Returns the most-specific public suffix that matches the hostname.
	 *
	 * Examples:
	 * - `www.example.com` => `com`
	 * - `www.example.co.uk` => `co.uk`
	 * - `www.example.kawasaki.jp` => `example.kawasaki.jp` for a wildcard rule like `*.kawasaki.jp`
	 * - `www.city.kawasaki.jp` => `kawasaki.jp` for an exception rule like `!city.kawasaki.jp`
	 *
	 * @param string $host
	 * @param string[]|null $publicSuffixList
	 * @return string
	 */
	private static function _get_most_specific_public_suffix($host, $publicSuffixList = null) {
		$host = self::_normalize_host($host);
		if ($host === '') {
			return '';
		}

		$rules = is_array($publicSuffixList) ? $publicSuffixList : self::get_public_suffix_list();
		$bestMatch = '';
		$bestExceptionMatch = '';
		foreach ($rules as $rule) {
			$exception = strpos($rule, '\\!') === 0;
			$pattern = $exception ? substr($rule, 2) : $rule;
			if (!preg_match('/(?:^|\.)' . $pattern . '$/i', $host)) {
				continue;
			}

			$matched = preg_replace('/^.*?((?:' . $pattern . '))$/i', '$1', $host);
			if (!is_string($matched) || $matched === '') {
				continue;
			}

			if ($exception) {
				if (self::_is_more_specific_public_suffix_match($matched, $bestExceptionMatch)) {
					$bestExceptionMatch = $matched;
				}
			}
			else if (self::_is_more_specific_public_suffix_match($matched, $bestMatch)) {
				$bestMatch = $matched;
			}
		}

		if ($bestExceptionMatch !== '') {
			$labels = explode('.', $bestExceptionMatch);
			if (count($labels) <= 1) {
				return $bestExceptionMatch;
			}
			return implode('.', array_slice($labels, 1));
		}
		if ($bestMatch !== '') {
			return $bestMatch;
		}

		$labels = explode('.', $host);
		return end($labels);
	}

	/**
	 * Returns whether the candidate match is more specific than the current best match.
	 *
	 * @param string $candidate
	 * @param string $current
	 * @return bool
	 */
	private static function _is_more_specific_public_suffix_match($candidate, $current) {
		if ($candidate === '') {
			return false;
		}
		if ($current === '') {
			return true;
		}

		$candidateLabels = substr_count($candidate, '.') + 1;
		$currentLabels = substr_count($current, '.') + 1;
		if ($candidateLabels !== $currentLabels) {
			return $candidateLabels > $currentLabels;
		}

		return strlen($candidate) > strlen($current);
	}

	/**
	 * Normalizes a host string to lowercase without a trailing dot.
	 *
	 * @param string $host
	 * @return string
	 */
	private static function _normalize_host($host) {
		if (!is_string($host)) {
			return '';
		}
		return strtolower(rtrim(trim($host), '.'));
	}

	/**
	 * Returns the stored ETag for the cached public suffix list, if any.
	 *
	 * @return string
	 */
	private static function _get_public_suffix_list_etag() {
		if (self::$_publicSuffixListEtag !== false) {
			return self::$_publicSuffixListEtag;
		}

		$etag = Controller_Settings::shared()->get(Controller_Settings::OPTION_PUBLIC_SUFFIX_LIST_ETAG, '');
		self::$_publicSuffixListEtag = is_string($etag) ? $etag : '';
		return self::$_publicSuffixListEtag;
	}

	/**
	 * Returns whether replacing the cached public suffix list would change the default passkey RP for the last
	 * successful passkey registration.
	 *
	 * @param string[] $publicSuffixList
	 * @return bool
	 */
	private static function _would_public_suffix_update_change_last_passkey_rp($publicSuffixList) {
		if (!is_array($publicSuffixList)) {
			return false;
		}

		$settings = Controller_Settings::shared();
		if ($settings->get(Controller_Settings::OPTION_PASSKEY_RELYING_PARTY_OVERRIDE, '') !== '') {
			return false;
		}

		$lastPasskeyRP = $settings->get(Controller_Settings::OPTION_LAST_PASSKEY_RP, '');
		if (!is_string($lastPasskeyRP) || $lastPasskeyRP === '') {
			return false;
		}

		$newRP = self::reduce_to_public_suffix_plus_one(home_url(), $publicSuffixList);
		if ($newRP === '') {
			$newRP = self::reduce_to_public_suffix_plus_one(site_url(), $publicSuffixList);
		}
		if ($newRP === '') {
			return false;
		}

		return $lastPasskeyRP !== $newRP;
	}

	/**
	 * Stores or clears the cached public suffix list ETag based on the response headers.
	 *
	 * @param array $response
	 * @param Model_DB|null $storage
	 * @return void
	 */
	private static function _store_public_suffix_list_etag($response, $storage = null) {
		self::_store_public_suffix_list_etag_value(wp_remote_retrieve_header($response, 'etag'), $storage);
	}

	/**
	 * Stores or clears the cached public suffix list ETag.
	 *
	 * @param string $newEtag
	 * @param Model_DB|null $storage
	 * @return void
	 */
	private static function _store_public_suffix_list_etag_value($newEtag, $storage = null) {
		if (!$storage) {
			$storage = new Model_DB();
		}

		if (is_string($newEtag) && $newEtag !== '') {
			$storage->set(
				Controller_Settings::OPTION_PUBLIC_SUFFIX_LIST_ETAG,
				$newEtag,
				Model_Settings::AUTOLOAD_NO
			);
			self::$_publicSuffixListEtag = $newEtag;
		}
		else {
			$storage->remove(Controller_Settings::OPTION_PUBLIC_SUFFIX_LIST_ETAG);
			self::$_publicSuffixListEtag = '';
		}
	}
}
