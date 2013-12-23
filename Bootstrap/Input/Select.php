<?php namespace Bootstrap\Input;

class Select extends AbstractElement {

	protected $value = null;
	
	protected $values = array();
	
	private $keyField = null;
	
	private $valueField = null;

	protected $label = null;

	protected $labelWidth = null;

	/**
	 * Construct the object for select element/dropdown/combobox - call it whatever
	 * @param string $name
	 * @param string $label [optional]
	 * @param array $values [optional] assoc array
	 * @param mixed $value [optional]
	 */
	public function __construct($name, $label = null, array $values = array(), $value = null) {
		$this->name = $name;
		$this->value = $value;
		$this->values = $values;
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
	 * @return  \Bootstrap\Input\Select
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

	/**
	 * Set the array of values. If you don't pass assoc array, then define which
	 * element is key and which one is value.
	 * @param array $values
	 * @param string $keyField
	 * @param string $valueField
	 * @return \Bootstrap\Input\Select
	 */
	public function values(array $values, $keyField = null, $valueField = null) {
		$this->values = $values;
		$this->keyField = $keyField;
		$this->valueField = $valueField;
		
		return $this;
	}
	
	/**
	 * Make it multiple choice
	 * @return \Bootstrap\Input\Select
	 */
	public function multiple() {
		return $this->addAttribute('multiple', 'multiple');
	}
	
	public function getValues() {
		return $this->values;
	}
	
	private function getOptions() {
		$s = '';
		if ($this->keyField !== null && $this->valueField !== null) {
			$keyField = $this->keyField;
			$valueField = $this->valueField;
			
			foreach ($this->values as $option) {
				if (is_object($option)) {
					$key = $option->$keyField;
					$value = $option->$valueField;
				} else {
					$key = $option[$keyField];
					$value = $option[$valueField];
				}
				
				$selected = ($this->value !== null && $this->value == $value) ? ' selected="selected"' : '';
				$s .= "\t\t<option value=\"{$key}\"{$selected}>{$value}</option>\n";
			} 
		} else {
			foreach ($this->values as $key => $value) {
				$selected = ($this->value !== null && $this->value == $key) ? ' selected="selected"' : '';
				$s .= "\t\t<option value=\"{$key}\"{$selected}>{$value}</option>\n";
			}
		}
		
		return $s;
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
			$html .= "\t<select name=\"{$this->name}\" class=\"{$this->getClasses('form-control')}\" id=\"{$this->getId()}\"{$this->getAttributes()}>\n";
			$html .= $this->getOptions();
			$html .= "\t</select>\n";
		} else {
			$html .= "\t<div class=\"col-lg-" . (12 - $this->labelWidth) . "\">\n";
				$html .= "\t\t<select name=\"{$this->name}\" class=\"{$this->getClasses('form-control')}\" id=\"{$this->getId()}\"{$this->getAttributes()}>\n";
				$html .= $this->getOptions();
				$html .= "\t\t</select>\n";
			$html .= "\t</div>\n";
		}
		$html .= "</div>\n";

		return $html;
	}
}