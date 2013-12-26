<?php namespace Koldy;

class Application {

	const DEVELOPMENT = 1;
	const PRODUCTION = 2;

	/**
	 * All loaded configs in one place
	 */
	private static $configs = array();

	/**
	 * @var const the application environment mode
	 */
	private static $mode = 2; // static::PRODUCTION by default

	/**
	 * @var string path to application with ending slash
	 * @example /Users/vkoudela/Sites/my.site.com/application/
	 */
	private static $applicationPath = null;

	/**
	 * @var string path to storage with ending slash - by default will be application/storage
	 * @example /Users/vkoudela/Sites/my.site.com/application/storage/
	 */
	private static $storagePath = null;

	/**
	 * @var string path to public folder with ending slash
	 * @example /Users/vkoudela/Sites/my.site.com/public/
	 */
	private static $publicPath = null;

	private static $routing = null;

	private static $uri = null;

	/**
	 * Add additional include path(s)
	 * @param string|array $path
	 */
	public static function addIncludePath($path) {
		$paths = explode(PATH_SEPARATOR, get_include_path());
		
		if (is_array($path)) {
			foreach ($path as $r) {
				$paths[] = $r;
			}
		} else {
			$paths[] = $path;
		}
		
		set_include_path(implode(PATH_SEPARATOR, array_unique($paths)));
	}

	/**
	 * Use the config file
	 * @param array $config
	 */
	public static function useConfig(array $config) {
		static::$configs['application'] = $config;

		static::$mode = $config['env'];
		static::$applicationPath = $config['application_path'];
		static::$publicPath = $config['public_path'];
		static::$storagePath = $config['storage_path'];
		
		defined('LOG') || define('LOG', $config['log']['enabled']);
		// todo: nastaviti
	}

	/**
	 * Get the path to application folder with ending slash
	 * @return string
	 * @example /Users/vkoudela/Sites/your.site.com/application/
	 */
	public static function getApplicationPath() {
		return static::$applicationPath;
	}

	/**
	 * Get the path to storage folder with ending slash
	 * @return string
	 * @example /Users/vkoudela/Sites/your.site.com/storage/
	 */
	public static function getStoragePath() {
		return static::$storagePath;
	}
	
	/**
	 * Get the path to the public folder with ending slash
	 * @return string
	 * @example /Users/vkoudela/Sites/your.site.com/public/
	 */
	public static function getPublicPath() {
		return static::$publicPath;
	}

	/**
	 * Get the configs from config file
	 * @param string $file
	 * @param string $segment the segment from config's array
	 * @return array
	 * @example if you give 'cache' as parameter, you'll get the array from public/config.cache.php file
	 */
	public static function getConfig($file = null, $segment = null) {
		if ($file === null) {
			$file = 'application';
		}

		if (!isset(static::$configs[$file])) {
			$path = static::$publicPath . "config.{$file}.php";
			if (!file_exists($path)) {
				static::throwError(503, 'Config file not found: ' . $file);
			} else {
				static::$configs[$file] = require $path;
			}
		}

		if ($segment !== null) {
			return static::$configs[$file][$segment]; // possible null pointer
		} else {
			return static::$configs[$file];
		}
	}

	/**
	 * Get the application URI
	 * @return string
	 */
	public static function getUri() {
		return static::$uri;
	}

	/**
	 * Throw some error HTTP response
	 */
	public static function throwError($errorCode, $message = null) {
		$path = static::$publicPath . $errorCode . '.php';
		if (file_exists($path)) {
			if (!headers_sent()) {
				switch($errorCode) {
					case 400: header('HTTP/1.0 400 Bad Request', true, 400); break;
					case 403: header('HTTP/1.0 403 Forbidden', true, 403); break;
					case 404: header('HTTP/1.0 404 Not Found', true, 404); break;
					case 500: header('HTTP/1.0 500 Internal server error', true, 500); break;
					case 503: header('HTTP/1.0 503 Temporary server error', true, 503); break; // todo: check this
				}
			}
			include($path);
			exit;
		} else {
			throw new \Exception($message === null ? ('Error ' . $errorCode) : $message);
		}
	}

	/**
	 * Get the initialized routing class
	 * @return \Koldy\Application\Route\AbstractRoute
	 */
	public static function route() {
		return static::$routing;
	}

	/**
	 * Is application running in development mode or not
	 * @return  boolean
	 */
	public static function inDevelopment() {
		return static::$mode === static::DEVELOPMENT;
	}

	/**
	 * Is application running in production mode or not
	 * @return  boolean
	 */
	public static function inProduction() {
		return static::$mode === static::PRODUCTION;
	}

