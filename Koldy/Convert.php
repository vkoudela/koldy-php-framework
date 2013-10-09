<?php namespace Koldy;

class Convert {

	private static $measure = array('B', 'KB', 'MB', 'GB', 'TB', 'PT');

	/**
	 * Get file's measure
	 * @param double $size
	 * @param int $count
	 * @param int $round
	 * @return string
	 */
	private static function getMeasure($size, $count = 0, $round = null) {
		if ($size >= 1024) {
			return self::getMeasure($size / 1024, ++$count);
		} else {
			return round($size, $round) . ' ' . self::$measure[$count];
		}
	}

	/**
	 * Get bytes size as string
	 * @param int $bytes
	 * @param int $round round to how many decimals
	 * @return string
	 * @example 2048 will return 2 MB
	 */
	public static function bytesToString($bytes, $round = null) {
		return self::getMeasure($bytes, 0, $round);
	}

	public static function shorthandToBytes($string) {
		$original = trim($string);
		$number = (int) $original;

		if ($number === $original) {
			return $number;
		} else {
			$char = strtoupper(substr($original, -1, 1));
			switch($char) {
				case 'K':
					return $number * 1024;
					break;

				case 'M':
					return $number * 1024 * 1024;
					break;

				case 'G':
					return $number * 1024 * 1024 * 1024;
					break;

				case 'T':
					return $number * 1024 * 1024 * 1024 * 1024;
					break;
			}
		}
	}

	/**
	 * Get the alphabet for number<->string conversions. It useful if you want to have much much bigger numbers written
	 * as small string.
	 *
	 * @return array
	 * @author Vlatko Koudela
	 */
	private static function getAlphabet() {
		return array(
			0 => '0', 10 => 'a', 20 => 'k', 30 => 'u', 40 => 'E', 50 => 'O', 60 => 'Y',
			1 => '1', 11 => 'b', 21 => 'l', 31 => 'v', 41 => 'F', 51 => 'P', 61 => 'Z',
			2 => '2', 12 => 'c', 22 => 'm', 32 => 'w', 42 => 'G', 52 => 'Q',
			3 => '3', 13 => 'd', 23 => 'n', 33 => 'x', 43 => 'H', 53 => 'R',
			4 => '4', 14 => 'e', 24 => 'o', 34 => 'y', 44 => 'I', 54 => 'S',
			5 => '5', 15 => 'f', 25 => 'p', 35 => 'z', 45 => 'J', 55 => 'T',
			6 => '6', 16 => 'g', 26 => 'q', 36 => 'A', 46 => 'K', 56 => 'U',
			7 => '7', 17 => 'h', 27 => 'r', 37 => 'B', 47 => 'L', 57 => 'V',
			8 => '8', 18 => 'i', 28 => 's', 38 => 'C', 48 => 'M', 58 => 'W',
			9 => '9', 19 => 'j', 29 => 't', 39 => 'D', 49 => 'N', 59 => 'X'
		);
	}

	/**
	 * Parse number into big alphabet string
	 *
	 * @param int $number
	 * @return string
	 * @author Vlatko Koudela
	 */
	public static function dec2bigAlphabet($number) {
		$alphabet = self::getAlphabet();

		$number = (int) $number;
		$string = (string) $number;
		$decimals = strlen($string);

		if ($number <= 0) {
			return 0;
		}

		$mod = sizeof($alphabet);
		$s = '';

		do {
			$x = floor($number / $mod);
			$left = $number % $mod;
			$char = $alphabet[$left];
			$s = "{$char}{$s}";

			$number = $x;
		} while ($x > 0);

		return $s;
	}

	/**
	 * Parse big alphabet string into decimal number
	 * @param string $alpha
	 * @return int
	 * @author Vlatko Koudela
	 */
	public static function bigAlphabet2dec($alpha) {
		if (strlen($alpha) <= 0) {
			return 0;
		}

		$alphabet = array_flip(self::getAlphabet());
		$mod = sizeof($alphabet);

		$x = 0;
		for ($i = 0, $j = strlen($alpha) -1; $i < strlen($alpha); $i++, $j--) {
			$char = substr($alpha, $j, 1);
			$val = $alphabet[$char];
			$x += ($val * pow($mod, $i));
		}

		return $x;
	}

	/**
	 * convert kilogram (kg) to pounds (lb)
	 * @param float $kilograms
	 * @return float
	 */
	public static function kilogramToPounds($kilograms) {
		return $kilograms * 2.20462262;
	}
	
	/**
	 * convert pounds (lb) to kilograms (kg)
	 * @param float $pounds
	 * @return float
	 */
	public static function poundToKilograms($pounds) {
		return $pounds / 2.20462262;
	}
	
	/**
	 * convert meter (m) to feets (ft)
	 * @param float $meters
	 * @return float
	 */
	public static function meterToFeet($meters) {
		return $meters * 3.2808399;
	}
	
	/**
	 * convert foot (ft) to meters (m)
	 * @param float $feets
	 * @return float
	 */
	public static function footToMeters($feets) {
		return $feets / 3.2808399;
	}
	
	/**
	 * convert centimeters (cm) to inchs (in)
	 * @param float $centimeters
	 * @return float
	 */
	public static function centimeterToInchs($centimeters) {
		return $centimeters * 0.393700787;
	}
	
	/**
	 * convert inchs (in) to centimeters (cm)
	 * @param float $inchs
	 * @return float
	 */
	public static function inchToCentimeters($inchs) {
		return $inchs / 0.393700787;
	}
}