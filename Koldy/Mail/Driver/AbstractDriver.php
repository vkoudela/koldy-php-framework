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

	abstract public function from($email, $name = null);

	abstract public function to($email, $name = null);

	abstract public function subject($subject);

	abstract public function body($body, $isHTML = false, $alternativeText = null);

	abstract public function send();

}