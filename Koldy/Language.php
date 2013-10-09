<?php namespace Koldy;

class Language {
	
	// configure storage
	
	/**
	 * Translate the given key.
	 * 
	 * @param string $key used in database
	 * @param string $defaultText the default text if translation wasn't found in database
	 * @param string $outputConfig optional, default null, otherwise can be javascript or html
	 * @param string $description the description of translation if there is no any translation in DB
	 * @param array $variables the array of key=>value variables that has to be replaces
	 * @return string
	 */
	public function __($key, $defaultText, $outputConfig = null, $description = null, array $variables = null) {
		$app =& Xcms_Application::getInstance();
		$languageId = $app->getCurrentLanguageId();
		$memoryKey = "Translation::{$languageId}::{$key}";
		
		if (!Xcms_Memory::doesExist($memoryKey)) {
			$translatedText = $text;
	
			switch($outputConfig) {
				default:
					$text = stripslashes($text);
					break;
					
				case 'javascript':
				case 'js':
					$text = addslashes($text);
					break;
	
				case 'html':
					$text = stripslashes($text);
					$text = str_replace('&', '&amp;', $text);
					$text = str_replace('&amp;amp;', '&amp;', $text);
					break;
			}
	
			Xcms_Memory::set($memoryKey, $translatedText);
		} else {
			$text = Xcms_Memory::get($memoryKey);
			switch($outputConfig) {
				default:
					$text = stripslashes($text);
					break;
					
				case 'javascript':
				case 'js':
					$text = addslashes($text);
					break;
	
				case 'html':
					$text = stripslashes($text);
					$text = str_replace('&', '&amp;', $text);
					$text = str_replace('&amp;amp;', '&amp;', $text);
					break;
			}
		}
		
		if ($variables !== null && sizeof($variables) > 0) {
			foreach ($variables as $key => $value) {
				$text = str_replace("{{$key}}", $value, $text);
			}
		}
		
		return $text;
	}
	
}