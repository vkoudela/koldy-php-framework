<?php namespace Koldy;

use Koldy\Log\Writer\AbstractLogWriter;

/**
 * Class to handle the log and writing to log. Be aware that using too much of log slows down the
 * complete application, while other processes are waiting to finish your log. Of course, you can rapidly optimize
 * this by using slightly different syntax. As you already know, if you have your log disabled, but you're still
 * calling for an example Log::info("User {$fullName} has logged in"); the PHP interpreter will still have to
 * parse the passing string and then inside of info() method, message will be disregarded. To avoid this, you can
 * use the following syntax:
 *
 * @example		Log::info("User {$fullName} has logged in");
 *
 * You are encouraged to use log in development, but reduce logs in production mode as much as you can. Always
 * log only important data and never log the code that will always execute successfully.
 *
 * Using file output, this singleton instance will open the file on the first call and won't be closed. Instead
 * of closing, class will register itself in application's shutdown process and will close the log file when
 * request finish its job. That way log file won't be opened and closed every time you want to log something.
 *
 * If you have enabled email logging, then this script will send you log message(s) to your error mail. To reduce
 * SPAM, if there are a lot of error messages to send, all other log messages will be mailed at once as well. Lets
 * say you have 5 info log messages, 1 notice and 1 error - you'll receive error mail with all messages logged
 * with Log class even if those message won't be written to your Log driver.
 *
 * @link http://koldy.net/docs/log
 *
 */
class Log {

	/**
	 * The array of only enabled writer instances for this request
	 *
	 * @var array
	 */
	private static $writers = null;

	/**
	 * The array of enabled log levels, combined for all loggers
	 *
	 * @var array of level => true, possible levels are: emergency, alert, critical, error, warning, notice, info, debug and sql
	 */
	private static $enabledLevels = array();

	/**
	 * @var string
	 */
	private static $who = null;

	/**
	 * The array of enabled classes, stored as class name string
	 *
	 * @var array of className => true
	 */
	private static $enabledWriters = array();

	protected function __construct() {}
	protected function __clone() {}

	/**
	 * Initialize, load config and etc.
	 */
	private static function init() {
		if (static::$writers === null) {
			if (Application::isCli()) {
				static::$who = Application::getCliName() . '-' . time();
			} else {
				static::$who = Request::ip() . '-' . rand(100000, 999999);
			}

			static::$writers = array();
			$configs = Application::getConfig('application', 'log');

			$count = 0;
			foreach ($configs as $config) {
				$enabled = $config['enabled'] && is_array($config['options']['log']) && count($config['options']['log']) > 0;

				if ($enabled) {
					// if the config is enabled, then make new instance
					$writer = $config['writer_class'];
					static::$writers[$count] = new $writer($config['options']);
					static::$enabledWriters[$config['writer_class']] = true;

					foreach ($config['options']['log'] as $level) {
						static::$enabledLevels[$level] = true;
					}

					if (!(static::$writers[$count] instanceof AbstractLogWriter)) {
						throw new Exception("Log driver {$writer} must extend AbstractLogWriter");
					}

					$count++;
				}
			}

			register_shutdown_function(function() {
				\Koldy\Log::shutdown();
			});
		}
	}

	/**
	 * Is there any log driver enabled in this moment?
	 *
	 * @return boolean
	 */
	public static function isEnabled() {
		static::init();
		return (sizeof(static::$writers) > 0);
	}

	/**
	 * @param string $level
	 *
	 * @return bool
	 */
	public static function isEnabledLevel($level) {
		if (!is_string($level)) {
			throw new \InvalidArgumentException('Expected string for $level, got ' . gettype($level));
		}

		return array_key_exists($level, static::$enabledLevels);
	}

	/**
	 * Was logger under given class name enabled or not?
	 *
	 * @param string $className
	 *
	 * @return bool
	 */
	public static function isEnabledLogger($className) {
		if (!is_string($className)) {
			throw new \InvalidArgumentException('Expected string for $className, got ' . gettype($className));
		}

		return array_key_exists($className, static::$enabledWriters);
	}

	/**
	 * @param array $params
	 *
	 * @return string
	 */
	private static function getMessageFromParameters(array $params) {
		$message = array();

		foreach ($params as $param) {
			if (is_array($param)) {
				$message[] = print_r($param, true);

			} else if (is_object($param) && method_exists($param, '__toString')) {
				$message[] = $param->__toString();

			} else if (is_object($param) && $param instanceof \Exception) {
				$message[] = "Exception in {$param->getFile()}:{$param->getLine()}\n\n{$param->getMessage()}\n\n{$param->getTraceAsString()}";

			} else if (is_object($param)) {
				$message[] = print_r($param, true);

			} else {
				$message[] = $param;

			}
		}

		return implode(' ', $message);
	}

