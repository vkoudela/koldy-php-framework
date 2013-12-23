<?php namespace Bootstrap;

class Form extends HtmlElement {

	protected $action = null;

	protected $elements = array();

	protected $buttons = array();

	protected $helpText = null;

	private $buttonsPosition = 'bottom';

	/**
	 * The vertical version mustn't have this property set. If you set this,
	 * then horizontal version will be generated with the label width set here.
	 * @param  integer $labelWidth The number between 1 and 11. The optimal is 2.
	 */
	private $labelWidth = null;

	protected $method = 'POST';

	public function __construct($action = null) {
		$this->action = ($action === null ? $_SERVER['REQUEST_URI'] : $action);
	}

	public function add($element) {
		if ($element instanceof Input\AbstractElement) {
			$this->elements[$element->getName()] = $element;
		} else if (is_object($element)) {
			$this->elements[$element->getId()] = $element;
		} else {
			$this->elements[HtmlElement::getGlobalId()] = $element;
		}
		return $this;
	}

	public function addButton($button) {
		$this->buttons[] = $button;
		return $this;
	}

	public function method($method) {
		$this->method = $method;
		return $this;
	}

	public function horizontal($labelWidth = 2) {
		$this->labelWidth = $labelWidth;
		return $this;
	}

	public function helpText($helpText) {
		$this->helpText = $helpText;
		return $this;
	}

	public function buttonsOn($position) {
		switch(strtolower($position)) {
			case 'top': $this->buttonsPosition = 'top'; break;
			case 'bottom': $this->buttonsPosition = 'bottom'; break;
		}
		return $this;
	}

	public function setValues(array $values) {
		foreach ($values as $field => $value) {
			if (isset($this->elements[$field]) && is_object($this->elements[$field]) && method_exists($this->elements[$field], 'value')) {
				$this->elements[$field]->value($value);
			}
		}
		return $this;
	}

	public function getValues() {
		$values = array();
		foreach ($this->elements as $key => $element) {
			if (is_object($element) && method_exists($element, 'getValue')) {
				$values[$key] = $element->getValue();
			}
		}
		return $values;
	}

	public function getField($name) {
		return isset($this->elements[$name])
			? $this->elements[$name]
			: null;
	}

	public function addSubmit($buttonText = null, $name = null) {
		if ($buttonText === null) {
			$buttonText = __('button.submit', 'Submit');
		}
		
		$button = \Bootstrap::button($buttonText)
			->type('submit')
			->color('primary');
		
		if ($name !== null) {
			$button->name($name);
		}
		
		return $this->addButton($button);
	}

	private function renderButtons($position) {
		$html = '';
		if ($this->buttonsPosition == $position && sizeof($this->buttons) > 0) {
			$html .= "\n<div class=\"form-group\">\n";

			if ($this->labelWidth !== null) {
				$html .= "\t<div class=\"col-lg-offset-{$this->labelWidth} col-lg-" . (12 - $this->labelWidth) . "\">\n";
			}

			foreach ($this->buttons as $element) {
				$html .= "\t" . $element . "\n";
			}

			$html .= "\t<span class=\"x-status-icon\"></span>\n";

			if ($this->labelWidth !== null) {
				$html .= "</div>\n";
			}

			$html .= "</div>\n";
		}
		return $html;
	}

	public function getHtml() {
		$this->addClass('x-form');
		$html = "<form action=\"{$this->action}\" method=\"{$this->method}\" id=\"{$this->getId()}\" role=\"form\"{$this->getCss()}{$this->getAttributes()}>\n";

		$html .= $this->renderButtons('top');

		foreach ($this->elements as $element) {
			if ($this->labelWidth !== null && is_object($element) && method_exists($element, 'getLabelWidth') && method_exists($element, 'labelWidth') && $element->getLabelWidth() === null) {
				$element->labelWidth($this->labelWidth);
			}
			$html .= $element;
		}

		$html .= $this->renderButtons('bottom');

		if ($this->labelWidth === null) {
			$html .= "\t<span class=\"help-block\">{$this->helpText}</span>\n";
		} else {
			$html .= "<div class=\"form-group\">\n";
			$html .= "\t<div class=\"col-lg-{$this->labelWidth}\"></div>\n";
			$html .= "\t<div class=\"col-lg-" . (12 - $this->labelWidth) . " x-help-block\">\n";
			$html .= "\t\t<span class=\"help-block\">{$this->helpText}</span>\n";
			$html .= "\t</div>\n";
			$html .= "</div>\n";
		}
		$html .= '</form>';
		return $html;
	}

}