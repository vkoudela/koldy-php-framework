<?php

class Bootstrap {

	public static function hiddenfield($name, $value = null) {
		return new \Bootstrap\Input\Hidden($name, $value);
	}
	
	/**
	 * Create textfield
	 * @param string $name
	 * @param string $value [optional]
	 * @param string $label [optional]
	 * @return \Bootstrap\Input\Text
	 */
	public static function textfield($name, $value = null, $label = null) {
		return new \Bootstrap\Input\Text($name, $value, $label);
	}
	
	/**
	 * Create input field for image
	 * @param string $name
	 * @param string $value
	 * @param string $label
	 * @return \Bootstrap\Input\Image
	 */
	public static function inputImage($name, $value = null, $label = null) {
		return new \Bootstrap\Input\Image($name, $value, $label);
	}
	
	/**
	 * Create response object for input image type
	 * @return \Bootstrap\Response\Input\Image
	 */
	public static function inputImageResponse() {
		return new \Bootstrap\Response\Input\Image();
	}
	
	/**
	 * Create new ImagePreview instance
	 * @return \Bootstrap\ImagePreview
	 */
	public static function imagePreview() {
		return new \Bootstrap\ImagePreview();
	}
	
	/**
	 * Create textarea
	 * @param string $name
	 * @param string $value [optional]
	 * @param string $label [optional]
	 * @return \Bootstrap\Input\Textarea
	 */
	public static function textarea($name, $value = null, $label = null) {
		return new \Bootstrap\Input\Textarea($name, $value, $label);
	}
	
	public static function select($name, $label = null, array $values = array(), $value = null) {
		return new \Bootstrap\Input\Select($name, $label, $values, $value);
	}
	
	public static function select2($name, $label = null, array $values = array(), $value = null) {
		return new \Bootstrap\Input\Select2($name, $label, $values, $value);
	}

	public static function container() {
		return new \Bootstrap\Container();
	}

	public static function row() {
		return new \Bootstrap\Row();
	}

	public static function form($action = null) {
		return new \Bootstrap\Form($action);
	}

	public static function formResponse() {
		return new \Bootstrap\Response\Form();
	}

	public static function button($text) {
		return new \Bootstrap\Button($text);
	}

	public static function buttonRemote($text) {
		return new \Bootstrap\Button\Remote($text);
	}

	public static function buttonRemoteResponse() {
		return new \Bootstrap\Response\ButtonRemote();
	}
	
	public static function link($text, $url) {
		return new \Bootstrap\Link($text, $url);
	}
	
	/**
	 * Get the icon
	 * @param string $icon
	 * @param string $color
	 * @return string
	 */
	public static function icon($icon, $color = null, $size = null) {
		$style = '';
		
		if ($color !== null) {
			switch($color) {
				case 'red': $color = '#d2322d'; break;
				case 'blue': $color = '#428bca'; break;
				case 'green': $color = '#5cb85c'; break;
			}
			$style .= "color: {$color}; ";
		}
		
		if ($size !== null) {
			$style .= "font-size: {$size}px; ";
		}
		
		if (strlen($style) > 0) {
			$style = ' style="' . substr($style, 0, -2) . '"';
		}
		
		return '<span class="glyphicon glyphicon-' . $icon . '"' . $style . '></span>';
	}

	public static function panel($title = null, $content = null, $footer = null) {
		return new \Bootstrap\Panel($title, $content, $footer);
	}

	public static function blockquote($text = null) {
		return new \Bootstrap\Blockquote($text);
	}
	
	public static function p($text = null) {
		return new \Bootstrap\Paragraph($text);
	}
	
	public static function h($size, $text = null) {
		return new \Bootstrap\Heading($size, $text);
	}
	
	public static function alert($element = null) {
		return new \Bootstrap\Alert($element);
	}

	public static function menu() {
		return new \Bootstrap\Menu();
	}

	/**
	 * The Bootstrap Nav component
	 * @return \Bootstrap\Nav
	 * @link http://getbootstrap.com/components/#nav
	 */
	public static function nav() {
		return new \Bootstrap\Nav();
	}
	
	/**
	 * Tabs are made of Nav element!
	 * @return \Bootstrap\Nav
	 * @see Boostrap::nav()
	 */
	public static function tabs() {
		return new \Bootstrap\Nav();
	}

	public static function navbar() {
		return new \Bootstrap\Navbar();
	}
	
	public static function breadcrumbs() {
		return new \Bootstrap\Breadcrumbs();
	}

	public static function image($src = null) {
		return new \Bootstrap\Image($src);
	}
	
	/**
	 * Create the label object
	 * @param string $text
	 * @return \Bootstrap\Label
	 */
	public static function label($text) {
		return new \Bootstrap\Label($text);
	}

	/**
	 * Create the simple table with static content
	 * @return \Bootstrap\Table
	 */
	public static function table() {
		return new \Bootstrap\Table();
	}

	/**
	 * Create the table with remote data source, panel, pagination and other styling
	 * @return \Bootstrap\Table\Remote
	 */
	public static function tableRemote() {
		return new \Bootstrap\Table\Remote();
	}
	
	/**
	 * Get the response object for remote table
	 * @return \Bootstrap\Response\TableRemote
	 */
	public static function tableRemoteResponse(\Bootstrap\Table\Response $remoteTable = null) {
		$response = new \Bootstrap\Response\TableRemote();
		if ($remoteTable !== null) {
			$response->useTable($remoteTable);
		}
		return $response;
	}
}