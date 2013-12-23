<?php namespace Bootstrap\Input;

abstract class AbstractElement extends \Bootstrap\HtmlElement {

	protected $name = null;

	public function name($name) {
		$this->name = $name;
		return $this;
	}

	public function getName() {
		return $this->name;
	}
	
	public function disabled() {
		$this->addAttribute('disabled', 'disabled')->addClass('x-disabled');
		return $this;
	}

}