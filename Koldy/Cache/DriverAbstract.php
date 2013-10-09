<?php namespace Koldy\Cache;

abstract class DriverAbstract {

	protected $config = null;

	protected $defaultDuration = 3600;

	abstract public function __construct(array $config);

	abstract public function get($key);

	abstract public function set($key, $value, $seconds = null);

	abstract public function add($key, $value, $seconds = null);

	abstract public function has($key);

	abstract public function delete($key);

	abstract public function getOrSet($key, $functionOnSet, $seconds = null);
}