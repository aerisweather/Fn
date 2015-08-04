<?php


namespace Aeris\FnTest\Fixture;


class SomeClass {

	public $ctorArgs;

	public function __construct() {
		$this->ctorArgs = func_get_args();
	}

	public function getSomeProp() {
		return 'someVal';
	}

	public function passThru($arg) {
		return $arg;
	}

}