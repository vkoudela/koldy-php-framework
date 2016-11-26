<?php namespace Koldy\Log\Writer;

use Koldy\Exception;
use Koldy\Mail;
use Koldy\Request;

/**
 * This log writer will simply send logged message by e-mail.
 * 
 * @link http://koldy.net/docs/log/email
 */
class Email extends AbstractLogWriter {

	/**
	 * The flag we're already sending an e-mail, to prevent recursion
	 * 
	 * @var boolean
	 */
	private $emailing = false;

	/**
	 * @var string
	 */
	private $firstMessage = null;

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

		if (!isset($config['to']) || strlen($config['to']) < 5) {
			throw new Exception('Email log sender don\'t have to field');
		}

		if (isset($config['get_data_fn']) && !is_callable($config['get_data_fn'])) {
			throw new Exception('get_data_fn in DB writer options is not callable');
		}

		if (!isset($config['driver'])) {
			$config['driver'] = null;
		}

		parent::__construct($config);
	}

	/**
	 * Get array of field=>value to be inserted in log table
	 * 
	 * @param string $level
	 * @param string $message
	 * @throws \Koldy\Exception
	 * @return array
	 */
	protected function getEmailMessage($level, $message) {
		if (isset($this->config['get_data_fn'])) {
			$data = call_user_func($this->config['get_data_fn'], $level, $message);

			if (!is_array($data)) {
				throw new Exception('Email driver config get_data_fn function must return an array; ' . gettype($data) . ' given');
			}

			return $data;
		}

		return array(
			'time' => gmdate('Y-m-d H:i:sO'),
			'level' => $level,
			'message' => $message
		);
	}

	/**
	 * @param string $level
	 * @param mixed $message
	 */
	protected function logMessage($level, $message) {
		if ($this->emailing) {
			return;
		}

		$data = $this->getEmailMessage($level, $message);

		if ($data !== false) {
			if (in_array($level, $this->config['log'])) {
				$message = implode("\t", array_values($data));

				if (count($this->messages) == 0) {
					$this->firstMessage = $data['message'];
				}

				if ($this->config['send_immediately']) {
					$this->sendEmail($message);
				}

				$this->appendMessage($message);
			}
		}
	}

	/**
	 * Send e-mail report if system detected that e-mail should be sent
	 *
	 * @param null|string $message
	 *
	 * @return bool|null true if mail was sent and null if mail shouldn't be sent
	 */
	protected function sendEmail($message = null) {
		if ($message === null) {
			$body = implode('', $this->messages);
		} else {
			$body = $message;
		}

		$body .= "\n\n----------\n";
		$body .= Request::signature(true);

		$mail = Mail::create($this->config['driver']);
		$mail
			->from('alert@' . Request::hostName(), Request::hostName())
			->subject(strlen($this->firstMessage) > 200 ? (substr($this->firstMessage, 0, 200) . '...') : $this->firstMessage)
			->body($body);

		$to = $this->config['to'];
		if (!is_array($this->config['to']) && strpos($this->config['to'], ',') !== false) {
			$to = explode(',', $this->config['to']);
		}

		if (is_array($to)) {
			foreach ($to as $toEmail) {
				$mail->to(trim($toEmail));
			}
		} else {
			$mail->to(trim($to));
		}

		try {
			$this->emailing = true;
			$mail->send();
			$this->emailing = false;
		} catch (Exception $e) {
			if (is_array($to)) {
				$to = implode(', ', $to);
			}

			$this->error("Can not send alert mail to {$to}: {$e->getMessage()}");
		}
	}

	public function shutdown() {
		$this->sendEmail();
	}

}
