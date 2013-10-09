<?php namespace Koldy;

class View extends Response {

	private $view = null;
	
	public static function create($view) {
		$self = new static();
		return $self->setView($view);
	}

	public function setView($view) {
		$this->view = $view;
		return $this;
	}
	
	public function with($key, $value) {
		if ($key == 'view') {
			throw new \Exception('You can not use key name that exists as reserved property in View class');
		}
		$this->$key = $value;
		return $this;
	}

	public function params(array $with) {
		foreach ($with as $key => $value) {
			if ($key == 'view') {
				Application::throwError(500, 'You can not use key name that exists as reserved property in View class');
			}
			$this->$key = $value;
		}

		return $this;
	}

	protected function href($controller, $action = null, array $params = null) {
		return Application::route()->href($controller, $action, $params);
	}

	protected function link($path) {
		return Application::route()->link($path);
	}

	protected function cdn($path) {
		return Application::route()->cdn($path);
	}

	protected function getViewPath($view) {
		$pos = strpos($view, ':');
		if ($pos === false) {
			return Application::getApplicationPath() . 'views' . DS . str_replace('.', DS, $view) . '.phtml';
		} else {
			return Application::getApplicationPath() . 'modules'
				. DS . substr($view, 0, $pos)
				. DS . 'views'
				. DS . str_replace('.', DS, substr($view, $pos +1)) . '.phtml';
		}
	}

	public function render($view) {
		$path = $this->getViewPath($view);

		if (!file_exists($path)) {
			Log::error("Can not find view on path={$path}");
			Application::throwError(500, "View ({$view}) not found");
		}

		ob_start();
		include($path);
		return ob_get_clean();
	}

	public function flush() {
		$path = $this->getViewPath($this->view);

		if (!file_exists($path)) {
			if (Application::inDevelopment()) {
				Log::debug("Can not find view on path={$path}");
			}
			Application::throwError(500, "View ({$this->view}) not found");
		}

		header('Connection: close');
		ob_start();

			include($path);
			$size = ob_get_length();
			header("Content-Length: {$size}");

		ob_end_flush();
		flush();

		if ($this->workAfterResponse !== null) {
			$fn = $this->workAfterResponse;
			$fn();
		}
	}

}