<?php namespace Koldy\Mail\Driver;

use Koldy\Exception;

/**
 * This mail driver class will use just internal mail() function to send an e-mail.
 * 
 * @link http://koldy.net/docs/mail/mail
 * @link http://php.net/manual/en/function.mail.php
 */
class Mail extends AbstractDriver {

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

		$to = implode(', ', $to);

		if (count($cc) > 0) {
			$this->header('Cc', implode(', ', $cc));
		}

		if (count($bcc) > 0) {
			$this->header('Bcc', implode(', ', $bcc));
		}

		$this->header('X-Mailer', 'PHP/' . phpversion());

		$charset = isset($this->config['charset']) ? $this->config['charset'] : 'utf-8';

		$contentType = ($this->isHTML)
			? ('Content-type: text/html; charset=' . $charset)
			: ('Content-type: text/plain; charset=' . $charset);

		$this->header('Content-type', $contentType);

		if (mail($to, $this->subject, $this->body, implode("\r\n", $this->getHeadersList()))) {
			return true;
		} else {
			return false;
		}
	}

}
