<?php namespace Koldy\Log\Writer;

use Koldy\Application;
use Koldy\Convert;
use Koldy\Mail;
use Koldy\Request;
use Koldy\Server;

/**
 * If you plan to create your own log writer, then please extend this class and
 * then do whatever you want to.
 *
 * @link http://koldy.net/docs/log
 */
abstract class AbstractLogWriter {


	/**
	 * The config array got from 'options' part in config/application.php
	 * 
	 * @var array
	 */
	protected $config = null;


	/**
	 * The array of last X messages (by default, the last 100 messages)
	 * 
	 * @var array
	 */
	private $messages = array();


	/**
	 * To send email report or not
	 * 
	 * @var boolean
	 */
	private $emailReport = false;


	/**
	 * Constructor
	 * 
	 * @param array $config
	 */
	public function __construct(array $config) {
		$this->config = $config;
	}
	
	/**
	 * Handle message logging
	 * 
	 * @param string $level
	 * @param mixed $message
	 */
	abstract public function logMessage($level, $message);


	/**
	 * Append log message to the request's scope
	 * 
	 * @param string $message
	 */
	protected function appendMessage($message) {
		$this->messages[] = $message;

		if (sizeof($this->messages) > 100) {
			array_shift($this->messages);
		}
	}


	/**
	 * Detect if e-mail alert should be sent
	 * 
	 * @param string $level
	 */
	protected function detectEmailAlert($level) {
		if (!$this->emailReport && $this->config['email'] !== null && in_array($level, $this->config['email_on'])) {
			$this->emailReport = true;
		}
	}


	/**
	 * Override this method if you have anything you need to do on
	 * request shutdown except of just sending e-mail alerts
	 */
	public function shutdown() {
		$this->sendEmailReport();
	}


	/**
	 * Process extended reports
	 */
	protected function processExtendedReports() {
		$dump = $this->config['dump'];

		//'speed', 'included_files', 'include_path'

		if (in_array('speed', $dump)) {
			$method = isset($_SERVER['REQUEST_METHOD'])
				? ($_SERVER['REQUEST_METHOD'] . '=' . Application::getUri())
				: ('CLI=' . Application::getCliName());

			$executedIn = Application::getRequestExecutionTime();
			$this->logMessage('notice', $method . ' LOADED IN ' . $executedIn . 'ms, ' . sizeof(get_included_files()) . ' files');
		}

		if (in_array('included_files', $dump)) {
			$this->logMessage('notice', print_r(get_included_files(), true));
		}

		if (in_array('include_path', $dump)) {
			$this->logMessage('notice', print_r(explode(':', get_include_path()), true));
		}
	}


	/**
	 * Send e-mail report if system detected that e-mail should be sent
	 * 
	 * @return boolean|null true if mail was sent and null if mail shouldn't be sent
	 */
	protected function sendEmailReport() {
		if ($this->emailReport && $this->config['email'] !== null) {
			$body = implode('', $this->messages);
			$body .= "\n\n---------- debug_backtrace:\n";

			foreach (debug_backtrace() as $r) {
				if (isset($r['file']) && isset($r['line'])) {
					$body .= "{$r['file']}:{$r['line']} ";
				}

				if (isset($r['function'])) {
					$body .= "{$r['function']} ";
				}

				if (isset($r['args'])) {
					$body .= implode(', ', $r['args']);
				}

				$body .= "\n";
			}
			
			$body .= "\n----------\n";
			$body .= sprintf("server: %s (%s)\n", Request::serverIp(), Request::hostName());

			if (PHP_SAPI != 'cli') {
				$body .= 'URI: ' . $_SERVER['REQUEST_METHOD'] . '=' . Application::getConfig('application', 'site_url') . Application::getUri() . "\n";
				$body .= sprintf("User IP: %s (%s)%s", Request::ip(), Request::host(), (Request::hasProxy() ? sprintf(" via %s for %s\n", Request::proxySignature(), Request::httpXForwardedFor()) : "\n"));
				$body .= sprintf("UAS: %s\n", (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'no user agent set'));
			} else {
				$body .= 'CLI Name: ' . Application::getCliName() . "\n";
				$body .= 'CLI Script: ' . Application::getCliScript() . "\n";
			}

			$body .= sprintf("Server load: %s\n", Server::getServerLoad());

			$peak = memory_get_peak_usage(true);
			$memoryLimit = ini_get('memory_limit');

			$body .= sprintf("Memory: %s; peak: %s; limit: %s; spent: %s%%\n",
				Convert::bytesToString(memory_get_usage(true)),
				Convert::bytesToString($peak),
				$memoryLimit,
				($memoryLimit !== false && $memoryLimit > 0 ? round($peak * 100 / Convert::stringToBytes($memoryLimit), 2) : 'null')
			);

			$body .= sprintf("included files: %s\n", print_r(get_included_files(), true));

			$mail = Mail::create();
			$mail
				->from('alert@' . Request::hostName(), Request::hostName())
				->subject('Log report')
				->body($body);

			if (!is_array($this->config['email']) && strpos($this->config['email'], ',') !== false) {
				$this->config['email'] = explode(',', $this->config['email']);
			}

			if (is_array($this->config['email'])) {
				foreach ($this->config['email'] as $toEmail) {
					$mail->to(trim($toEmail));
				}
			} else {
				$mail->to(trim($this->config['email']));
			}

			if (!$mail->send()) {
				$this->error("Can not send alert mail to {$this->config['email']}: {$mail->getError()}\n{$mail->getException()->getTraceAsString()}");
				return false;
			}

			return true;
		}

		return null;
	}

}
