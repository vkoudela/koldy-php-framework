<?php namespace Koldy\Application\Route;
/**
 * I call this the default route because this will be just fine for the most
 * sites in the world. This class will parse and generate the URLs to the
 * following criteria:
 *
 * 	http://your.domain.com/[controller]/[action]/[param1]/[param2]/[paramN]
 *  or if module exists under that controller URL:
 *  http://your.domain.com/[module]/[controller]/[action]/[param1]/[param2]/[paramN]
 */

use Koldy\Application;

class DefaultRoute extends AbstractRoute {

	private $controllerUrl = null;

	private $controllerClass = null;

	private $controllerPath = null;

	private $actionUrl = null;

	private $actionMethod = null;
	
	private $isAjax = false;
	
	public function __construct($uri) {
		parent::__construct($uri);

		// There are two possible scenarios:
		// 1. The first part of URL leads to the module controller
		// 2. The first part of URL leads to the default controller
		
		if ($this->uri[1] == '') {
			$this->controllerUrl = 'index';
			$this->controllerClass = 'IndexController';
		} else {
			$this->controllerUrl = strtolower($this->uri[1]);
			$this->controllerClass = str_replace(' ', '', ucwords(str_replace('-', ' ', $this->controllerUrl))) . 'Controller';
		}

		// Now we have the controller class name detected, but, should it be
		// taken from module or from default controllers?
		
		$moduleDir = Application::getApplicationPath() . 'modules' . DS . $this->controllerUrl;
		if (is_dir($moduleDir)) {
			// ok, it is a module with module/controller/action path
			$moduleUrl = $this->controllerUrl;
			if (isset($this->uri[2]) && $this->uri[2] != '') {
				$this->controllerUrl = strtolower($this->uri[2]);
				$this->controllerClass = str_replace(' ', '', ucwords(str_replace('-', ' ', $this->controllerUrl))) . 'Controller';
			} else {
				$this->controllerUrl = 'index';
				$this->controllerClass = 'IndexController';
			}
			$this->controllerPath = $moduleDir . DS . 'controllers' . DS . $this->controllerClass . '.php';
			
			$mainControllerExists = true;

			if (!is_file($this->controllerPath)) {
				$this->controllerPath = Application::getApplicationPath() . 'modules' . DS . $moduleUrl . DS . 'controllers' . DS
					. 'IndexController.php';

				if (!is_file($this->controllerPath)) {
					// Even IndexController is missing. Can not resolve that.
					if (Application::inDevelopment()) {
						$controllersPath = $moduleDir . DS . 'controllers';
						\Koldy\Log::debug("Can not find {$this->controllerClass} nor IndexController in {$controllersPath}");
					}
					Application::throwError(404, 'Page not found');
				}

				$mainControllerExists = false;
				$this->controllerClass = 'IndexController';
			}

			if ($mainControllerExists) {
				if (!isset($this->uri[3]) || $this->uri[3] == '') {
					$this->actionUrl = 'index';
					$this->actionMethod = 'index';
				} else {
					$this->actionUrl = strtolower($this->uri[3]);
					$this->actionMethod = ucwords(str_replace('-', ' ', $this->actionUrl));
					$this->actionMethod = str_replace(' ', '', $this->actionMethod);
					$this->actionMethod = strtolower(substr($this->actionMethod, 0, 1)) . substr($this->actionMethod, 1);
				}
			} else if (isset($this->uri[2]) && $this->uri[2] != '') {
				$this->actionUrl = strtolower($this->uri[2]);
				$this->actionMethod = ucwords(str_replace('-', ' ', $this->actionUrl));
				$this->actionMethod = str_replace(' ', '', $this->actionMethod);
				$this->actionMethod = strtolower(substr($this->actionMethod, 0, 1)) . substr($this->actionMethod, 1);
			} else {
				$this->actionUrl = 'index';
				$this->actionMethod = 'index';
			}

			// and now, configure the include paths according to the case
			Application::addIncludePath(array(
				// module paths has higher priority then default stuff
				$moduleDir . DS . 'controllers' . DS,
				$moduleDir . DS . 'models' . DS,
				$moduleDir . DS . 'library' . DS,
				Application::getApplicationPath() . 'controllers' . DS, // so you can extend abstract controllers in the same directory if needed,
				Application::getApplicationPath() . 'models' . DS, // all models should be in this directory
				Application::getApplicationPath() . 'library' . DS, // the place where you can define your own classes and methods
			));
		} else {
			// ok, it is the default controller/action
			$this->controllerPath = Application::getApplicationPath() . 'controllers' . DS
				. $this->controllerClass . '.php';

			$mainControllerExists = true;

			if (!is_file($this->controllerPath)) {
				$this->controllerPath = Application::getApplicationPath() . 'controllers' . DS
					. 'IndexController.php';

				if (!is_file($this->controllerPath)) {
					// Even IndexController is missing. Can not resolve that.
					if (Application::inDevelopment()) {
						$controllersPath = Application::getApplicationPath() . 'controllers';
						\Koldy\Log::debug("Can not find {$this->controllerClass} nor IndexController in {$controllersPath}");
					}
					Application::throwError(404, 'Page not found');
				}

				$mainControllerExists = false;
				$this->controllerClass = 'IndexController';
			}

			if ($mainControllerExists) {
				if (!isset($this->uri[2]) || $this->uri[2] == '') {
					$this->actionUrl = 'index';
					$this->actionMethod = 'index';
				} else {
					$this->actionUrl = strtolower($this->uri[2]);
					$this->actionMethod = ucwords(str_replace('-', ' ', $this->actionUrl));
					$this->actionMethod = str_replace(' ', '', $this->actionMethod);
					$this->actionMethod = strtolower(substr($this->actionMethod, 0, 1)) . substr($this->actionMethod, 1);
				}
			} else {
				$this->actionUrl = strtolower($this->uri[1]);
				$this->actionMethod = ucwords(str_replace('-', ' ', $this->actionUrl));
				$this->actionMethod = str_replace(' ', '', $this->actionMethod);
				$this->actionMethod = strtolower(substr($this->actionMethod, 0, 1)) . substr($this->actionMethod, 1);
			}

			// and now, configure the include paths according to the case
			Application::addIncludePath(array(
				Application::getApplicationPath() . 'controllers' . DS, // so you can extend abstract controllers in the same directory if needed,
				Application::getApplicationPath() . 'models' . DS, // all models should be in this directory
				Application::getApplicationPath() . 'library' . DS, // the place where you can define your own classes and methods
			));
		}
		
		$this->isAjax = (
			isset($_SERVER['REQUEST_METHOD'])
			&& $_SERVER['REQUEST_METHOD'] == 'POST'
			&& isset($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		) || (
			isset($_SERVER['HTTP_ACCEPT'])
			&& strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
		);

		if ($this->isAjax) {
			$this->actionMethod .= 'Ajax';
		} else {
			$this->actionMethod .= 'Action';
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Koldy\Application\Route\AbstractRoute::isAjax()
	 */
	public function isAjax() {
		return $this->isAjax;
	}

	public function getControllerUrl() {
		return $this->controllerUrl;
	}

	public function getControllerClass() {
		return $this->controllerClass;
	}

	public function getControllerPath() {
		return $this->controllerPath;
	}

	public function getActionUrl() {
		return $this->actionUrl;
	}

	public function getActionMethod() {
		return $this->actionMethod;
	}

	public function href($controller, $action = null, array $params = null) {
		$config = Application::getConfig();
		$url = '';

		if ($config['domain'] !== null) {
			$url .= "//{$config['domain']}";
		} else if (isset($_SERVER['HTTP_HOST'])) {
			$url .= "//{$_SERVER['HTTP_HOST']}";
		}

		$url .= '/' . $controller;

		if ($action !== null) {
			$url .= '/' . $action;
		}

		if ($params !== null && sizeof($params) > 0) {
			$params = array_values($params);
			foreach ($params as $value) {
				$url .= '/' . $value;
			}
		}

		return $url;
	}

	public function link($path) {
		$config = Application::getConfig();
		$url = '';

		if ($config['domain'] !== null) {
			$url .= "//{$config['domain']}";
		} else if (isset($_SERVER['HTTP_HOST'])) {
			$url .= "//{$_SERVER['HTTP_HOST']}";
		}

		$url .= '/' . $path;
		return $url;
	}

	public function cdn($path) {
		$config = Application::getConfig();
		$url = '';

		if ($config['domain'] !== null) {
			$url .= "//{$config['domain']}";
		} else if (isset($_SERVER['HTTP_HOST'])) {
			$url .= "//{$_SERVER['HTTP_HOST']}";
		}

		$url .= '/' . $path;
		return $url;
	}
}