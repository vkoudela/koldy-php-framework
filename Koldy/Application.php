<?php namespace Koldy;

/**
 * The main class of Koldy framework. This class will bootstrap the request. It
 * will prepare everything you need and it will print response to the request by the
 * way you want.
 *
 * Enjoy!
 *
 * @link http://koldy.net/docs/how-framework-works
 */
class Application {

	/**
	 * All loaded configs in one place so feel free to call
	 * Application::getConfig() as many times as you want
	 *
	 * @var array
	 */
	protected static $configs = array();

	/**
	 * Array of configs that were loaded from filesystem
	 *
	 * @var array
	 */
	protected static $loadedConfigs = array();

	/**
	 * All loaded module configs are in one place so feel free to call
	 * Application::getModuleConfig() as many times as you want
	 *
	 * @var array
	 */
	protected static $moduleConfigs = array();

	/**
	 * The array of already registered modules, so we don't execute code if you already registered your module
	 *
	 * @var array
	 */
	private static $registeredModules = array();

	/**
	 * The environment modes. Only these for now
	 *
	 * @var array
	 */
	private static $modes = array('DEVELOPMENT' => 1, 'PRODUCTION' => 2);

	/**
	 * The application environment mode
	 *
	 * @var int
	 */
	protected static $mode = 1;

	/**
	 * Is this application on live server or not? So you can do combinations:
	 *
	 * - production/live
	 * - production/not-live (testing production on developer machine or anywhere else)
	 * - development/not-live (real dev mode)
	 * - development/live - (running live, but in dev mode; e.g. you want your analytics, but serving dev not minimized versions)
	 *
	 * @var bool
	 */
	protected static $isLive = false;

	/**
	 * Thr routing class instance - this is the instance of class
	 * defined in config/application.php under routing_class
	 *
	 * @var \Koldy\Application\Route\AbstractRoute
	 */
	protected static $routing = null;

	/**
	 * The requested URI. Basically $_SERVER['REQUEST_URI'], but not always
	 *
	 * @var string
	 */
	protected static $uri = null;

	/**
	 * If CLI env, then this is the path of CLI script
	 *
	 * @var string
	 */
	protected static $cliScript = null;

	/**
	 * The parameter from CLI call - the script name
	 *
	 * @var string
	 */
	protected static $cliName = null;

	/**
	 * Current domain on which we're running
	 *
	 * @var string
	 */
	protected static $domain = null;

	/**
	 * Add additional include path(s) - add anything you want under include path
	 *
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
	 * Return internal server error headers
	 */
	private static function returnInternalServerError() {
		header('HTTP/1.1 503 Service Temporarily Unavailable', true, 503);
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: 300'); // 300 seconds / 5 minutes
	}

