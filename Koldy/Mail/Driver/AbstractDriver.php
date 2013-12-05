<?php namespace Koldy\Mail\Driver;

abstract class AbstractDriver {

	protected $config;

	public function __construct($config){
		$this->config = $config;
	}

	public function getHost() {
		return $this->config['host'];
	}

	public function getPort() {
		return $this->config['port'];
	}

	public function getUsername() {
		return $this->config['username'];
	}

	public function getPassword() {
		return $this->config['password'];
	}

	public function isSecure() {
		return $this->config['secure'];
	}

	/**
	 * Set From
	 * @param string $email
	 * @param string $name [optional]
	 * @return \Koldy\Mail\Driver\AbstractDriver
	 */
	abstract public function from($email, $name = null);

	/**
	 * Send mail to this e-mail
	 * @param string $email
	 * @param string $name [optional]
	 * @return \Koldy\Mail\Driver\AbstractDriver
	 */
	abstract public function to($email, $name = null);

	/**
	 * Set the e-mail subject
	 * @param string $subject
	 * @return \Koldy\Mail\Driver\AbstractDriver
	 */
	abstract public function subject($subject);

	/**
	 * Set e-mail body
	 * @param string $body
	 * @param boolean $isHTML
	 * @param string $alternativeText The plain text
	 * @return \Koldy\Mail\Driver\AbstractDriver
	 */
	abstract public function body($body, $isHTML = false, $alternativeText = null);

	/**
	 * Send e-mail
	 */
	abstract public function send();

}