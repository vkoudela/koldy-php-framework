<?php namespace Koldy\Log\Writer;

use Koldy\Application;

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
	protected function logMessage($level, $message) {
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


	/**
	 * (non-PHPdoc)
	 * @see \Koldy\Log\Writer\AbstractLogWriter::processExtendedReports()
	 */
	protected function processExtendedReports() {
		if (!isset($this->config['dump'])) {
			return;
		}
	
		$dump = $this->config['dump'];
	
		// 'speed', 'included_files', 'include_path', 'whitespace'
	
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
	
		if (in_array('whitespace', $dump)) {
			$this->logMessage('notice', "----------\n\n\n");
		}
	}
}