	/**
	 * Use the application config file and validate all values we need
	 *
	 * @param string|array $config if string, then it can be path relative to your index.php file, otherwise it's the
	 * content of configs/application.php
	 *
	 * @throws \Exception
	 */
	public static function useConfig($config) {
		if (is_string($config)) {
			$applicationPath = stream_resolve_include_path($config); // this is path to /application/configs/application.php
			if ($applicationPath === false || !is_file($applicationPath)) {
				static::returnInternalServerError();
				print 'Can not resolve the full path to the main application config file or file doesn\'t exists!';
				exit(1);
			}

			$config = require $applicationPath; // will return array

			if (!isset($config['application_path'])) {
				// we'll make an assumption and try to guess...

				$config['application_path'] = dirname(getcwd()) . '/application/';

				if (!is_dir($config['application_path'])) {
					static::returnInternalServerError();
					print 'Can\'t continue without defined and existing application_path';
					exit(1);
				}
			} else {
				//$applicationPath = $config['application_path']; // user defined it

				if (!is_dir($config['application_path'])) {
					static::returnInternalServerError();
					print 'Can\'t continue without application_path that exists';
					exit(1);
				}
			}

		} else if (!is_array($config)) {
			static::returnInternalServerError();
			print 'Can\'t run without config. Expected string or array, got ' . gettype($config);
			exit(1);

		} else { // it's good, we got an array

			// first check if application_path is defined .. because if it's not, then we can't continue
			if (!isset($config['application_path'])) {
				// we'll make an assumption and try to guess...

				$config['application_path'] = dirname(getcwd()) . '/application/';

				if (!is_dir($config['application_path'])) {
					static::returnInternalServerError();
					print 'Can\'t continue without defined application_path';
					exit(1);
				}
			} else {
				//$applicationPath = $config['application_path']; // user defined it

				if (!is_dir($config['application_path'])) {
					static::returnInternalServerError();
					print 'Can\'t continue without application_path that exists';
					exit(1);
				}
			}
		}

		// first, check if the site can be run on multiple host names
		if (defined('KOLDY_CLI') && KOLDY_CLI) {
			if (is_array($config['site_url'])) {
				if (sizeof($config['site_url']) == 0 || !isset($config['site_url'][0])) {
					static::returnInternalServerError();
					print 'Invalid config of site_url';
					exit(1);
				}

				$config['site_url'] = $config['site_url'][0];
			} else if (!isset($config['site_url'])) {
				static::returnInternalServerError();
				print 'Invalid config of site_url';
				exit(1);
			}

			static::$domain = substr($config['site_url'], strpos($config['site_url'], '://') + 3);
		} else {
			if ($config['site_url'] === null) {
				$config['site_url'] = "//{$_SERVER['HTTP_HOST']}";
				static::$domain = $_SERVER['HTTP_HOST'];

				// this is assumption...
				if (!isset($_SERVER['HTTPS'])) {
					$config['site_url'] = 'http://' . $config['site_url'];
				} else {
					$config['site_url'] = 'https://' . $config['site_url'];
				}

			} else if (is_array($config['site_url'])) {
				$sizeofSiteUrls = count($config['site_url']);
				$found = false;
				for ($i = 0; !$found && $i < $sizeofSiteUrls; $i++) {
					$siteUrl = $config['site_url'][$i];
					$siteUrl = substr($siteUrl, strpos($siteUrl, '//') + 2);
					if ($siteUrl === $_SERVER['HTTP_HOST']) {
						$config['site_url'] = $config['site_url'][$i];
						$found = true;

						static::$domain = substr($config['site_url'], strpos($config['site_url'], '://') + 3);
					}
				}

				if (!$found) {
					static::returnInternalServerError();
					print 'Invalid config of site_url; hostname not found';
					exit(1);
				}
			} else {
				static::$domain = substr($config['site_url'], strpos($config['site_url'], '://') + 3);
			}
		}

		// check the environment
		$env = strtoupper($config['env']);
		if (array_key_exists($env, static::$modes)) {
			static::$mode = static::$modes[$env];
		} else {
			throw new \Exception('Invalid ENV parameter in config/application.php');
		}

		if (!isset($config['public_path'])) {
			$config['public_path'] = (PHP_SAPI != 'cli' ? dirname($_SERVER['SCRIPT_FILENAME']) : getcwd()) . DIRECTORY_SEPARATOR;
		}

		if (!isset($config['storage_path'])) {
			$config['storage_path'] = dirname($config['application_path']) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR;
		}

		if (!isset($config['key']) || !is_string($config['key'])) {
			throw new \Exception('Invalid unique key in config/application.php');
		}

		if (strlen($config['key']) > 32) {
			throw new \Exception('Unique application key is too long');
		}

		if (!isset($config['timezone'])) {
			throw new Exception('Timezone is not set in config/application.php');
		}

		static::$isLive = array_key_exists('live', $config) ? (bool) $config['live'] : false;

		static::$configs['application'] = $config;

	}

	/**
	 * Get the path to application folder with ending slash
	 *
	 * @param string $append [optional] append anything you want to application path
	 *
	 * @return string
	 * @example /var/www/your.site/com/application/
	 */
	public static function getApplicationPath($append = null) {
		if ($append === null) {
			return static::getConfig('application', 'application_path');
		} else {
			return str_replace(DS . DS, DS, static::getConfig('application', 'application_path') . $append);
		}
	}

	/**
	 * Get the path to storage folder with ending slash
	 *
	 * @param string $append [optional] append anything you want to application path
	 *
	 * @return string
	 * @example /var/www/your.site/com/storage/
	 */
	public static function getStoragePath($append = null) {
		if ($append === null) {
			return static::getConfig('application', 'storage_path');
		} else {
			return str_replace(DS . DS, DS, static::getConfig('application', 'storage_path') . $append);
		}
	}

	/**
	 * Get the path to the public folder with ending slash
	 *
	 * @param string $append [optional] append anything you want to application path
	 *
	 * @return string
	 * @example /var/www/your.site/com/public/
	 */
	public static function getPublicPath($append = null) {
		if ($append === null) {
			return static::getConfig('application', 'public_path');
		} else {
			if (!is_string($append)) {
				throw new \InvalidArgumentException('String expected, ' . gettype($append) . ' got');
			}

			return str_replace(DS . DS, DS, static::getConfig('application', 'public_path') . $append);
		}
	}

