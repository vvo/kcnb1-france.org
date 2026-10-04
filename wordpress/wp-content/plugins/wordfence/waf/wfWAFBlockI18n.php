<?php
require_once __DIR__ . '/wfWAFBlockConstants.php';

class wfWAFBlockI18n implements wfWAFBlockConstants {
	
	protected static function formatBlockDescription($translated, $key) {
		$parameters = array();
		$formatString = $translated ? self::getBlockFormatString($key) : $key;
		if ($formatString === null) {
			$parsed = self::parseBlockKey($key, $parameters);
			if ($parsed === null) {
				return $key;
			}
			$formatString = $translated ? self::getBlockFormatString($parsed) : $key;
		} else {
			if (func_num_args() <= 2) {
				return $formatString;
			}
			$parameters = array_slice(func_get_args(), 2);
		}
		array_unshift($parameters, $formatString);
		return call_user_func_array('sprintf', $parameters);
	}
	
	public static function getTranslatedBlockDescription($key) {
		$args = func_get_args();
		array_unshift($args, true);
		return call_user_func_array(array(get_called_class(), 'formatBlockDescription'), $args);
	}
	
	/**
	 * Translate without WordPress, tolerating an older parent site's WAF.
	 * @param string $text Original message.
	 * @param string $domain Text domain retained for translation extraction.
	 * @return string Translated message, or the original when WAF translation is unavailable.
	 */
	private static function __($text, $domain) {
		if (class_exists('wfWAFI18n') && method_exists('wfWAFI18n', '__')) {
			return wfWAFI18n::__($text);
		}
		return $text;
	}

	protected static function getBlockFormatString($key) {
		switch ($key) {
			case self::WFWAF_BLOCK_UAREFIPRANGE:
				return self::__('UA/Referrer/IP Range not allowed', 'wordfence');
			case self::WFWAF_BLOCK_COUNTRY:
				return self::__('blocked access via country blocking', 'wordfence');
			case self::WFWAF_BLOCK_COUNTRY_REDIR:
				return /* translators: URL */ self::__('blocked access via country blocking and redirected to URL (%s)', 'wordfence');
			case self::WFWAF_BLOCK_COUNTRY_BYPASS_REDIR:
				return self::__('redirected to bypass URL', 'wordfence');
			case self::WFWAF_BLOCK_WFSN:
				return self::__('Blocked by Wordfence Security Network', 'wordfence');
			case self::WFWAF_BLOCK_BADPOST:
				return self::__('POST received with blank user-agent and referer', 'wordfence');
			case self::WFWAF_BLOCK_BANNEDURL:
				return self::__('Accessed a banned URL', 'wordfence');
			case self::WFWAF_BLOCK_INVALIDUSERNAME:
				/* translators: WordPress username. */
				return self::__("Used an invalid username '%s' to try to sign in", 'wordfence');
			case self::WFWAF_BLOCK_LOGINSEC:
				return self::__('Blocked by login security setting', 'wordfence');
			case self::WFWAF_BLOCK_LOGINSEC_FORGOTPASSWD:
				/* translators: 1. Password reset limit (number). 2. WordPress username. */
				return self::__('Exceeded the maximum number of tries to recover their password which is set at: %1$s. The last username or email they entered before getting locked out was: \'%2$s\'', 'wordfence');
			case self::WFWAF_BLOCK_LOGINSEC_FAILURES:
				/* translators: 1. Login attempt limit. 2. WordPress username. */
				return self::__('Exceeded the maximum number of login failures which is: %1$s. The last username they tried to sign in with was: \'%2$s\'', 'wordfence');
			case self::WFWAF_BLOCK_MANUAL:
				return self::__('Manual block by administrator', 'wordfence');
			case self::WFWAF_BLOCK_THROTTLEGLOBAL:
				return self::__('Exceeded the maximum global requests per minute for crawlers or humans.', 'wordfence');
			case self::WFWAF_BLOCK_THROTTLECRAWLER:
				return self::__('Exceeded the maximum number of requests per minute for crawlers.', 'wordfence');
			case self::WFWAF_BLOCK_THROTTLECRAWLERNOTFOUND:
				return self::__('Exceeded the maximum number of page not found errors per minute for a crawler.', 'wordfence');
			case self::WFWAF_BLOCK_THROTTLEHUMAN:
				return self::__('Exceeded the maximum number of page requests per minute for humans.', 'wordfence');
			case self::WFWAF_BLOCK_THROTTLEHUMANNOTFOUND:
				return self::__('Exceeded the maximum number of page not found errors per minute for humans.', 'wordfence');
			default:
				return null;
		}
	}
	
	protected static function parseBlockKey($string, &$parameters = null) {
		if ($string !== null) {
			$formatPatternStrings = array(
				self::WFWAF_BLOCK_COUNTRY_REDIR => self::WFWAF_BLOCK_COUNTRY_REDIR_REGEX,
				self::WFWAF_BLOCK_INVALIDUSERNAME => self::WFWAF_BLOCK_INVALIDUSERNAME_REGEX,
				self::WFWAF_BLOCK_LOGINSEC_FORGOTPASSWD => self::WFWAF_BLOCK_LOGINSEC_FORGOTPASSWD_REGEX,
				self::WFWAF_BLOCK_LOGINSEC_FAILURES => self::WFWAF_BLOCK_LOGINSEC_FAILURES_REGEX,
			);
			foreach ($formatPatternStrings as $formatString => $pattern) {
				$flags = 0;
				if (defined('PREG_UNMATCHED_AS_NULL')) {
					$flags = PREG_UNMATCHED_AS_NULL;
				}
				if (preg_match($pattern, $string, $matches, $flags)) {
					array_shift($matches);
					$parameters = $matches;
					return $formatString;
				}
			}
		}
		
		$parameters = null;
		return null;
	}
	
	/**
	 * Map the WFWAF_BLOCK_ constants to translated strings
	 * (The constants are string descriptions for legacy reasons and should
	 *  now be regarded as identifiers)
	 * @param string $key the WFWAF_BLOCK_ constant
	 * @param ...$parameters any format parameters to pass to the description
	 * @return string a translated description when available, otherwise the original text
	 */
	public static function getBlockDescription($key) {
		$args = func_get_args();
		array_unshift($args, false);
		return call_user_func_array(array(get_called_class(), 'formatBlockDescription'), $args);
	}
	
	public static function matchesBlockKey($string, $key) {
		if ($key === $string)
			return true;
		return self::parseBlockKey($string) === $key;
	}
}