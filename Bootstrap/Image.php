<?php namespace Bootstrap;

class Image extends HtmlElement {
	
	protected $type = 'rounded';
	
	public function __construct($src = null) {
		if ($src !== null) {
			$this->src($src);
		}
	}
	
	public function circle() {
		$this->type = 'circle';
		return $this;
	}
	
	public function thumbnail() {
		$this->type = 'thumbnail';
		return $this;
	}
	
	public function src($path) {
		$this->addAttribute('src', $path);
		return $this;
	}
	
	public function alt($text) {
		$this->addAttribute('alt', $text);
		return $this;
	}
	
	public function responsive() {
		$this->addClass('img-responsive');
		return $this;
	}
	
	public function getHtml() {
		$this->addClass('img-' . $this->type);
		
		$html = "
		<img{$this->getCss()}{$this->getAttributes()}>
		";
		
		return $html;
	}
}