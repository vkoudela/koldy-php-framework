<?php namespace Koldy\Log\Writer;

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
	abstract protected function logMessage($level, $message);

	/**
	 * Write EMERGENCY message to log
	 *
	 * @param string $message
	 * @link http://koldy.net/docs/log#usage
	 */
	public function emergency($message) {
		$this->logMessage('emergency', $message);
	}

	/**
	 * Write ALERT message to log
	 *
	 * @param string $message
	 * @link http://koldy.net/docs/log#usage
	 */
	public function alert($message) {
		$this->logMessage('alert', $message);
	}

	/**
	 * Write CRITICAL message to log
	 *
	 * @param string $message
	 * @link http://koldy.net/docs/log#usage
	 */
	public function critical($message) {
		$this->logMessage('critical', $message);
	}

	/**
	 * Write DEBUG message to log
	 *
	 * @param string $message
	 * @link http://koldy.net/docs/log#usage
	 */
	public function debug($message) {
		$this->logMessage('debug', $message);
	}

	/**
	 * Write NOTICE message to log
	 *
	 * @param string $message
	 * @link http://koldy.net/docs/log#usage
	 */
	public function notice($message) {
		$this->logMessage('notice', $message);
	}

	/**
	 * Write SQL message to log
	 *
	 * @param string $query
	 * @link http://koldy.net/docs/log#usage
	 */
	public function sql($query) {
		$this->logMessage('sql', $query);
	}

	/**
	 * Write INFO message to log
	 *
	 * @param string $message
	 * @link http://koldy.net/docs/log#usage
	 */
	public function info($message) {
		$this->logMessage('info', $message);
	}

	/**
	 * Write WARNING message to log
	 *
	 * @param string $message
	 * @link http://koldy.net/docs/log#usage
	 */
	public function warning($message) {
		$this->logMessage('warning', $message);
	}

	/**
	 * Write ERROR message to log
	 *
	 * @param string $message
	 * @link http://koldy.net/docs/log#usage
	 */
	public function error($message) {
		$this->logMessage('error', $message);
	}

	/**
	 * Override this method if you have anything you need to do on
	 * request shutdown except of just sending e-mail alerts
	 */
	public function shutdown() {}

}
