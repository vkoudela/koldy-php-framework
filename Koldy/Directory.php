<?php namespace Koldy;

class Directory {
	
	/**
	 * Get the list of all files from the given folder
	 * @param string $path the directory path to read
	 * @param string $filter [optional] regex for filtering the list
	 * @return array assoc; the key is full path of the file and value is only file name
	 * @example return array('/Users/vkoudela/Sites/site.tld/folder/croatia.png' => 'croatia.png')
	 */
	public static function read($path, $filter = null) {
		if (is_dir($path) && $handle = opendir($path)) {
			$files = array();
			while (false !== ($entry = readdir($handle))) {
				if ($entry !== '.' && $entry !== '..') {
					if ($filter === null || preg_match($filter, $entry)) {
						$files[$path . $entry] = $entry;
					}
				}
			}
			return $files;
		} else {
			return null;
		}
	}
}