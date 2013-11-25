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
	
	/**
	 * Create the target directory
	 * @param string $path
	 * @param octal $chmod default 0644
	 * @return bool was it successfull
	 * @example $chmod 0777, 0755, 0700
	 */
	public static function mkdir($path, $chmod = 0644) {
		if (is_dir($path)) {
			return true;
		}
	
		$paths = explode(DIRECTORY_SEPARATOR, $path);
		if (sizeof($paths)) {
			array_shift($paths);
			$path = DIRECTORY_SEPARATOR;
				
			foreach($paths as $key => $dir) {
				$path .= $dir . DIRECTORY_SEPARATOR;
				if (!is_dir($path)) {
					if (!@mkdir($path, $chmod)) {
						return false;
					}
				}
			}
				
			return true;
		}
		return false;
	}
	
	/**
	 * Remove directory and content inside recursively
	 * @param string $directory
	 * @return boolean
	 */
	public static function rmdirRecursive($directory) {
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
			$path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
		}
		rmdir($dirPath);
		return true;
	}
}