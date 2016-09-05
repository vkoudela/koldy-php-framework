<?php namespace Koldy;

/**
 * The cookie class. You can manipulate with cookies with this class.
 * This class will automatically crypt or decrypt the cookie's value
 * with Crypt class so client won't be able to see the real value of
 * cookie.
 *
 * Be aware that users can manually change the cookie value so you must
 * validate cookie's value every time you get it with Cookie::get().
 * TODO: Implement check
 * TODO: Finish unit tests
 *
 */
class Cookie {

	/**
	 * Get the cookie value
	 *
	 * @param string $key
	 *
	 * @return string or null if cookie value doesn't exist
	 */
	public static function get($key) {
		if (isset($_COOKIE) && array_key_exists($key, $_COOKIE)) {
			return null;
		}
		return Crypt::decrypt($_COOKIE[$key]);
	}

	/**
	 * Set the cookie
	 *
	 * @param string $name the cookie name
	 * @param string|number $value the cookie value
	 * @param int $expire [optional] when will cookie expire?
	 * @param string $path [optional] path of the cookie
	 * @param string $domain [optional]
	 * @param boolean $secure [optional]
	 * @param boolean $httpOnly [optional]
	 *
	 * @link http://koldy.net/docs/cookies#set
	 * @example Cookie::set('last_visited', date('r'));
	 * @return string
	 */
	public static function set($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httpOnly = false) {
		$encryptedValue = Crypt::encrypt($value);
		setcookie($name, $encryptedValue, $expire, $path, $domain, $secure, $httpOnly);
		return $encryptedValue;
	}

	/**
	 * Set cookie only if this cookie doesn't already exist
	 *
	 * @param string $name
	 * @param string|number $value
	 * @param int $expire [optional] when will cookie expire?
	 * @param string $path [optional] path of the cookie
	 * @param string $domain [optional]
	 * @param boolean $secure [optional]
	 * @param boolean $httpOnly [optional]
	 *
	 * @return boolean true if cookie was set
	 *
	 * @link http://koldy.net/docs/cookies#set
	 * @example Cookie::add('last_visited', date('r'));
	 */
	public static function add($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httpOnly = false) {
		if (!static::has($name)) {
			static::set($name, $value, $expire, $path, $domain, $secure, $httpOnly);
			return true;
		}

		return false;
	}

	/**
	 * Is cookie with given name set or not
	 *
	 * @param string $name
	 *
	 * @return boolean
	 * @link http://koldy.net/docs/cookies#has
	 */
	public static function has($name) {
		return isset($_COOKIE) && array_key_exists($name, $_COOKIE);
	}

	/**
	 * Delete the cookie
	 *
	 * @param string $name
	 *
	 * @link http://koldy.net/docs/cookies#delete
	 */
	public static function delete($name) {
		setcookie($name, '', time() - 3600 * 24);
	}

}
