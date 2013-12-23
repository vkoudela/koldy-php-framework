<?php namespace Bootstrap\Input;
/**
 * The Select2 Bootstrap component.
 * @author vkoudela
 * @link http://ivaynberg.github.io/select2/
 */
class Select2 extends Select {

	/**
	 * Construct the object for select element/dropdown/combobox - call it whatever
	 * @param string $name
	 * @param string $label [optional]
	 * @param array $values [optional] assoc array
	 * @param mixed $value [optional]
	 */
	public function __construct($name, $label = null, array $values = array(), $value = null) {
		parent::__construct($name, $label, $values, $value);
		
		\Xcms\View::css('select2-basic', '3rd/select2-3.4.3/select2.css');
		\Xcms\View::css('select2-bootstrap3', '3rd/select2-bootstrap3.css');
		\Xcms\View::javascript('select2', '3rd/select2-3.4.3/select2.min.js');
		\Xcms\View::javascript('select2-bind', '3rd/select2-bind.js');
		
		$this->addClass('select2');
	}

}