<?php namespace Bootstrap;

class Navbar extends HtmlElement {
	
	protected $title = null;
	
	protected $active = null;
	
	protected $links = array();
	
	public function title($title) {
		$this->title = $title;
		return $this;
	}
	
	public function active($number) {
		$this->active = $number;
		return $this;
	}
	
	public function addLink($href, $text, $target = null) {
		$this->links[] = array(
			'text' => $text,
			'href' => $href,
			'target' => $target
		);
		
		return $this;
	}
	
	/*
<nav class="navbar navbar-default" role="navigation">
  <div class="navbar-header">
    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex2-collapse">
      <span class="sr-only">Toggle navigation</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </button>
    <a class="navbar-brand" href="#">Brand</a>
  </div>
	
  <div class="collapse navbar-collapse navbar-ex2-collapse">
    <ul class="nav navbar-nav">
      <li class="active"><a href="#">Link</a></li>
      <li><a href="#">Link</a></li>
      <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Dropdown <b class="caret"></b></a>
        <ul class="dropdown-menu">
          <li><a href="#">Action</a></li>
          <li><a href="#">Another action</a></li>
          <li><a href="#">Something else here</a></li>
          <li><a href="#">Separated link</a></li>
          <li><a href="#">One more separated link</a></li>
        </ul>
      </li>
    </ul>
  </div><!-- /.navbar-collapse -->
</nav>
*/
	
	public function getHtml() {
		$html = "\n";
		$html .= '<nav class="navbar navbar-default" role="navigation">';
			$html .= '<div class="navbar-header">';
				$html .= '<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-' . $this->getId() . '">';
					$html .= '<span class="sr-only">Toggle navigation</span>';
					$html .= '<span class="icon-bar"></span>';
					$html .= '<span class="icon-bar"></span>';
					$html .= '<span class="icon-bar"></span>';
				$html .= '</button>';
				if ($this->title !== null) {
					$html .= '<a class="navbar-brand" href="javascript:void(0);">' . $this->title . '</a>';
				}
			$html .= '</div>';
			
			$html .= "\n";
			
			$html .= '<div class="collapse navbar-collapse navbar-' . $this->getId() . '">';
				$html .= '<ul class="nav navbar-nav">';
				foreach ($this->links as $index => $link) {
					$selected = ($this->active !== null && $this->active === $index) ? ' class="active"' : '';
					$target = ($link['target'] !== null) ? " target=\"{$link['target']}\"" : '';
					$html .= "<li{$selected}><a href=\"{$link['href']}\"{$target}>{$link['text']}</a></li>";
				}
				$html .= '</ul>';
			$html .= '</div>';
		$html .= "</nav>\n\n";
		
		return $html;
	}
}