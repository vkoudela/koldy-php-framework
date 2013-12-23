<?php namespace Bootstrap;

class Breadcrumbs extends HtmlElement {
	
	protected $items = array();
	
	protected $selected = null;
	
	public function add($text, $href = null, $target = null) {
		$this->items[] = array(
			'text' => $text,
			'href' => $href,
			'target' => $target
		);
		
		return $this;
	}
	
	public function active($selected) {
		$this->selected = $selected;
		return $this;
	}
	
	public function getHtml() {
		$html = "<ol class=\"breadcrumb\" id=\"{$this->getId()}\">\n";
		
		foreach ($this->items as $index => $item) {
			$active = ($this->selected === $index) ? ' class="active"' : '';
			
			$href = ($item['href'] !== null) ? $item['href'] : '#';
			$target = ($item['target'] !== null) ? " target=\"{$item['target']}\"" : '';
			
			$html .= "\t<li{$active}><a href=\"{$href}\"{$target}>{$item['text']}</a></li>\n";
		}
		
		$html .= "</ol>\n";
		return $html;
	}
	
}