	/**
	 * Get the path to directory with views
	 * @return array|string
	 * @throws Exception
	 */
	public static function getViewPath() {
		$viewPath = static::getConfig('application', 'view_path');

		if ($viewPath === null) {
			return static::getApplicationPath('views');
		} else {
			return $viewPath;
		}
	}

	/**
	 * Get the running CLI script name - this is available only if this
	 * request is running in CLI environment
	 *
	 * @return string
	 * @example if you call "php cli.php backup", this method will return "/path/to/application/scripts/backup.php"
	 */
	public static function getCliScript() {
		return static::$cliScript;
	}

	/**
	 * Get the CLI script name
	 *
	 * @return string
	 * @example if you call "php cli.php backup", this method will return "backup" only
	 */
	public static function getCliName() {
		return static::$cliName;
	}

	/**
	 * Is this CLI request?
	 * @return bool
	 */
	public static function isCli() {
		return static::$cliName !== null;
	}

	/**
	 * Get the configs from any config file. If you don't pass first parameter,
	 * then system will use 'application' as default. Second variable is segment
	 * - it is the key name in configuration array. If you define it, then
	 * you'll get only the value under that key, but if you don't define it,
	 * then the whole config from that file will be returned.
	 *
	 * @param string $file [optional] by default: application
	 * @param string $segment [optional] the key from config's array
	 *
	 * @return array
	 * @example if you pass 'cache' as parameter, you'll get the array from application/configs/cache.php file
	 * @throws \Koldy\Exception
	 */
	public static function getConfig($file = null, $segment = null) {
		if ($file === null) {
			$file = 'application';
		}

		if (!isset(static::$configs[$file])) {
			// has config path being defined?
			if (isset(static::$configs['application']['config_path'])) {
				$configPath = static::$configs['application']['config_path'];
			} else {
				$configPath = static::$configs['application']['application_path'] . 'configs/'; // don't call getApplicationPath, it'll go in recursion
			}

			if (isset(static::$configs['application']['configs'])) {
				if (is_array(static::$configs['application']['configs'])) {
					if (isset(static::$configs['application']['configs'][$file])) {
						$path = static::$configs['application']['configs'][$file];
					} else {
						$path = $configPath . $file . '.php';
					}
				} else {
					$path = $configPath . $file . '.php';
				}
			} else {
				$path = $configPath . $file . '.php';
			}

			if (!file_exists($path)) {
				throw new Exception('Config file not found: ' . $file);
			} else {
				static::$configs[$file] = require $path;
				static::$loadedConfigs[] = $file;
			}
		}

		if ($segment !== null) {
			if (array_key_exists($segment, static::$configs[$file])) {
				return static::$configs[$file][$segment];
			} else {
				return null;
			}
		} else {
			return static::$configs[$file];
		}
	}

	/**
	 * Append new settings in currently loaded config
	 *
	 * @param string $file
	 * @param string $segment
	 * @param mixed $value
	 */
	public static function appendConfig($file, $segment, $value) {
		$config = static::getConfig($file);
		$config[$segment] = $value;
		static::$configs[$file] = $config;
	}

	/**
	 * Get the configs from any config file within the module. Firt parameter is file name without extension.
	 * Second param is segment - it is the key name in configuration array. If you define it, then
	 * you'll get only the value under that key, but if you don't define it,
	 * then the whole config from that file will be returned.
	 *
	 * @param string $module
	 * @param string $file
	 * @param string $segment [optional] the key from config's array
	 *
	 * @return array
	 * @example if you pass 'koldy-mail-queue' and 'database' as second parameter, you'll get the array from /application/modules/koldy-mail-queue/configs/database.php
	 * @throws \Koldy\Exception
	 */
	public static function getModuleConfig($module, $file, $segment = null) {
		if (!isset(static::$moduleConfigs[$module])) {
			static::$moduleConfigs[$module] = array();
		}

		if (!isset(static::$moduleConfigs[$module][$file])) {
			$path = static::getModulePath($module) . 'configs' . DS . $file . '.php';
			if (!file_exists($path)) {
				throw new Exception('Config file \'' . $file . '\' not found in module \'' . $module . '\':' . $path);
			} else {
				static::$moduleConfigs[$module][$file] = require $path;
			}
		}

		if ($segment !== null) {
			if (array_key_exists($segment, static::$moduleConfigs[$module][$file])) {
				return static::$moduleConfigs[$module][$file][$segment];
			} else {
				return null;
			}
		} else {
			return static::$moduleConfigs[$module][$file];
		}
	}

