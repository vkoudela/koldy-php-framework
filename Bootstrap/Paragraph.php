<?php namespace Bootstrap;

class Paragraph extends HtmlElement {
	
	private $color = null;
	
	protected $text = null;
	
	public function __construct($text = null) {
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
		if (is_array($this->text)) {
			foreach ($this->text as $key => $text) {
				$html = "<p id=\"{$this->getId()}-{$key}\"{$this->getCss()}{$this->getAttributes()}>\n";
				$html .= "\t{$text}\n";
				$html .= "</p>\n";
			}
		} else {
			$html = "<p id=\"{$this->getId()}\"{$this->getCss()}{$this->getAttributes()}>\n";
			$html .= "\t{$this->text}\n";
			$html .= "</p>\n";
		}
		
		return $html;
	}
}