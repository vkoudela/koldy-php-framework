<?php namespace Koldy;
/**
 * Class to handle the log and writing to log. Be aware that using too much of log rapidly slows down the
 * complete application, while other processes are waiting to finish your log. Ofcourse, you can rapidly optimze
 * this by using slightly different syntax. As you already know, if you have your log disabled, but you're still
 * calling for an example Log::info("User {$fullName} has logged in"); the PHP interpreter will still have to
 * parse the passing string and then inside of info() method, message will be disregarded. To avoid this, you can
 * use the following syntax:
 * 
 * @example		if (LOG) Log::info("User {$fullName} has logged in");
 * 
 * The LOG constant will always have the same value as $config['log']['enabled'] located in config.application.php
 *
 * You are encouraged to use log in development, but reduce logs in production mode as much as you can. Always
 * log only important data and never log the code that will always execute successfully.
 *
 * Using file output, this singleton instance will open the file on the first call and won't be closed. Instead
 * of closing, class will register itself in application's
 * shutdown proceses and will close the log file when complete request finish its job. That way log file won't be
 * opened and closed every time you want to log something. This method rapidly increases page load speed.
 *
 * If you have enabled email logging, then this script will send you log message(s) to your error mail. To reduce
 * SPAM, if there are a lot of error messages to send, all other log messages will be mailed at once as well. Lets
 * say you have 5 info log messages, 1 notice and 1 error - you'll receive error mail with all those log messages
 * and with all other debug informations.
 *
 * @author Vlatko Koudela
 * 
 */

class Log {

	/**
	 * The file pointer
	 * @var resource
	 */
	public static $fp = null;

	/**
	 * Local collection of all log messages
	 * @var array
	 */
	private static $messages = array();

	/**
	 * The config part from config.application.php
	 * @var array $config
	 */
	public static $config = null;

	/**
	 * Is this class initiated or not
	 * @var boolean
	 */
	private static $initialized = false;

	/**
	 * Send email report or not
	 */
	public static $emailReport = false;

	protected function __construct() {}
	protected function __clone() {}

	private static function logMessage($level, &$message) {
		if (!self::$initialized) {
			self::$initialized = true;
			$config = Application::getConfig('application');
			self::$config = $config['log'];

			if (!self::$config['enabled']) {
				return;
			}

			if (self::$config['path'] === null) {
				$path = Application::getStoragePath() . 'log' . DS;
			} else {
				$path = self::$config['path'];
				if (substr($path, -1, 1) != DS) {
					$path .= DS;
				}
			}

			$path .= date('Y-m-d') . '.log';

			self::$fp = fopen($path, 'a');

			register_shutdown_function(function() {
				if (Log::$fp !== null) {
					fclose(Log::$fp);
				}

				if (Log::$emailReport && Log::$config['email'] !== null) {
					$body = implode('', Log::getMessages());
					$body .= "----------\n";
					$body .= sprintf("server: %s (%s)\n", Server::getServerAddr(), Server::getServerHost());
					$body .= sprintf("URI: %s=%s\n", (isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : 'CLI', Application::getUri());
					$body .= sprintf("User IP: %s (%s)%s", Request::getUserIp(), Request::getRemoteHost(), (Request::hasProxy() ? sprintf(" via %s for %s\n", Request::getProxySignature(), Request::getProxyForwardedFor()) : "\n"));
					$body .= sprintf("UAS: %s\n", (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'no user agent set'));
					$body .= sprintf("Server load: %s\n", Server::getServerLoad());

					$peak = memory_get_peak_usage(true);
					$memoryLimit = ini_get('memory_limit');
					$body .= sprintf("Memory: %s; peak: %s; limit: %s; spent: %s%%\n",
						Convert::bytesToString(memory_get_usage(true)),
						Convert::bytesToString($peak),
						$memoryLimit,
						($memoryLimit !== false ? round($peak * 100 / Convert::shorthandToBytes($memoryLimit), 2) : 'null')
					);

					$body .= sprintf("included files: %s\n", print_r(get_included_files(), true));

					$mail = Mail::create();
					$mail->from('logreport@' . Server::getServerHost(), Server::getServerHost())
						->to(Log::$config['email'])
						->subject('Log report')
						->body($body)
						->send();
				}
			});
		}

		if (!self::$config['enabled']) {
			return;
		} else if (!isset(self::$config['level'][$level]) || !self::$config['level'][$level]) {
			return;
		}

		$user = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';

		if (is_object($message)) {
			$message = $message->__toString();
		}

		$logMessage = date('Y-m-d H:i:sO') . "\t{$level}\t{$user}\t{$message}\n";
		fwrite(self::$fp, $logMessage);
		self::$messages[] = $logMessage;

		if (!self::$emailReport && isset(self::$config['email_level'][$level]) && self::$config['email_level'][$level]) {
			self::$emailReport = true;
		}
	}

	public static function getMessages() {
		return self::$messages;
	}

	public static function debug($string) {
		self::logMessage('debug', $string);
	}

	public static function notice($string) {
		self::logMessage('notice', $string);
	}

	public static function info($string) {
		self::logMessage('info', $string);
	}

	public static function warning($string) {
		self::logMessage('warning', $string);
	}

	public static function error($string) {
		self::logMessage('error', $string);
	}

	public static function sql($sql) {
		self::logMessage('sql', $sql);
	}

}