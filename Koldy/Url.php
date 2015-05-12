<?php namespace Koldy;

/**
 * This is another utility class that know how to handle URL. While developing
 * your site, you'll probably need to generate URL and detect if you're
 * currently on some given URL. This class provides all of it.
 * 
 * This class relies on your route instance so you'll probably need to check
 * the docs of your routes to understand the methods below.
 */
class Url {


	/**
	 * Get the variable from request. This depends about the route you're using.
	 *
	 * @param string|int $whatVar
	 * @param string|int $default
	 *
	 * @return string|int
	 */
	public static function getVar($whatVar, $default = null) {
		return Application::route()->getVar($whatVar, $default);
	}


	/**
	 * Get the controller name in the exact format as its being used in URL
	 *
	 * @return string
	 */
	public static function controller() {
		return Application::route()->getControllerUrl();
	}


	/**
	 * Is given controller the current working controller?
	 * 
	 * @param string $controller the url format (e.g. "index"), not the class name such as "IndexController"
	 * @return boolean
	 */
	public static function isController($controller) {
		return ($controller == Application::route()->getControllerUrl());
	}


	/**
	 * Get the current action in the exact format as it is being used in URL
	 *
	 * @return string
	 */
	public static function action() {
		return Application::route()->getActionUrl();
	}


	/**
	 * Is given action the current working action?
	 * 
	 * @param string $action the url format (e.g. "index"), not the method name such as "indexAction"
	 * @return boolean
	 */
	public static function isAction($action) {
		return ($action == Application::route()->getActionUrl());
	}


	/**
	 * Are given controller and action current working controller and action?
	 * 
	 * @param string $controller in the url format
	 * @param string $action in the url format
	 * @return boolean
	 */
	public static function is($controller, $action) {
		return ($controller == Application::route()->getControllerUrl() && $action == Application::route()->getActionUrl());
	}


	/**
	 * Is this the matching module, controller and action?
	 * 
	 * @param string $module
	 * @param string $controller
	 * @param string $action
	 * @return boolean
	 */
	public static function isModule($module, $controller = null, $action = null) {
		$route = Application::route();
		if ($module === $route->getModuleUrl()) {
			if ($controller === null) {
				return true;
			} else {
				if ($controller === $route->getControllerUrl()) {
					// now we have matched module and controller
					if ($action === null) {
						return true;
					} else {
						return ($action === $route->getActionUrl());
					}
				} else {
					return false;
				}
			}
		} else {
			return false;
		}
	}


	/**
	 * Get the complete current URL with domain and protocol and request URI
	 * 
	 * @return null|string will return NULL in CLI environment
	 */
	public static function current() {
		if (!isset($_SERVER['REQUEST_URI'])) {
			return null;
		}

		return Application::getConfig('application', 'site_url') . $_SERVER['REQUEST_URI'];
	}


	/**
	 * Generate the link suitable for <a> tags. Generating links depends about the routing class you're using.
	 *
	 * @param string $controller
	 * @param string $action
	 * @param array $params
	 *
	 * @return string
	 */
	public static function href($controller = null, $action = null, array $params = null) {
		return Application::route()->href($controller, $action, $params);
	}


	/**
	 * Generate the link suitable for <a> tags. Generating links depends about the routing class you're using.
	 *
	 * @param string $controller
	 * @param string $action
	 * @param array $params
	 *
	 * @return string
	 */
	public static function siteHref($site, $controller = null, $action = null, array $params = null) {
		return Application::route()->siteHref($site, $controller, $action, $params);
	}


	/**
	 * Generate the link to home page
	 * 
	 * @return string
	 */
	public static function home() {
		return static::href();
	}


	/**
	 * Generate the link to static asset on the same host where application is. This method is using link() method in
	 * routing class, so be careful because it might be overridden in your case.
	 *
	 * @param string $path
	 * @param string $server
	 *
	 * @return string
	 */
	public static function asset($path, $server = null) {
		return Application::route()->asset($path, $server);
	}

	/**
	 * Catch
	 *
	 * @param string $name
	 * @param array $args
	 *
	 * @return string
	 */
	public static function __callStatic($name, $args) {
		return static::asset($args[0], $name);
	}


	/**
	 * This method returns string prepared to be used in URLs as slugs
	 *
	 * @return string
	 * @example "Your new - title" will become "your-new-title"
	 * @example "Vozač napravio 1500€ štete" will become "vozac-napravio-1500eur-stete"
	 */
	public static function slug($string) {
		if ($string === null) {
			return null;
		}

		$s = strip_tags(trim($string));

		$table = array(
			'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
			'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
			'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
			'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
			'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
			'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
			'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
			'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
		);

		$s = strtr($s, $table);

		$rpl = array(
			'/(,|;|\!|\?|:|&|\+|\=|-|\'|\/|\*|\t|\n|\$|\%|#|\^|\(|\)|\[|\]|\{|\}|\.)/' => '-',

			'/≈°/' => 's',
			'/ƒë/' => 'd',
			'/ƒç/' => 'c',
			'/ƒá/' => 'c',
			'/≈æ/' => 'z',
			'/≈†/' => 's',
			'/ƒê/' => 'd',
			'/ƒå/' => 'c',
			'/ƒÜ/' => 'c',
			'/≈Ω/' => 'z',

			'/&353;/' => 's',
			'/&273;/' => 'd',
			'/&269;/' => 'c',
			'/&263;/' => 'c',
			'/&382;/' => 'z',
			'/&351;/' => 'S',
			'/&272;/' => 'D',
			'/&268;/' => 'C',
			'/&262;/' => 'C',
			'/&381;/' => 'Z'
		);

		$s = preg_replace(array_keys($rpl), array_values($rpl), $s);

		$s = str_replace('\\','', $s);
		$s = str_replace('¬Æ','-', $s);
		$s = str_replace('‚Äì','-', $s);
		$s = str_replace('¬©','-', $s);
		$s = str_replace('√ü','', $s);
		$s = str_replace('’','', $s);

		$s = str_replace('€','eur', $s);
		$s = str_replace('$','usd', $s);
		$s = str_replace('£','pound', $s);
		$s = str_replace('¥','yen', $s);

		$s = str_replace(' ', '-', $s);

		while (strpos($s, '--') !== false) {
			$s = str_replace('--','-',$s);
		}

		$s = preg_replace('~[^\\pL\d]+~u', '-', $s);
		$s = trim($s, '-');
		$s = iconv('utf-8', 'us-ascii//TRANSLIT', $s);
		$s = strtolower($s);
		$s = preg_replace('~[^-\w]+~', '', $s);

		return $s;
	}

}
