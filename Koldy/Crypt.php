<?php namespace Koldy;

class Crypt {
	
	const CYPHER = MCRYPT_RIJNDAEL_256;
	const MODE   = MCRYPT_MODE_CBC;

	/**
	 * Encrypt the given string
	 * @param string $plaintext
	 * @param string $key
	 * @return string
	 */
	public static function encrypt($plaintext, $key) {
		$td = mcrypt_module_open(self::CYPHER, '', self::MODE, '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $key, $iv);
		$crypttext = mcrypt_generic($td, $plaintext);
		mcrypt_generic_deinit($td);
		return base64_encode($iv . $crypttext);
    }

    /**
     * Descrypt the given string
     * @param string $crypttext
     * @param string $key
     * @return string
     */
	public static function decrypt($crypttext, $key) {
		$crypttext = base64_decode($crypttext);
		$plaintext = '';
		$td        = mcrypt_module_open(self::CYPHER, '', self::MODE, '');
		$ivsize    = mcrypt_enc_get_iv_size($td);
		$iv        = substr($crypttext, 0, $ivsize);
		$crypttext = substr($crypttext, $ivsize);
		if ($iv) {
			mcrypt_generic_init($td, $key, $iv);
			$plaintext = mdecrypt_generic($td, $crypttext);
		}
		return trim($plaintext);
	}

}