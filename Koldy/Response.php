<?php

namespace Koldy;

abstract class Response {

	protected $workAfterResponse = null;

	abstract public function flush();

	public function after($function) {
		$this->workAfterResponse = $function;
		return $this;
	}

}