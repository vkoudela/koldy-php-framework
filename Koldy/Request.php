<?php namespace Koldy;

class Request {

	/**
	 * Get the ip address of remote user
	 * @return string
	 */
	public static function getRemoteAddr() {
		return isset($_SERVER['REMOTE_ADDR'])
			? $_SERVER['REMOTE_ADDR']
			: '127.0.0.1';
	}

	/**
	 * Get the ip address of remote user
	 * @return string
	 */
	public static function ip() {
		return self::getRemoteAddr();
	}

	/**
	 * Get the host name of remote user
	 * @return string|null
	 */
	public static function host() {
		$host = gethostbyaddr(self::getRemoteAddr());
		return ($host == '') ? null : $host;
	}

	/**
	 * Are there proxy headers detected?
	 * @return bool
	 */
	public static function hasProxy() {
		if (isset($_SERVER)) {
			return (isset($_SERVER['HTTP_VIA']) || isset($_SERVER['HTTP_X_FORWARDED_FOR']));
		}

		return false;
	}

	/**
	 * Get proxy signature
	 * @return string|null
	 * @example 1.1 example.com (squid/3.0.STABLE1)
	 */
	public static function proxySignature() {
		if (isset($_SERVER) && isset($_SERVER['HTTP_VIA'])) {
			return $_SERVER['HTTP_VIA'];
		}

		return null;
	}

	/**
	 * Get the other IP address if exists
	 * @return string|null
	 */
	public static function proxyForwardedFor() {
		if (isset($_SERVER) && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		return null;
	}

	/**
	 * Get remote IP address with additional IP sent over proxy if exists
	 * @return string
	 * @example 89.205.104.23;10.100.10.190
	 */
	public static function ipWithProxy() {
		if (isset($_SERVER)) {
			$ip = self::ip();
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$ip .= ";{$_SERVER['HTTP_X_FORWARDED_FOR']}";
			}

			return $ip;
		} else {
			return "127.0.0.1";
		}
	}

	/**
	 * Get HTTP VIA header
	 * @return string|null
	 * @example 1.0 200.63.17.162 (Mikrotik HttpProxy)
	 */
	public static function httpVia() {
		return (isset($_SERVER['HTTP_VIA']))
			? $_SERVER['HTTP_VIA']
			: null;
	}

	/**
	 * Get HTTP X_FORWARDED_FOR header
	 * @return string|null
	 * @example 58.22.246.105
	 */
	public static function httpXForwardedFor() {
		return (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			? $_SERVER['HTTP_X_FORWARDED_FOR']
			: null;
	}

	/**
	 * Get the user agent
	 * @return string or null if not set
	 */
	public static function userAgent() {
		return isset($_SERVER['HTTP_USER_AGENT'])
			? $_SERVER['HTTP_USER_AGENT']
			: null;
	}

	/**
	 * Get HTTP referer if set
	 * @return string or null if not set
	 */
	public static function httpReferer() {
		return isset($_SERVER['HTTP_REFERER'])
			? $_SERVER['HTTP_REFERER']
			: null;
	}

	/**
	 * Is POST request?
	 * @return boolean
	 */
	public static function isPost() {
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	/**
	 * Is GET request?
	 * @return boolean
	 */
	public static function isGet() {
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET';
	}
}