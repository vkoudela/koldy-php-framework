<?php namespace Bootstrap;

class Row extends HtmlElement {

	protected $columns = array();

	public static function start() {
		$self = new self();
		return '<div class="row" id="' . $self->getId() . '">';
	}

	public static function end() {
		return '</div>';
	}

	public function addColumn($size, $element, $offset = null) {
		$this->columns[] = array(
			'size' => $size,
			'element' => $element,
			'offset' => $offset
		);

		return $this;
	}
	
	public function getElements() {
		$data = array();
		foreach ($this->columns as $column) {
			$data[] = $column['element'];
		}
		return $data;
	}

	public function getHtml() {
		$html = "<div class=\"{$this->getClasses('row')}\" id=\"{$this->getId()}\"{$this->getAttributes()}>\n";

		foreach ($this->columns as $column) {
			$size = $column['size'];
			$offset = ($column['offset'] !== null) ? " col-md-offset-{$column['offset']}" : '';
			$element = is_array($column['element']) ? implode("\n", $column['element']) : $column['element'];
			$html .= "\t<div class=\"col-md-{$size}{$offset}\">\n\t\t{$element}\n\t</div>\n";
		}

		$html .= '</div>';

		return $html;
	}
}