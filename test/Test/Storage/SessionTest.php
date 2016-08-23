<?php

namespace PhpConsole\Test\Storage;

use PhpConsole\Storage\Session;

class SessionTest extends Test {

	/**
	 * @return \PhpConsole\Storage
	 */
	protected function initStorage() {
		$_SESSION = array();
		return new Session('__PHP_Console_postponed', false);
	}
}
