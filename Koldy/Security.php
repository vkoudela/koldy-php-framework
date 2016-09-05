<?php namespace Koldy;

/**
 * Class Security
 *
 * Some common security stuff.
 *
 * @package Koldy
 */
class Security {

	/**
	 * Set the CSRF token into current session.
	 *
	 * @param null|string $token Your token, leave null if you want framework to generate it
	 * @param null $length
	 *
	 * @return \stdClass The object of <token, cookie_token> properties
	 */
	public static function setCsrfToken($token = null, $length = null) {
		if ($token == null) {
			if ($length == null) {
				$length = 64;
			}

			// generate token here
			if (function_exists('openssl_random_pseudo_bytes')) {
				$token = bin2hex(openssl_random_pseudo_bytes($length));

				if (strlen($token) > $length) {
					// we have a string, now, take some random part there
					$token = substr($token, rand(0, strlen($token) - $length), $length);
				}
			} else {
				$token = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' . Application::getConfig(null, 'key')), 0, $length);
			}
		}

		$data = new \stdClass();
		$data->token = $token;
		Session::set('csrf_token', $token);
		$data->cookie_token = Cookie::set('csrf_token', $token);

		return $data;
	}

	/**
	 * Is there CSRF token in the session?
	 *
	 * @return bool
	 */
	public static function hasCsrfToken() {
		$token = static::getCsrfToken();
		return $token !== null && $token != 0 && $token != false;
	}

	/**
	 * Get currently active CSRF token
	 *
	 * @return string|null
	 */
	public static function getCsrfToken() {
		return Session::get('csrf_token');
	}

	/**
	 * Check if given CSRF token is valid
	 *
	 * @param string $token
	 *
	 * @return bool
	 */
	public static function isCsrfTokenOk($token) {
		return static::getCsrfToken() === $token;
	}

}