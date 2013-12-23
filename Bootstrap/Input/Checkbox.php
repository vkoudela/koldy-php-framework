<?php namespace Bootstrap\Input;
// TODO: TEST!!
class Checkbox extends AbstractElement {

	protected $value = null;
	
	protected $checked = false;

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

	public function checked($checked = true) {
		$this->checked = $checked;
		return $this;
	}

	public function getHtml() {
		$html = '<div class="form-group">';

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

		if ($this->value !== null) {
			$this->addAttribute('value', $this->value);
		}

		if ($this->labelWidth === null) {
			$html .= '\t<div class="checkbox">';
			$html .= "\n\t\t<input type=\"checkbox\"{$this->getCss()} id=\"{$this->getId()}\"{$this->getAttributes()}>\n";
			$html .= "\t</div>\n";
		} else {
			$html .= "\t<div class=\"col-lg-" . (12 - $this->labelWidth) . "\">\n";
			$html .= "\t<div class=\"checkbox\">\n";
			$html .= "\t\t<input type=\"checkbox\"{$this->getCss()} id=\"{$this->getId()}\"{$this->getAttributes()}>\n";
			$html .= "\t</div>\n\t</div>\n";
		}
		$html .= "</div>\n";

		return $html;
	}
}