	/**
	 * Get or set the "who" - it'll be visible in logs as "who did that".
	 *
	 * If you pass string, you'll set the "who"
	 *
	 * @param null|string $who
	 *
	 * @return string
	 */
	public static function who($who = null) {
		if ($who == null) {
			return static::$who;
		} else {
			static::$who = $who;
		}
	}

	/**
	 * Write EMERGENCY message to log
	 *
	 * @param array|string ...$string
	 *
	 * @link http://koldy.net/docs/log#usage
	 */
	public static function emergency(...$string) {
		static::init();

		$string = static::getMessageFromParameters($string);
		foreach (static::$writers as $writer) {
			/* @var $writer \Koldy\Log\Writer\AbstractLogWriter */
			$writer->emergency($string);
		}
	}

	/**
	 * Write ALERT message to log
	 *
	 * @param array|string ...$string
	 *
	 * @link http://koldy.net/docs/log#usage
	 */
	public static function alert(...$string) {
		static::init();

		$string = static::getMessageFromParameters($string);
		foreach (static::$writers as $writer) {
			/* @var $writer \Koldy\Log\Writer\AbstractLogWriter */
			$writer->alert($string);
		}
	}

	/**
	 * Write CRITICAL message to log
	 *
	 * @param array|string ...$string
	 *
	 * @link http://koldy.net/docs/log#usage
	 */
	public static function critical(...$string) {
		static::init();

		$string = static::getMessageFromParameters($string);
		foreach (static::$writers as $writer) {
			/* @var $writer \Koldy\Log\Writer\AbstractLogWriter */
			$writer->critical($string);
		}
	}

	/**
	 * Write DEBUG message to log
	 *
	 * @param array|string ...$string
	 *
	 * @link http://koldy.net/docs/log#usage
	 */
	public static function debug(...$string) {
		static::init();

		$string = static::getMessageFromParameters($string);
		foreach (static::$writers as $writer) {
			/* @var $writer \Koldy\Log\Writer\AbstractLogWriter */
			$writer->debug($string);
		}
	}

	/**
	 * Write NOTICE message to log
	 *
	 * @param array|string ...$string
	 *
	 * @link http://koldy.net/docs/log#usage
	 */
	public static function notice(...$string) {
		static::init();

		$string = static::getMessageFromParameters($string);
		foreach (static::$writers as $writer) {
			/* @var $writer \Koldy\Log\Writer\AbstractLogWriter */
			$writer->notice($string);
		}
	}

	/**
	 * Write INFO message to log
	 *
	 * @param array|string ...$string
	 *
	 * @link http://koldy.net/docs/log#usage
	 */
	public static function info(...$string) {
		static::init();

		$string = static::getMessageFromParameters($string);
		foreach (static::$writers as $writer) {
			/* @var $writer \Koldy\Log\Writer\AbstractLogWriter */
			$writer->info($string);
		}
	}

	/**
	 * Write WARNING message to log
	 *
	 * @param array|string ...$string
	 *
	 * @link http://koldy.net/docs/log#usage
	 */
	public static function warning(...$string) {
		static::init();

		$string = static::getMessageFromParameters($string);
		foreach (static::$writers as $writer) {
			/* @var $writer \Koldy\Log\Writer\AbstractLogWriter */
			$writer->warning($string);
		}
	}

	/**
	 * Write ERROR message to log
	 *
	 * @param array|string ...$string
	 *
	 * @link http://koldy.net/docs/log#usage
	 */
	public static function error(...$string) {
		static::init();

		$string = static::getMessageFromParameters($string);
		foreach (static::$writers as $writer) {
			/* @var $writer \Koldy\Log\Writer\AbstractLogWriter */
			$writer->error($string);
		}
	}

	/**
	 * Write SQL query to log
	 *
	 * @param string $sql
	 * @link http://koldy.net/docs/log#usage
	 */
	public static function sql($sql) {
		static::init();

		foreach (static::$writers as $writer) {
			/* @var $writer \Koldy\Log\Writer\AbstractLogWriter */
			$writer->sql($sql);
		}
	}

	/**
	 * This method is called internally on request shutdown event. Do not use
	 * it on your own!
	 */
	public static function shutdown() {
		if (static::isEnabled()) {
			foreach (static::$writers as $writer) {
				/* @var $writer \Koldy\Log\Writer\AbstractLogWriter */
				$writer->shutdown();
			}
		}
	}

}
