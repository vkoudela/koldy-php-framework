<?php

namespace Koldy;

class Server {

	/**
	 * Get server load ... if linux, returns all three averages, if windows, returns
	 * average load for all CPU cores
	 *
	 * @return string|null
	 */
	public static function getServerLoad() {
		if (function_exists('sys_getloadavg')) {
			$a = sys_getloadavg();
			foreach ($a as $k => $v) {$a[$k] = round($v, 2);}
			return implode(', ', $a);
		} else {
			$os = strtolower(PHP_OS);
			if (strpos($os, 'win') === false) {
				if (@file_exists('/proc/loadavg') && @is_readable('/proc/loadavg')) {
					$load = file_get_contents('/proc/loadavg');
					$load = explode(' ', $load);
					return implode(',', $load);
				} else if (function_exists('shell_exec')) {
					$load = @shell_exec('uptime');
					$load = split('load average' . (PHP_OS == 'Darwin' ? 's' : '') . ':', $load);
					return implode(',', $load);
					//return $load[count($load)-1];
				} else {
					return null;
				}
			} else if (class_exists('COM')) {
				$wmi = new COM("WinMgmts:\\\\.");
				$cpus = $wmi->InstancesOf('Win32_Processor');

				$cpuload = 0;
				$i = 0;
				while ($cpu = $cpus->Next()) {
					$cpuload += $cpu->LoadPercentage;
					$i++;
				}

				$cpuload = round($cpuload / $i, 2);
				return $cpuload . '%';
			}
		}

		return null;
	}

	/**
	 * Get the server's ip address where xcms is running
	 * @return string
	 */
	public static function getServerAddr() {
		return isset($_SERVER['SERVER_ADDR'])
			? $_SERVER['SERVER_ADDR']
			: '127.0.0.1';
	}

	/**
	 * Get HTTP host of where the site is running
	 * @return string|null
	 * @example domain.com or m.domain.com
	 */
	public static function getServerHost() {
		return (isset($_SERVER['HTTP_HOST']))
			? $_SERVER['HTTP_HOST']
			: null;
	}

	/**
	 * Get HTTP host (alias to getServerHost)
	 * @return string|null
	 * @example domain.com or m.domain.com
	 */
	public static function getHttpHost() {
		return self::getServerHost();
	}

	/**
	 * Get the server's ip address
	 * @return string
	 */
	public static function getServerIp() {
		return self::getServerAddr();
	}

	/**
	 * Get the server's signature
	 * @return string or null if not set
	 */
	public static function getServerSignature() {
		return isset($_SERVER['SERVER_SIGNATURE'])
			? $_SERVER['SERVER_SIGNATURE']
			: null;
	}

	/**
	 * Get the server's software
	 * @return string or null if not set
	 */
	public static function getServerSoftware() {
		return isset($_SERVER['SERVER_SOFTWARE'])
			? $_SERVER['SERVER_SOFTWARE']
			: null;
	}

}