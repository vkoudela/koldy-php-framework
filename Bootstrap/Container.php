<?php namespace Bootstrap;

class Container extends HtmlElement {

	protected $elements = array();

	public static function start() {
		$self = new self();
		return '<div class="container" id="' . $self->getId() . '">';
	}

	public static function end() {
		return '</div>';
	}

	public function add($element) {
		$this->elements[] = $element;
		return $this;
	}

	public function getHtml() {
		$html = "<div class=\"{$this->getClasses('container')}\" id=\"{$this->getId()}\"{$this->getAttributes()}>\n";

		foreach ($this->elements as $element) {
			$html .= $element;
		}

		$html .= '</div>';

		return $html;
	}
}