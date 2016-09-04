<?php namespace Koldy\Mail\Driver;

use Koldy\Application;
use Koldy\Directory;
use Koldy\Exception;

/**
 * This mail driver class will create nice file where all email details will be printed
 * 
 * @link http://koldy.net/docs/mail/file
 * @link http://php.net/manual/en/function.mail.php
 */
class File extends AbstractDriver {

	/**
	 * The array of recipients
	 * 
	 * @var array
	 */
	private $to = array();

	/**
	 * The array of CC recipients
	 *
	 * @var array
	 */
	private $cc = array();

	/**
	 * The array of BCC recipients
	 *
	 * @var array
	 */
	private $bcc = array();

	/**
	 * @var string
	 */
	private $subject = null;

	/**
	 * @var string
	 */
	private $body = null;

	/**
	 * @var boolean
	 */
	private $isHTML = null;

	/**
	 * @var string
	 */
	private $alternativeText = null;

	/**
	 * Set email's "from"
	 *
	 * @param string $email
	 * @param string $name [optional]
	 *
	 * @return $this
	 */
	public function from($email, $name = null) {
		return $this->header('From', $this->getAddressValue($email, $name));
	}

	/**
	 * Set email's "Reply To" option
	 *
	 * @param string $email
	 * @param string $name [optional]
	 *
	 * @return $this
	 */
	public function replyTo($email, $name = null) {
		return $this->header('Reply-To', $this->getAddressValue($email, $name));
	}

	/**
	 * Set email's "to"
	 *
	 * @param string $email
	 * @param string $name [optional]
	 *
	 * @return $this
	 */
	public function to($email, $name = null) {
		$this->to[] = array(
			'email' => $email,
			'name' => $name
		);
		return $this;
	}

	/**
	 * Send mail carbon copy
	 *
	 * @param string $email
	 * @param string $name [optional]
	 *
	 * @return $this
	 * @link http://koldy.net/docs/mail#example
	 */
	public function cc($email, $name = null) {
		$this->cc[] = array(
			'email' => $email,
			'name' => $name
		);
		return $this;
	}

	/**
	 * Send mail blind carbon copy
	 *
	 * @param string $email
	 * @param string $name [optional]
	 *
	 * @return $this
	 * @link http://koldy.net/docs/mail#example
	 */
	public function bcc($email, $name = null) {
		$this->bcc[] = array(
			'email' => $email,
			'name' => $name
		);
		return $this;
	}

	/**
	 * Set email's subject
	 *
	 * @param string $subject
	 *
	 * @return $this
	 */
	public function subject($subject) {
		$this->subject = $subject;
		return $this;
	}

	/**
	 * Sets the e-mail's body in HTML format. If you want to send plain text only, please use plain() method.
	 *
	 * @param string $body
	 * @param bool $isHTML
	 * @param string $alternativeText is email's plain text which will be shown if recipient's email client doesn't support HTML version
	 *
	 * @return $this
	 */
	public function body($body, $isHTML = false, $alternativeText = null) {
		$this->body = is_object($body) && method_exists($body, '__toString') ? $body->__toString() : $body;
		$this->isHTML = $isHTML;
		$this->alternativeText = $alternativeText;

		if ($isHTML) {
			$this->header('MIME-Version', '1.0');
		}

		return $this;
	}

	/**
	 * @param string $filePath
	 * @param string $name
	 *
	 * @return void
	 * @throws Exception
	 */
	public function attachFile($filePath, $name = null) {
		throw new Exception('AttachFile is not implemented in Mail driver');
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function send() {
		$content = array();

		if ($this->hasHeader('From')) {
			$content[] = 'From: ' . $this->getHeader('From');
			$this->removeHeader('From');
		}

		$to = $cc = $bcc = array();

		if (count($this->to) == 0) {
			throw new Exception('There\'s no recipients to send email to');
		}

		foreach ($this->to as $address) {
			$to[] = $this->getAddressValue($address['email'], $address['name']);
		}

		foreach ($this->cc as $address) {
			$cc[] = $this->getAddressValue($address['email'], $address['name']);
		}

		foreach ($this->bcc as $address) {
			$bcc[] = $this->getAddressValue($address['email'], $address['name']);
		}

		$content[] = 'To: ' . implode(', ', $to);

		if (count($cc) > 0) {
			$content[] = 'Cc: ' . implode(', ', $cc);
		}

		if (count($bcc) > 0) {
			$content[] = 'Bcc: ' . implode(', ', $bcc);
		}

		if ($this->hasHeader('Reply-To')) {
			$content[] = 'Reply-To: ' . $this->getHeader('Reply-To');
			$this->removeHeader('Reply-To');
		}

		$content[] = 'Subject: ' . $this->subject;

		$this->header('X-Mailer', 'PHP/' . phpversion());

		$charset = isset($this->config['charset']) ? $this->config['charset'] : 'utf-8';

		$contentType = ($this->isHTML)
			? ('Content-type: text/html; charset=' . $charset)
			: ('Content-type: text/plain; charset=' . $charset);

		$this->header('Content-type', $contentType);

		$content = implode("\n", $content) . "\n" . str_repeat('=', 80) . "\n";

		$content .= $this->body;

		if ($this->alternativeText != null) {
			$content .= "\n" . str_repeat('=', 80) . "\n";
			$content .= $this->alternativeText;
		}

		$now = \DateTime::createFromFormat('U.u', microtime(true));
		$time = $now->format('Y-m-d H-i-s.u');

		if (!isset($this->config['location'])) {
			$file = Application::getStoragePath('email' . DS . "{$time}.txt");
		} else {
			$location = $this->config['location'];

			if (substr($location, 0, 8) == 'storage:') {
				$file = Application::getStoragePath(substr($location, 8) . DS . "{$time}.txt");
			} else {
				$file = $location . DS . "{$time}.txt";
				$file = str_replace(DS.DS, DS, $file);
			}
		}

		$directory = dirname($file);
		if (!is_dir($directory)) {
			if (!Directory::mkdir($directory, 0755)) {
				throw new Exception('Sending email failed, can not create directory: ' . $directory);
			}
		}

		return file_put_contents($file, $content) !== false;
	}

}
