<?php namespace Bootstrap;

class Heading extends HtmlElement {
	
	private $color = null;
	
	protected $text = null;
	
	private $size = null;
	
	public function __construct($size, $text = null) {
		$this->size = $size;
		$this->text = $text;
	}
	
	public function text($text) {
		$this->text = $text;
		return $this;
	}
	
	public function color($color) {
		if (isset(static::$colors[$color])) {
			$this->addClass('text-' . static::$colors[$color]);
			$this->color = $color;
		}
		return $this;
	}
	
	public function getHtml() {
		$html = "<h{$this->size} id=\"{$this->getId()}\"{$this->getCss()}{$this->getAttributes()}>\n";
		$html .= "\t{$this->text}\n";
		$html .= "</h{$this->size}>\n";
		
		return $html;
	}
}