	/**
	 * Helper method for loading adapter configs (for cache, db and mail or your own)
	 * 
	 * @param string $file
	 * @param string $adapter
	 *
	 * @return array|boolean
	 * @throws Exception
	 */
	public static function getAdapterConfig($file, $adapter) {
		$config = static::getConfig($file);

		if ($config == null) {
			// config not found
			return false;
		}

		if (!isset($config[$adapter])) {
			//throw new Exception("Can not find adapter config for file: {$file}");
			return false;
		}

		$adapterConfig = $config[$adapter];

		if (is_string($adapterConfig)) {
			$otherConfigKey = $config[$adapter];

			if (!isset($config[$otherConfigKey])) {
				throw new Exception("Unable to find adapter config '{$adapter}'->'{$otherConfigKey}' in config file: {$file}");
			}

			$adapter = $otherConfigKey;
		}

		if (!is_array($config[$adapter])) {
			throw new Exception("Config '{$adapter}' in {$file} needs to be array");
		}

		return $config[$adapter];
	}

	/**
	 * Clear all configs that are already loaded.
	 */
	public static function reloadConfig() {
		foreach (static::$loadedConfigs as $loadedConfig) {
			unset(static::$configs[$loadedConfig]);
			static::getConfig($loadedConfig);
		}
	}

	/**
	 * Get the application URI. Yes, use this one instead of $_SERVER['REQUEST_URI']
	 * because you can pass this URI in index.php while calling Application::run()
	 * or somehow different so the real request URI will be overridden.
	 *
	 * @return string
	 */
	public static function getUri() {
		return static::$uri;
	}

	/**
	 * Show some nice error in HTTP response that will be visible to user.
	 * If you want to log anything, then log that before calling this method.
	 *
	 * @param int $code
	 * @param string $message [optional]
	 * @param \Exception $e [optional]
	 */
	public static function error($code, $message = null, \Exception $e = null) {
		$route = static::route();

		if ($route === null) {
			// damn, even route class wasn't initialized and route instance
			// should handle this error!

			header('HTTP/1.1 503 Service Temporarily Unavailable', true, 503);
			header('Status: 503 Service Temporarily Unavailable');
			header('Retry-After: 300'); // 300 seconds / 5 minutes

			if ($e !== null) {
				echo "<p>{$e->getMessage()}</p>";
				if (static::inDevelopment()) {
					echo "<pre>{$e->getTraceAsString()}</pre>";
				}
			} else {
				echo "<h1>{$code}</h1><p>{$message}</p>";
				exit(0);
			}

		} else {
			// otherwise, route should handle exception
			// because of this, you can customize almost everything
			static::route()->error($code, $message, $e);
		}
	}

	/**
	 * Get the initialized routing class
	 *
	 * @return \Koldy\Application\Route\AbstractRoute
	 */
	public static function route() {
		return static::$routing;
	}

	/**
	 * Is application running in development mode or not
	 *
	 * @return boolean
	 */
	public static function inDevelopment() {
		return static::$mode === static::$modes['DEVELOPMENT'];
	}

	/**
	 * Is application running in production mode or not
	 *
	 * @return boolean
	 */
	public static function inProduction() {
		return static::$mode === static::$modes['PRODUCTION'];
	}

	/**
	 * Is app running live or not?
	 *
	 * @return bool
	 */
	public static function isLive() {
		return static::$isLive;
	}

	/**
	 * Register all include paths for module
	 *
	 * @param string $name
	 *
	 * @example if your module is located on "/application/modules/invoices", then pass "invoices"
	 */
	public static function registerModule($name) {
		if (!isset(static::$registeredModules[$name])) {
			$modulePath = static::getModulePath($name);

			static::addIncludePath(array(
				$modulePath . 'controllers',
				$modulePath . 'models',
				$modulePath . 'library'
			));

			static::$registeredModules[$name] = true;

			$initPath = $modulePath . 'init.php';
			if (is_file($initPath)) {
				include $initPath;
			}
		}
	}

