<?php

require_once '../../Koldy/Html.php';

class HtmlTest extends PHPUnit_Framework_TestCase {
	
	public function testHtml() {
		$this->assertEquals(\Html::apos("That's how I like it."), 'That&apos;s how I like it.');
		$this->assertNotEquals(\Html::apos("That's how I like it."), 'That\'s how I like it.');
	}
	
}