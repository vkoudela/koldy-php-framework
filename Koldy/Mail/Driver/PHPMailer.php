<?php namespace Koldy\Mail\Driver;

use Koldy\Mail\DriverInterface;

require_once realpath(dirname(__FILE__) . '/../../../PHPMailer/class.phpmailer.php');

class PHPMailer extends AbstractDriver {

	private $mailer = null;

	public function __construct($config) {
		parent::__construct($config);

		$this->mailer = new \PHPMailer();
		$this->mailer->Host = $config['host'];
		$this->mailer->Port = $config['port'];

		if (isset($config['username']) && $config['username'] !== null) {
			$this->mailer->Username = $config['username'];
		}

		if (isset($config['password']) && $config['password'] !== null) {
			$this->mailer->Password = $config['password'];
		}

		switch($config['type']) {
			default:
			case 'smtp': $this->mailer->IsSMTP(); break;
			case 'mail': $this->mailer->IsMail(); break;
		}
	}

	public function from($email, $name = null) {
		$this->mailer->SetFrom($email, $name);
		return $this;
	}

	public function to($email, $name = null) {
		$this->mailer->AddAddress($email, $name === null ? '' : $name);
		return $this;
	}

	public function subject($subject) {
		$this->mailer->Subject = $subject;
		return $this;
	}

	/**
	 * Sets the e-mail's body in HTML format. If you want to send plain text only, please use plain() method.
	 * @param string $body
	 * @see setText()
	 */
	public function body($body, $isHTML = false, $alternativeText = null) {
		$this->mailer->Body = $body;

		if ($isHTML) {
			$this->mailer->IsHTML();
		}

		if ($alternativeText !== null) {
			$this->mailer->AltBody = $alternativeText;
		}

		return $this;
	}

	public function send() {
		try {
			return $this->mailer->Send();
		} catch(phpmailerException $e) {
			return false;
		}
	}

}