	/**
	 * Get the path on file system to the module WITH ending slash
	 * @param string $name
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function getModulePath($name) {
		$modulePath = static::getConfig('application', 'module_path');

		if ($modulePath === null) {
			$modulePath = static::getApplicationPath('modules');
		}

		return str_replace(DS.DS, DS, $modulePath . DS . $name . DS);
	}

	/**
	 * Is module with given name already registered in system or not
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function isModuleRegistered($name) {
		return isset(static::$registeredModules[$name]);
	}

	/**
	 * Initialize the application :)
	 *
	 * @throws Exception
	 */
	protected static function init() {
		// second, check all requirements
		if (!function_exists('spl_autoload_register')) {
			throw new Exception('SPL is missing! Can not register autoload function');
		}

		// set the error reporting in development mode
		if (static::inDevelopment()) {
			error_reporting(E_ALL | E_STRICT);
		}

		$config = static::getConfig('application');

		// this is just shorthand for Directory Separator
		defined('DS') || define('DS', DIRECTORY_SEPARATOR);

		date_default_timezone_set($config['timezone']);

		// Register Autoload function
		spl_autoload_register(function ($className) {
				$classPath = str_replace('\\', DS, $className);
				$path = $classPath . '.php';
				require $path;
		});

		// set the include path to the framework folder (to Koldy and any other
		// framework(s) located in framework folder with same name spacing style)
		$includePaths = array(substr(dirname(__FILE__), 0, -6));

		$basePath = static::getApplicationPath();

		// auto registering modules if there are any defined
		if (isset($config['auto_register_modules'])) {
			if (!is_array($config['auto_register_modules'])) {
				throw new Exception('Invalid config for auto_register_modules in config/application.php');
			}

			foreach ($config['auto_register_modules'] as $moduleName) {
				$modulePath = static::getModulePath($moduleName);
				$includePaths[] = $modulePath . 'controllers';
				$includePaths[] = $modulePath . 'models';
				$includePaths[] = $modulePath . 'library';
			}
		}

		// register include path of application itself
		$includePaths[] = $basePath . 'controllers';
		$includePaths[] = $basePath . 'library';
		$includePaths[] = $basePath . 'models';

		// adding additional include paths if there are any
		if (isset($config['additional_include_path'])) {
			if (!is_array($config['additional_include_path'])) {
				throw new Exception('Invalid config for additional_include_path in config/application.php');
			}

			// so, we need to include something more
			foreach ($config['additional_include_path'] as $path) {
				$includePaths[] = $path;
			}
		}

		// set the include path
		set_include_path(implode(PATH_SEPARATOR, $includePaths) . PATH_SEPARATOR . get_include_path());

		// set the error handler
		if (isset($config['error_handler']) && $config['error_handler'] instanceof \Closure) {
			set_error_handler($config['error_handler']);
		} else {
			set_error_handler(function ($errno, $errstr, $errfile, $errline) {
				if (!(error_reporting() & $errno)) {
					// This error code is not included in error_reporting
					return;
				}

				switch($errno) {
					case E_USER_ERROR:
						\Koldy\Log::error("PHP [{$errno}] {$errstr} in file {$errfile}:{$errline}");
						break;

					case E_USER_WARNING:
					case E_DEPRECATED:
					case E_STRICT:
						\Koldy\Log::warning("PHP [{$errno}] {$errstr} in file {$errfile}:{$errline}");
						break;

					case E_USER_NOTICE:
						\Koldy\Log::notice("PHP [{$errno}] {$errstr} in file {$errfile}:{$errline}");
						break;


					default:
						\Koldy\Log::error("PHP Uknown [{$errno}] {$errstr} in file {$errfile}:{$errline}");
						break;
				}

				/* Don't execute PHP internal error handler */
				return true;
			});
		}

		// register PHP fatal errors
		register_shutdown_function(function () {
			if (!defined('KOLDY_FATAL_ERROR_HANDLER')) {
				define('KOLDY_FATAL_ERROR_HANDLER', true); // to prevent possible recursion if you run into problems with logger

				$fatalError = error_get_last();

				if ($fatalError !== null && $fatalError['type'] == E_ERROR) {
					$errno = E_ERROR;
					$errstr = $fatalError['message'];
					$errfile = $fatalError['file'];
					$errline = $fatalError['line'];

					$config = \Koldy\Application::getConfig('application');
					if (isset($config['error_handler']) && $config['error_handler'] instanceof \Closure) {
						call_user_func($config['error_handler'], $errno, $errstr, $errfile, $errline);
					} else {
						\Koldy\Log::error("PHP [{$errno}] Fatal error: {$errstr} in {$errfile} on line {$errline}");
					}
				}
			}
		});

		// all execeptions will be caught in run() method
	}

