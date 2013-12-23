<?php namespace Bootstrap;

class Panel extends HtmlElement {

	/**
	 * @var string
	 */
	protected $title = null;

	/**
	 * @var array<mixed>
	 */
	protected $headerElements = array();

	/**
	 * @var mixed
	 */
	protected $content = null;

	/**
	 * @var mixed
	 */
	protected $footer = null;

	/**
	 * @var string
	 */
	private $color = null;
	
	/**
	 * Is this panel collapsed or not? True for yes, false for no, null for not using
	 * @var bool
	 */
	private $collapsible = null;

	/**
	 * Construct the panel
	 * @param string $title [optional]
	 * @param string $content [optional]
	 * @param string $footer [optional]
	 */
	public function __construct($title = null, $content = null, $footer = null) {
		$this->title = $title;
		$this->content = $content;
		$this->footer = $footer;
	}

	/**
	 * Set the panel title
	 * @param string $title
	 * @return \Bootstrap\Panel
	 */
	public function title($title) {
		$this->title = $title;
		return $this;
	}

	/**
	 * Add the element to the top right side of panel's title
	 * @param mixed $button
	 * @return \Bootstrap\Panel
	 */
	public function addHeaderElement($element) {
		$this->headerElements[] = $element;
		return $this;
	}

	/**
	 * Set the content of panel's body. You can also pass the array of elements.
	 * If you pass only \Bootstrap\Table or \Bootstrap\Table\Remote element, then
	 * table will be rendered as <table> inside of panel, not inside of panel-body.
	 * @param mixed $content
	 * @return \Bootstrap\Panel
	 */
	public function content($content) {
		$this->content = $content;
		return $this;
	}

	/**
	 * Set the panel's footer
	 * @param mixed $footer
	 * @return \Bootstrap\Panel
	 */
	public function footer($footer) {
		$this->footer = $footer;
		return $this;
	}

	/**
	 * Set the bootstrap color.
	 * @param string $color
	 * @return \Bootstrap\Panel
	 */
	public function color($color) {
		if (isset(static::$colors[$color])) {
			$this->addClass('panel-' . static::$colors[$color]);
			$this->color = $color;
		}
		return $this;
	}
	
	/**
	 * Use option for collapsing panel's body and footer.
	 * @param bool|null $collapsed true to init expanded, false to hide, null to not use it
	 * @return \Bootstrap\Panel
	 */
	public function collapsible($collapsed = true) {
		$this->collapsible = $collapsed;
		$this->addHeaderElement(\Bootstrap::link('', '')
			->addClass('x-panel-collapsible')
			->data('up', 'collapse-up')
			->data('down', 'collapse-down')
		);
		$this->data('collapsed', $collapsed ? 'true' : 'false');
		return $this;
	}

	/**
	 * (non-PHPdoc)
	 * @see \Bootstrap\HtmlElement::getHtml()
	 */
	public function getHtml() {
		if ($this->color === null) {
			$this->color('default');
		}
		
		$html = "<div class=\"{$this->getClasses('panel')}\" id=\"{$this->getId()}\"{$this->getAttributes()}>";

		if ($this->title !== null) {
			if (sizeof($this->headerElements) > 0) {
				$elements = '<div class="pull-right x-panel-header-elements">' . implode("\n", $this->headerElements) . '</div>';
			} else {
				$elements = '';
			}
			$html .= "<div class=\"panel-heading\"><span class=\"x-panel-title\">{$this->title}</span>{$elements}</div>";
		}
		
		if ($this->content instanceof \Bootstrap\Table || $this->content instanceof \Bootstrap\Table\Remote) {
			$html .= $this->content;
		} else {
			$style = ($this->collapsible === false ? ' style="display:none;"' : '');
			$html .= '<div class="panel-body"' . $style . '>';
			$html .= is_array($this->content) ? implode("\n\n", $this->content) : $this->content;
			$html .= '<div class="clearfix"></div></div>';
		}

		if ($this->footer !== null) {
			$html .= "<div class=\"panel-footer\">{$this->footer}</div>";
		}

		$html .= '</div>';

		return $html;
	}
}