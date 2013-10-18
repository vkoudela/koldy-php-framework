<?php namespace Koldy\Application\Route;

abstract class AbstractRoute {

	protected $uri = null;

	public function __construct($uri) {
		$this->uri = explode('/', $uri);
	}

	abstract public function getControllerUrl();

	abstract public function getControllerClass();

	abstract public function getControllerPath();

	abstract public function getActionUrl();

	abstract public function getActionMethod();
	
	/**
	 * Is this request Ajax request or not?
	 * @return boolean
	 */
	abstract public function isAjax();

	abstract public function href($controller, $action = null, array $params = null);

	abstract public function link($path);

	abstract public function cdn($path);
}