	/**
	 * Get the request execution time in milliseconds
	 *
	 * @return float
	 */
	public static function getRequestExecutionTime() {
		return round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2);
	}

	/**
	 * Run the application with given URI. If URI is not set, then application
	 * will try to detect it automatically.
	 *
	 * @param string $uri [optional]
	 *
	 * @throws Exception
	 */
	public static function run($uri = null) {
		$config = static::getConfig();
		$isCLI = defined('KOLDY_CLI') && (KOLDY_CLI === true);

		static::init();

		$routingClassName = $config['routing_class'];
		$routeOptions = isset($config['routing_options']) ? $config['routing_options'] : null;
		static::$routing = new $routingClassName($routeOptions);

		if (!$isCLI) {
			// this is normal HTTP request that came from Web Server, so we'll handle it

			if ($uri === null && isset($_SERVER['REQUEST_URI'])) {
				if (isset($config['url_namespace'])) {
					$uri = str_replace($config['url_namespace'], '', $_SERVER['REQUEST_URI']);
				} else {
					$uri = $_SERVER['REQUEST_URI'];
				}
			} else if ($uri === null) {
				// if your script goes here, then something is really fucked up on your server
				throw new Exception('URI doesn\'t exists');
			}

			static::$uri = $uri;

			try {
				static::$routing->prepareHttp(static::$uri);
				$response = static::$routing->exec();
				if ($response instanceof Response) {
					print $response->flush();
				} else if (is_array($response)) {
					$className = static::$routing->getControllerClass();
					$actionName = static::$routing->getActionMethod();
					throw new Exception("Invalid return value from {$className}->{$actionName}; got array instead of something else");
				} else {
					print $response;
				}

			} catch (\Exception $e) { // something threw up

				$route = static::route();
				if ($route === null) {
					// damn, even route class wasn't initialized
					static::returnInternalServerError();

					if (static::inDevelopment()) {
						print "<p>{$e->getMessage()}</p>";
						print "<pre>{$e->getTraceAsString()}</pre>";
					} else {
						print '<p>Something went really wrong. Please try again later.</p>';
					}
				} else {
					// otherwise, route should handle exception
					// because of this, you can customize almost everything
					static::route()->handleException($e);
				}

			}

		} else {

			// and this is case when you're dealing with CLI request
			// scripts are stored in /application/scripts, but before that, we need to determine which script is called

			global $argv;
			// $argv[0] - this should be "cli.php", but we don't need this at all

			try {
				// so, if you run your script as "php cli.php backup", you'll have only two elements
				// in the future, we might handle different number of parameters, but until that, we won't

				// you can also call script in module using standard colon as separator
				// example: php cli.php user:backup   -> where "user" is module and "backup" is script name

				if (!isset($argv[1])) {
					throw new Exception('Script name is not set in you CLI call. Check http://koldy.net/docs/cli for more info');
				}

				$script = $argv[1]; // this should be the second parameter
				static::$cliName = $script;

				if (preg_match('/^([a-zA-Z0-9\_\-\:]+)$/', $script)) {
					$scriptName = $script;

					if (strpos($script, ':') !== false) {
						// script name has colon - it means that the script needs to be looked for in modules
						$tmp = explode(':', $script);
						static::$cliScript = static::getApplicationPath("modules/{$tmp[0]}/scripts/{$tmp[1]}.php");
						$scriptName = $tmp[0] . ':' . $tmp[1];

						if (is_dir(static::getApplicationPath("modules/{$tmp[0]}"))) {
							static::registerModule($tmp[0]);
						}
					} else {
						static::$cliScript = static::getApplicationPath("scripts/{$script}.php");
					}

					if (!is_file(static::$cliScript)) {
						throw new Exception('CLI script doesn\'t exist on ' . static::$cliScript);
					} else {
						Log::notice('Started CLI script: ' . $scriptName);
						include static::$cliScript;
						Log::notice('Done CLI script: ' . $scriptName);
					}
				} else {
					throw new Exception("CLI script name contains invalid characters: {$script}");
				}

			} catch (\Exception $e) {
				if (!Log::isEnabledLogger('\Koldy\Log\Writer\Out')) {
					echo "{$e->getMessage()} in {$e->getFile()}:{$e->getLine()}\n\n{$e->getTraceAsString()}";
				}
				Log::critical($e);
			}
		}

	}

}
