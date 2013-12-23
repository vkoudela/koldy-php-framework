<?php namespace Bootstrap;
/**
 * @link  http://getbootstrap.com/components/#list-group
 */
class Menu extends HtmlElement {

	protected $items = array();

	protected $activeItem = null;

	public function addLink($url, $text, $activeOn = null) {
		$this->items[] = array(
			'url' => $url,
			'text' => $text,
			'title' => null,
			'activeOn' => $activeOn
		);
		return $this;
	}

	public function addBig($url, $title, $text, $activeOn = null) {
		$this->items[] = array(
			'url' => $url,
			'text' => $text,
			'title' => $title,
			'activeOn' => $activeOn
		);
		return $this;
	}

	public function active($item) {
		if (is_numeric($item)) {
			$this->activeItem = $item;
		} else { // must be string, so it will be compared by URL
			foreach ($this->items as $index => $element) {
				if ($element['activeOn'] !== null) {
					$pos = strpos($_SERVER['REQUEST_URI'], $element['activeOn']);
					if ($pos !== false && $pos >= 0 && $pos <= 2) {
						$this->activeItem = $index;
						return $this;
					}
				}
			}
		}

		return $this;
	}

	public function getHtml() {
		$html = "<div class=\"{$this->getClasses('list-group')}\" id=\"{$this->getId()}\"{$this->getAttributes()}>\n";

		foreach ($this->items as $index => $item) {
			$active = '';
			if ($this->activeItem === null) {
				$target = ($item['activeOn'] === null ? $item['url'] : $item['activeOn']);
				$pos = strpos($_SERVER['REQUEST_URI'], $target);
				if ($pos !== false && $pos >= 0) {
					$active = ' active';
				}
			} else if ($this->activeItem !== null && $index == $this->activeItem) {
				$active = ' active';
			}

			if ($item['title'] === null) {
				$html .= "\t<a href=\"{$item['url']}\" class=\"list-group-item{$active}\">{$item['text']}</a>\n";
			} else {
				$html .= "\t<a href=\"{$item['url']}\" class=\"list-group-item{$active}\">";
				$html .= "\t\t<h4 class=\"list-group-item-heading\">{$item['title']}</h4>\n";
				$html .= "\t\t<p class=\"list-group-item-text\">{$item['text']}</p>\n";
				$html .= "\t</a>\n";
			}
		}

		$html .= "</div>\n";
		return $html;
	}
}