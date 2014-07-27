<?php namespace Koldy\Log\Writer;
/**
 * This log writer will print all messages to console. This writer is made to
 * be used in CLI environment.
 * 
 * @link http://koldy.net/docs/log/out
 *
 */
class Out extends AbstractLogWriter {


	/**
	 * Get the message that will be printed in console. You have to return the
	 * whole line including the time if you want. This is default, but you can
	 * override this method.
	 * 
	 * @param string $level
	 * @param string $message
	 * @return string
	 */
	protected function getMessage($level, $message) {
		return date('Y-m-d H:i:sO') . "\t{$level}\t{$message}\n";
	}


	/**
	 * Actually print message out
	 * 
	 * @param string $level
	 * @param string $message
	 * @throws \Koldy\Exception
	 */
	public function logMessage($level, $message) {
		if (is_object($message)) {
			$message = $message->__toString();
		}

		$logMessage = $this->getMessage($level, $message);

		if (in_array($level, $this->config['log'])) {
			print $logMessage;
		}

		$this->detectEmailAlert($level);
		$this->appendMessage($logMessage);
	}


	/**
	 * This method is called internally.
	 */
	public function shutdown() {
		$this->processExtendedReports();
		$this->sendEmailReport();
	}

}
