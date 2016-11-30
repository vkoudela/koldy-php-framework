<?php namespace Koldy\Log\Writer;

use Koldy\Exception;

/**
 * This log writer will simply send logged message by e-mail.
 * 
 * @link http://koldy.net/docs/log/email
 */
class Custom extends AbstractLogWriter {

	/**
	 * The flag we're already sending an e-mail, to prevent recursion
	 * 
	 * @var boolean
	 */
	private $working = false;

	/**
	 * Construct the DB writer
	 * 
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct(array $config) {
		if (isset($config['handler']) && !is_callable($config['handler'])) {
			throw new Exception('handler in Custom writer options is not callable');
		}

		parent::__construct($config);
	}

	/**
	 * @param string $level
	 * @param mixed $message
	 */
	protected function logMessage($level, $message) {
		if ($this->working) {
			return;
		}

		if (in_array($level, $this->config['log'])) {
			$handler = $this->config['handler'];

			$this->working = true;
			call_user_func($handler, $level, $message);
			$this->working = false;
		}
	}

}
