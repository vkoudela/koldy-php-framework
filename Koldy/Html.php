<?php namespace Koldy;

/**
 * This is another utility class that is related to HTML stuff.
 *
 */
class Html {


	/**
	 * Convert (') into (& apos ;)
	 *
	 * @param string $string
	 * @return string
	 */
	public static function apos($string) {
		return str_replace("'", '&apos;', $string);
	}


	/**
	 * Parse quotes and return it with html entities
	 *
	 * @param string $string
	 * @return string
	 * @example " -> & quot ;
	 */
	public static function quotes($string) {
		return str_replace("\"", '&quot;', $string);
	}


	/**
	 * Parse "<" and ">" and return it with html entities
	 *
	 * @param string $string
	 * @return string
	 * @example "<" and ">" -> "&lt;" and "&gt;"
	 */
	public static function tags($string) {
		$string = str_replace('<', '&lt;', $string);
		$string = str_replace('>', '&gt;', $string);
		return $string;
	}


	/**
	 * Truncate the long string properly
	 * 
	 * @param string $string
	 * @param int $length default 80 [optional]
	 * @param string $etc suffix string [optional] default '...'
	 * @param bool $breakWords [optional] default false, true to cut the words in text
	 * @param bool $middle [optional] default false
	 * @return string
	 */
	public static function truncate($string, $length = 80, $etc = '...', $breakWords = false, $middle = false) {
		if ($length == 0) {
			return '';
		}

		if (strlen($string) > $length) {
			$length -= min($length, strlen($etc));

			if (!$breakWords && !$middle) {
				$string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length +1));
			}

			if(!$middle) {
				return substr($string, 0, $length) . $etc;
			} else {
				return substr($string, 0, $length /2) . $etc . substr($string, -$length /2);
			}
		} else {
			return $string;
		}
	}


	/**
	 * When having plain text with paragraphs and rows delimited only with new
	 * line and you need to make HTML paragraphs from that omitted with <p>
	 * tag, then use this method.
	 * 
	 * @param string $string text
	 * @example text "Lorem ipsum\n\ndolor sit amet\nperiod." will become "<p>Lorem ipsum</p><p>dolor sit amet<br/>period.</p>"
	 */
	public static function p($string) {
		$string = str_replace("\n\n", '</p><p>', $string);
		$string = str_replace("\n", '<br />', $string);
		return "<p>{$string}</p>";
	}


	/**
	 * Format given string - equivalent to Ext's String.format()
	 * 
	 * @param string $subject
	 * @param mixed $value1
	 * @param mixed $value2...
	 * @return string
	 * @link http://docs.sencha.com/extjs/4.1.3/#!/api/Ext.String-method-format
	 * @example
	 * 
	 * 		Html::formatString('<div class="{0}">{1}</div>', 'my-class', 'text');
	 * 		will return <div class="my-class">text</div>
	 */
	public static function formatString() {
		$args = func_get_args();
		if (sizeof($args) >= 2) {
			$string = $args[0];
			$argsSize = count($args);
			for ($i = 1; $i < $argsSize; $i++) {
				$num = $i -1;
				$string = str_replace("{{$num}}", $args[$i], $string);
			}
			return $string;
		} else {
			return null;
		}
	}


	/**
	 * Detect URLs in text and replace them with HTML A tag
	 * 
	 * @param string $text
	 * @param string $target optional, default _blank
	 * @return string
	 */
	public static function a($text, $target = null) {
		return preg_replace(
			'@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@',
			"<a href=\"\$1\"" . ($target != null ? " target=\"{$target}\"" : '') . ">$1</a>",
			$text
		);
	}

}
