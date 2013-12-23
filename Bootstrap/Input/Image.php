<?php namespace Bootstrap\Input;

use Koldy\Json;
use Koldy\Application;

class Image extends Text {
	
	protected $type = 'file';
	
	protected $params = array();
	
	protected $url = null;
	
	protected $multiple = false;
	
	protected $allowExtensions = array('gif', 'jpg', 'jpeg', 'png');
	
	protected $previewElements = array();
	
	public function __construct($name, $value = null, $label = null) {
		parent::__construct("_{$name}", $value, $label);
		$this->addClass('x-image');
	}
	
	/**
	 * Allow given extensions
	 * @param array $extensions
	 * @return \Bootstrap\Input\Image
	 */
	public function allowExtensions(array $extensions) {
		$this->allowExtensions = $extensions;
		return $this;
	}
	
	/**
	 * This object can handle multiple files
	 * @return \Bootstrap\Input\Image
	 */
	public function multiple() {
		$this->multiple = true;
		return $this;
	}
	
	/**
	 * Can this object handle multiple files
	 * @return boolean
	 */
	public function hasMultiple() {
		return $this->multiple;
	}
	
	/**
	 * Add preview element
	 * @param mixed $element
	 * @return \Bootstrap\Input\Image
	 */
	public function previewAdd($element) {
		$this->previewElements[] = $element;
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Bootstrap\Input\Text::getInputHtml()
	 */
	protected function getInputHtml() {
		$html = '';
		
		$preview = implode("\n", $this->previewElements);
		if ($this->labelWidth === null) {
			if ($this->prepend !== null || $this->append !== null) {
				$html .= "\t<div class=\"input-group\">\n";
			}
	
			if ($this->prepend !== null) {
				$html .= "\t\t<span class=\"input-group-addon\">{$this->prepend}</span>\n";
			}
	
			$html .= "\t\t<input type=\"{$this->type}\" class=\"{$this->getClasses('form-control')}\" id=\"{$this->getId()}\"{$this->getAttributes()}>\n";
			$html .= "\t\t<div class=\"x-input-image-preview\">{$preview}</div>";
	
			if ($this->append !== null) {
				$html .= "\t\t<span class=\"input-group-addon\">{$this->append}</span>\n";
			}
	
			if ($this->prepend !== null || $this->append !== null) {
				$html .= "\t</div>\n";
			}
		} else {
			$html .= "\t<div class=\"col-lg-" . (12 - $this->labelWidth) . "\">\n";
			$html .= "\t\t<input type=\"{$this->type}\" class=\"{$this->getClasses('form-control')}\" id=\"{$this->getId()}\"{$this->getAttributes()}>\n";
			$html .= "\t\t<div class=\"x-input-image-preview\">{$preview}</div>";
			$html .= "\t</div>\n";
		}
		return $html;
	}
	
	/**
	 * Set the URL where image will be submitted
	 * @param string $url
	 * @return \Bootstrap\Input\Image
	 */
	public function url($url) {
		$this->url = $url;
		return $this;
	}
	
	/**
	 * Get the URL
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Bootstrap\Input\Text::getHtml()
	 */
	public function getHtml() {
		$this->data('url', $this->url);
		$this->data('extensions', base64_encode(Json::encode($this->allowExtensions)));
		
		if (sizeof($this->params) > 0) {
			$this->data('params', base64_encode(Json::encode($this->params)));
		}
		
		$html = parent::getHtml();
		$html .= "<iframe src=\"about:blank\" width=\"1\" height=\"1\" id=\"iframe_{$this->getId()}\" frameborder=\"0\" style=\"position:absolute;top:0;left:0;\"></iframe>";
		
		return $html;
	}
}