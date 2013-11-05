<?php

require_once('PhpConsole.php');
PhpConsole::start(true, true, dirname(__FILE__));

// test

debug('debug message');
debug('SELECT * FROM users', 'db');

class TestErrorBacktrace {
	function __construct() {
		$this->yeah(12, array());
	}
	function yeah() {
		self::oops('some string', new stdClass());
	}
	static function oops() {
		file_get_contents('oops.txt');
		throw new Exception('Exception with call backtrace');
	}
}

new TestErrorBacktrace();