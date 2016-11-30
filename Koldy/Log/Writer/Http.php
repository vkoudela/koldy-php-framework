<?php namespace Koldy\Log\Writer;

use Koldy\Exception;
use Koldy\Http\Request as HttpRequest;
use Koldy\Log;

/**
 * This log writer will simply send logged message by e-mail.
 * 
 * @link http://koldy.net/docs/log/http
 */
class Http extends AbstractLogWriter {

	/**
	 * The flag we're already sending an e-mail, to prevent recursion
	 * 
	 * @var boolean
	 */
	private $sending = false;

	/**
	 * The array of last X messages (by default, the last 100 messages)
	 *
	 * @var array
	 */
	protected $messages = array();

	/**
	 * Construct the DB writer
	 * 
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct(array $config) {
		if (!isset($config['send_immediately'])) {
			$config['send_immediately'] = false;
		}

		if (isset($config['prepare_request']) && !is_callable($config['prepare_request'])) {
			throw new Exception('prepare_request in HTTP writer options is not callable');
		}

		parent::__construct($config);
	}

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
	 * @param string $level
	 * @param mixed $message
	 */
	protected function logMessage($level, $message) {
		if ($this->sending) {
			return;
		}

		if (in_array($level, $this->config['log'])) {

			$data = array(
				'time' => gmdate('Y-m-d H:i:sO'),
				'level' => $level,
				'who' => Log::who(),
				'message' => $message
			);

			$message = implode("\t", array_values($data));

			if ($this->config['send_immediately']) {
				$this->sendRequest($data);
			} else {
				$this->appendMessage($message);
			}
		}
	}

	/**
	 * Send HTTP request if system detected that request should be sent
	 *
	 * @param null|array $message
	 */
	protected function sendRequest(array $message = null) {
		if ($message === null) {
			$messages = implode("\n", $this->messages);
		} else {
			$messages = $message;
		}

		$this->sending = true;
		/** @var \Closure $prepareRequest */
		$prepareRequest = $this->config['prepare_request'];

		/** @var HttpRequest $request */
		$request = $prepareRequest($messages);

		if (!($request instanceof HttpRequest)) {
			Log::emergency('Log/HTTP adapter prepare_request doesn\'t return instance of \Koldy\Http\Request');
			$this->sending = false;
		} else {
			try {
				$request->exec();
			} catch (Exception $e) {
				Log::alert('Can not send log by e-mail', $e);
			}
		}
	}

	public function shutdown() {
		if (count($this->messages) > 0) {
			$this->sendRequest();
		}
	}

}
