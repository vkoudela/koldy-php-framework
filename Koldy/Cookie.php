<?php namespace Koldy;

class Cookie {

	private static $key = null;

	private static function getKey() {
		if (self::$key === null) {
			$config = Application::getConfig('application');
			self::$key = $config['key'];
		}

		return self::$key;
	}

	public static function get($key) {
		return Crypt::decrypt($_COOKIE[$key], self::getKey());
	}

	public static function set($key, $value, $expire = 0, $path = '/',
							$domain = null, $secure = false, $httponly = false)
	{
		$value = Crypt::encrypt($value, self::getKey());
		setcookie($key, $value, $expire, $path, $domain, $secure, $httponly);
	}

	public static function add($key, $value) {
		if (!isset($_COOKIE[$key])) {
			self::set($key, $value);
		}
	}

	public static function has($key) {
		return isset($_COOKIE[$key]);
	}

	public static function delete($key) {
		setcookie($key, '', time() - 3600);
	}
}