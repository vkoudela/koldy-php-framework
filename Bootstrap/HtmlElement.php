<?php namespace Bootstrap;

abstract class HtmlElement {

	private static $fieldCounter = 0;

	private $id = null;

	protected $attributes = array();

	protected $cssClasses = array();
	
	protected $localData = array();
	
	public static $colors = array(
		'default' => 'default',
		'primary' => 'primary',
		'blue' => 'primary',
		'success' => 'success',
		'green' => 'success',
		'info' => 'info',
		'lightblue' => 'info',
		'warning' => 'warning',
		'orange' => 'warning',
		'danger' => 'danger',
		'red' => 'danger'
	);

	public function id($id) {
		$this->id = $id;
		return $this;
	}

	public function getId() {
		if ($this->id === null) {
			$this->id = "el_" . (++self::$fieldCounter);
		}

		return $this->id;
	}

	public static function getGlobalId() {
		return "el_" . (++self::$fieldCounter);
	}

	public function addClass($class) {
		$this->cssClasses[] = is_array($class) ? implode(' ', $class) : $class;
		return $this;
	}

	public function removeClass($class) {
		$classes = array_flip($this->cssClasses);
		if (isset($classes[$class])) {
			unset($classes[$class]);
			$this->cssClasses = array_flip($classes);
		}
		return $this;
	}

	protected function getClasses($default = null) {
		if ($default !== null) {
			$default = is_array($default)
				? implode(' ', $default)
				: $default;

			$default .= ' ';
		} else {
			$default = '';
		}

		return $default . implode(' ', $this->cssClasses);
	}

	protected function getCss($defaultCss = null) {
		return " class=\"{$this->getClasses($defaultCss)}\"";
	}

	public function addAttribute($name, $value) {
		$this->attributes[$name] = $value;
		return $this;
	}

	/**
	 * Set the data atribute to this element
	 * @param string $name
	 * @param string|mixed $value
	 * @return \Bootstrap\HtmlElement
	 */
	public function data($name, $value) {
		return $this->addAttribute("data-{$name}", $value);
	}

	protected function getAttributes() {
		$s = '';
		foreach ($this->attributes as $name => $value) {
			$value = str_replace('"', '&quot;', $value);
			$s .= " {$name}=\"{$value}\"";
		}

		return $s;
	}
	
	/**
	 * Set the local data for any kind of information holder
	 * @param string $key
	 * @param mixed $value
	 * @return \Bootstrap\HtmlElement
	 */
	public function setLocalData($key, $value) {
		$this->localData[$key] = $value;
		return $this;
	}
	
	/**
	 * Get the local data previously stored for some reason
	 * @param string $key
	 * @return mixed
	 */
	public function getLocalData($key) {
		return $this->localData[$key];
	}
	
	/**
	 * Is there local data set
	 * @param string $key
	 * @return boolean
	 */
	public function hasLocalData($key) {
		return isset($this->localData[$key]);
	}

	abstract public function getHtml();

	public function __toString() {
		return $this->getHtml();
	}
}