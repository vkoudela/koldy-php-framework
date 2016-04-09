<?php namespace Koldy\Mail\Driver;

use Koldy\Exception;
use Koldy\Log;

/**
 * This is mail driver class that won't do anything. Instead of actually sending the mail, this class will dump email data into log [INFO].
 *
 * @link http://koldy.net/docs/mail/simulate
 */
class Simulate extends AbstractDriver {

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
	private $fromEmail = null;

	/**
	 * @var string
	 */
	private $fromName = null;

	/**
	 * @var string
	 */
	private $replyTo = null;

	/**
	 * @var string
	 */
	private $subject = null;

	/**
	 * @var string
	 */
	private $body = null;

	/**
	 * @var string
	 */
	private $alternativeText = null;

	/**
	 * @var bool
	 */
	private $isHTML = false;

	/**
	 * Set email's "from"
	 *
	 * @param string $email
	 * @param string $name [optional]
	 *
	 * @return $this
	 */
	public function from($email, $name = null) {
		$this->fromEmail = $email;
		$this->fromName = $name;

		return $this;
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
		$this->replyTo = $this->getAddressValue($email, $name);
		return $this;
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
	 * Set email's "cc"
	 *
	 * @param string $email
	 * @param string $name [optional]
	 *
	 * @return $this
	 */
	public function cc($email, $name = null) {
		$this->cc[] = array(
			'email' => $email,
			'name' => $name
		);

		return $this;
	}

	/**
	 * Set email's "bcc"
	 *
	 * @param string $email
	 * @param string $name [optional]
	 *
	 * @return $this
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
	 * @param boolean $isHTML
	 * @param string $alternativeText
	 *
	 * @return $this
	 */
	public function body($body, $isHTML = false, $alternativeText = null) {
		$this->body = $body;
		$this->isHTML = $isHTML;
		$this->alternativeText = $alternativeText;
		return $this;
	}

	/**
	 * @param string $filePath
	 * @param string $name [optional]
	 *
	 * @return $this
	 */
	public function attachFile($filePath, $name = null) {
		return $this;
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function send() {
		$from = ($this->fromName !== null) ? "{$this->fromName} <{$this->fromEmail}>" : $this->fromEmail;

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
			$cc = ' CC=' . implode(', ', $cc);
		}

		if (count($bcc) > 0) {
			$bcc = ' BCC=' . implode(', ', $bcc);
		}

		$replyTo = '';
		if ($this->replyTo != null) {
			$replyTo = ' replyTo=' . $this->replyTo;
		}

		Log::info("E-mail [SIMULATED] is sent FROM={$from}{$replyTo} TO={$to}{$cc}{$bcc} with subject \"{$this->subject}\" and content length: " . strlen($this->body));
		return true;
	}

}
