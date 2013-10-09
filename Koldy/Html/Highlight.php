<?php

namespace Koldy\Html;

class Highlight {

	/**
	 * Highlight the PHP code and get results as string with HTML tags
	 *
	 * @param string $phpCode
	 * @return string
	 */
	public static function php($phpCode) {
		return highlight_string($phpCode, true);
	}

	/**
	 * Highlight the javascript code
	 *
	 * @param string $javascriptCode
	 * @return string
	 * @author Created by Benjamin Mayo and Chris Coyier, modified by Vlatko Koudela
	 */
	public static function highlightJavascriptCode($javascriptCode) {
		$data = $javascriptCode;

		$options = false;
		$c_string = '#DD0000';
		$c_comment = '#FF8000';
		$c_keyword = '#007700';
		$c_default = '#0000BB';
		$c_html = '#0000BB';
		$flush_on_closing_brace = false;

		if (is_array($options)) { // check for alternative usage
			extract($options, EXTR_OVERWRITE); // extract the variables from the array if so
		} else {
			$advanced_optimizations = $options; // otherwise carry on as normal
		}
//		@ini_set('highlight.string', $c_string); // Set each colour for each part of the syntax
//		@ini_set('highlight.comment', $c_comment); // Suppression has to happen as some hosts deny access to ini_set and there is no way of detecting this
//		@ini_set('highlight.keyword', $c_keyword);
//		@ini_set('highlight.default', $c_default);
//		@ini_set('highlight.html', $c_html);

		if ($advanced_optimizations) { // if the function has been allowed to perform potential (although unlikely) code-destroying or erroneous edits
			$data = preg_replace('/([$a-zA-z09]+) = \((.+)\) \? ([^]*)([ ]+)?\:([ ]+)?([^=\;]*)/', 'if ($2) {'."\n".' $1 = $3; }'."\n".'else {'."\n".' $1 = $5; '."\n".'}', $data); // expand all BASIC ternary statements into full if/elses
		}

		$data = str_replace(array(') { ', ' }', ";", "\r\n"), array(") {\n", "\n}", ";\n", "\n"), $data); // Newlinefy all braces and change Windows linebreaks to Linux (much nicer!)
		$data = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $data); // Regex identifies all extra empty lines produced by the str_replace above. It is quicker to do it like this than deal with a more complicated regular expression above.
		$data = str_replace("<?php", "<script>", highlight_string("<?php \n" . $data . "\n?>", true));

		$data = explode("\n", str_replace(array("<br />"), array("\n"),$data));

		# experimental tab level highlighting
		$tab = 0;
		$output = '';

		foreach ($data as $line) {
			$lineecho = $line;
			if (substr_count($line, "\t") != $tab) {
				$lineecho = str_replace("\t", "", trim($lineecho));
				$lineecho = str_repeat("\t", $tab) . $lineecho;
			}
			$tab = $tab + substr_count($line, "{") - substr_count($line, "}");
			if ($flush_on_closing_brace && trim($line) == "}") {
				$output .= '}';
			} else {
				$output .= str_replace(array("{}", "[]"), array("<span style='color:".$c_string."!important;'>{}</span>", "<span style='color:".$c_string." !important;'>[]</span>"), $lineecho."\n"); // Main JS specific thing that is not matched in the PHP parser
			}
		}

		return substr($output, 87, -58); // Add nice and friendly <script> tags around highlighted text
	}
}