	public static function registerModule($module) {
		static::addIncludePath(array(
			static::getApplicationPath() . 'modules' . DS . $module . DS . 'controllers' . DS,
			static::getApplicationPath() . 'modules' . DS . $module . DS . 'models' . DS,
			static::getApplicationPath() . 'modules' . DS . $module . DS . 'library' . DS
		));
	}

	/**
	 * Initialize the application :)
	 * @param string $uri
	 */
	protected static function init($uri) {
		static::$uri = $uri;

		// first, check the URI for duplicate slashes - they are not allowed
		// if you must pass duplicate slashes in URL, then urlencode them
		if (strpos($uri, '//') !== false) {
			header('Location: ' . str_replace('//', '/', $uri));
			die();
		}

		// second, check all requirements
		if (!function_exists('spl_autoload_register')) {
			throw new \Exception('SPL is missing! Can not register autoload function');
		}

		// set the error reporting
		if (static::inDevelopment()) {
			error_reporting(E_ALL);
		}

		defined('DS') || define('DS', DIRECTORY_SEPARATOR);

		// Register Autoload function
		spl_autoload_register(function($className) {
			$classes = Application::getConfig('application', 'classes');
			if (isset($classes[$className])) {
				class_alias($classes[$className], $className);
			} else {
				$classPath = str_replace('\\', DS, $className);
				$path = "{$classPath}.php";
				require($path);
			}
		});

		// detect some common staff for paths
		if (static::$applicationPath === null || static::$publicPath === null) {
			if (isset($_SERVER['SCRIPT_FILENAME'])) {
				$rootPath = dirname($_SERVER['SCRIPT_FILENAME']);
				$rootPath = substr($rootPath, 0, strrpos($rootPath, DS)) . DS;
			} else {
				throw new \Exception('Something went wrong determining script filename');
			}
		}

		if (static::$applicationPath === null) {
			static::$applicationPath = $rootPath . 'application' . DS;
		}

		if (static::$storagePath === null) {
			static::$storagePath = static::$applicationPath . 'storage' . DS;
		}

		if (static::$publicPath === null) {
			static::$publicPath = $rootPath . 'public' . DS;
		}

		static::addIncludePath(array(
			substr(dirname(__FILE__), 0, -6) // set the include path to the framework folder (to Koldy and any other framework(s) located in framework folder with same namespacing style)
		));
	}

	/**
	 * Run the application with given URI. If URI is not set, then application will try to detect it automatically.
	 * @param string $uri OPTIONAL Passing this value can be useful for cron jobs / CLI environment.
	 */
	public static function run($uri = null) {
		$config = static::getConfig();
		$microtimeStart = microtime();

		if ($uri === null && isset($_SERVER) && isset($_SERVER['REQUEST_URI'])) {
			$uri = $_SERVER['REQUEST_URI'];
			$questionPos = strpos($uri, '?');
			if ($questionPos !== false) {
				$uri = substr($uri, 0, $questionPos);
			}
		} else if ($uri === null) {
			throw new \Exception('URI doesn\'t exists');
		}

		try {
			static::init($uri);

			$routingClassName = $config['routing_class'];
			static::$routing = new $routingClassName($uri);

			$className = static::$routing->getControllerClass();

			if (class_exists($className, true)) {
				$controller = new $className();
				$method = static::$routing->getActionMethod();

				if (method_exists($controller, $method) || method_exists($controller, '__call')) {
					// get the return value of your method (json, xml, view object, string or nothing)
					$response = $controller->$method();

					switch (gettype($response)) {
						case 'object': $response->flush(); break;
						case 'integer':
						case 'double': // yes, I know...
						case 'float':
						case 'string': echo $response; break;
					}
				} else {
					if (static::inDevelopment()) {
						Log::debug('Can not find method=' . $method . ' in class=' . static::$routing->getControllerClass() . ' on path=' . static::$routing->getControllerPath());
					}
					static::throwError(404, "Method not found {$className}->{$method}");
				}
			} else { // controller doesn't exists
				static::throwError(404, "Can not find {$className}");
			}

			if (LOG && static::inDevelopment()) {
				Log::debug(round((microtime() - $microtimeStart) * 1000, 3) . 'ms, ' . sizeof(get_included_files()) . ' files');
// 				Log::debug(print_r(explode(':', get_include_path()), true));
			}
		} catch (\Exception $e) {
			echo "<strong>{$e->getMessage()}</strong><pre>{$e->getTraceAsString()}</pre>";
		}
	}

}