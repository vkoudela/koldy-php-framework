<?php namespace Bootstrap;

class Link extends HtmlElement {

	protected $text = null;

	protected $url = null;

	protected $target = null;

	private $sizes = array(
		'large' => 'lg',
		'lg' => 'lg',
		'small' => 'sm',
		'sm' => 'sm',
		'extra-small' => 'xs',
		'xs' => 'xs'
	);

	public function __construct($text, $url) {
		$this->text = $text;
		$this->url = $url;
		$this->addClass('btn')->addClass('btn-link');
	}

	public function text($text) {
		$this->text = $text;
		return $this;
	}

	public function color($color) {
		if (isset(static::$colors[$color])) {
			$this->addClass('btn-' . static::$colors[$color]);
		}
		return $this;
	}

	public function size($size) {
		if (isset($this->sizes[$size])) {
			return $this->addClass("btn-{$this->sizes[$size]}");
		}
		return $this;
	}

	public function asButton() {
		return $this->removeClass('btn-link');
	}

	public function target($target) {
		$this->target = $target;
		return $this;
	}
	
	public function promptText($promptText) {
		$promptText = str_replace('\'', '\\\'', $promptText);
		$promptText = str_replace('"', '&quot;', $promptText);
		$this->addAttribute('onclick', "return confirm('{$promptText}')");
		return $this;
	}

	public function disabled() {
		return $this->addAttribute('disabled', 'disabled');
	}

	public function getHtml() {
		$html = "<a href=\"{$this->url}\" id=\"{$this->getId()}\" ";
		if ($this->target !== null) {
			$html .= "target=\"{$this->target}\" ";
		}
		$html .= "class=\"{$this->getClasses()}\"{$this->getAttributes()}>";
		$html .= "{$this->text}</a>";

		return $html;
	}
}