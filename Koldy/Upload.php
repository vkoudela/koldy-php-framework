<?php namespace Koldy;

use Koldy\Convert;

/**
 * Get some informations about upload capabilities.
 * @author vkoudela
 *
 */
class Upload {

	/**
	 * Get the maximum size of file that can be uploaded
	 * @return int/string
	 * @example return 32M
	 */
	public static function getMaxFilesize() {
		return Convert::shorthandToBytes(ini_get('upload_max_filesize'));
	}
}