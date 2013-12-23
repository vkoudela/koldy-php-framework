<?php namespace Bootstrap\Input;

class Textarea extends AbstractElement {

	protected $value = null;

	protected $label = null;

	protected $labelWidth = null;

	public function __construct($name, $value = null, $label = null) {
		$this->name = $name;
		$this->value = $value;
		$this->label = $label;
	}

	public function label($label) {
		$this->label = $label;
		return $this;
	}

	public function getLabel() {
		return $this->label;
	}

	/**
	 * The bootstrap width, between 1 and 11
	 * @param  integer $width
	 * @return  \Bootstrap\Input\Text
	 */
	public function labelWidth($width) {
		$this->labelWidth = $width;
		return $this;
	}

	public function getLabelWidth() {
		return $this->labelWidth;
	}

	public function value($value) {
		$this->value = $value;
		return $this;
	}

	public function getValue() {
		return $this->value;
	}

	public function placeholder($placeholder) {
		$this->addAttribute('placeholder', $placeholder);
		return $this;
	}
	
	public function rows($rows) {
		$this->addAttribute('rows', $rows);
		return $this;
	}

	public function getHtml() {
		$html = '<div class="form-group">';
		
		if ($this->value !== null) {
			$this->value = stripslashes($this->value);
		}

		if ($this->label !== null) {
			if ($this->labelWidth === null) {
				$html .= "\n\t<label for=\"{$this->getId()}\" class=\"control-label\">{$this->label}</label>\n";
			} else {
				$html .= "\n\t<label for=\"{$this->getId()}\" class=\"col-lg-{$this->labelWidth} control-label\">{$this->label}</label>\n";
			}
		}

		if ($this->name !== null) {
			$this->addAttribute('name', $this->name);
		}

		if ($this->labelWidth === null) {
			$html .= "\t<textarea class=\"{$this->getClasses('form-control')}\" id=\"{$this->getId()}\"{$this->getAttributes()}>{$this->value}</textarea>\n";
		} else {
			$html .= "\t<div class=\"col-lg-" . (12 - $this->labelWidth) . "\">\n";
			$html .= "\t\t<textarea class=\"{$this->getClasses('form-control')}\" id=\"{$this->getId()}\"{$this->getAttributes()}>{$this->value}</textarea>\n";
			$html .= "\t</div>\n";
		}
		$html .= "</div>\n";

		return $html;
	}
}