<?php namespace Koldy;

class Session {

	private static $initialized = false;

	private static function init() {
		if (!self::$initialized) {
			self::$initialized = true;

			$config = Application::getConfig('session');

			session_set_cookie_params(
				$config['cookie_life'],
				$config['cookie_path'],
				$config['cookie_domain'],
				$config['cookie_secure']
			);

			session_name($config['session_name']);

			/*
			if (!isset($config['drivers'][$config['driver']])) {
				Application::throwError(503, "Session driver {$config['driver']} is not defined");
			}

			if ($config['driver'] !== null) {
				self::$drivers = $config['drivers'];
				self::$defaultDriver = $config['driver'];
			} else {
				self::$defaultDriver = false;
			}
			*/

			session_start();
		}
	}

	public static function get($key) {
		self::init();
		return $_SESSION[$key];
	}

	public static function set($key, $value) {
		self::init();
		$_SESSION[$key] = $value;
	}

	public static function add($key, $value) {
		self::init();
		if (!isset($_SESSION[$key])) {
			$_SESSION[$key] = $value;
		}
	}

	public static function has($key) {
		self::init();
		return isset($_SESSION[$key]);
	}

	public static function delete($key) {
		self::init();
		unset($_SESSION[$key]);
	}

	public static function getOrSet($key, $functionOnSet) {
		self::init();
		if (!isset($_SESSION[$key])) {
			$_SESSION[$key] = $functionOnSet();
		}

		return $_SESSION[$key];
	}

	public static function commit() {
		self::init();
		session_write_close();
	}

	/**
	 * You can start session with this method if you need that. Session start
	 * will be automatically called with any of other static methods (excluding
	 * hasStarted() method)
	 */
	public static function start() {
		self::init();
	}

	public static function hasStarted() {
		return self::$initialized;
	}

	public static function destroy() {
		self::init();
		session_unset();
		session_destroy();
	}
}