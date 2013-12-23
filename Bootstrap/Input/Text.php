<?php namespace Bootstrap\Input;

class Text extends AbstractElement {

	protected $value = null;

	protected $label = null;

	protected $labelWidth = null;

	protected $type = 'text';
	
	protected $prepend = null;
	
	protected $append = null;

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

	public function type($type) {
		$this->type = $type;
		return $this;
	}

	public function placeholder($placeholder) {
		$this->addAttribute('placeholder', $placeholder);
		return $this;
	}
	
	public function prepend($el) {
		$this->prepend = $el;
		return $this;
	}
	
	public function append($el) {
		$this->append = $el;
		return $this;
	}
	
	protected function getInputHtml() {
		$html = '';
		if ($this->labelWidth === null) {
			if ($this->prepend !== null || $this->append !== null) {
				$html .= "\t<div class=\"input-group\">\n";
			}
				
			if ($this->prepend !== null) {
				$html .= "\t\t<span class=\"input-group-addon\">{$this->prepend}</span>\n";
			}
				
			$html .= "\t\t<input type=\"{$this->type}\" class=\"{$this->getClasses('form-control')}\" id=\"{$this->getId()}\"{$this->getAttributes()}>\n";
				
			if ($this->append !== null) {
				$html .= "\t\t<span class=\"input-group-addon\">{$this->append}</span>\n";
			}
				
			if ($this->prepend !== null || $this->append !== null) {
				$html .= "\t</div>\n";
			}
		} else {
			$html .= "\t<div class=\"col-lg-" . (12 - $this->labelWidth) . "\">\n";
			$html .= "\t\t<input type=\"{$this->type}\" class=\"{$this->getClasses('form-control')}\" id=\"{$this->getId()}\"{$this->getAttributes()}>\n";
			$html .= "\t</div>\n";
		}
		return $html;
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
			$this->addAttribute('value', str_replace("\"", '&quot;', stripslashes($this->value)));
		}

		$html .= $this->getInputHtml();
		
		$html .= "</div>\n";

		return $html;
	}
}