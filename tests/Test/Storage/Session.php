<?php

namespace PhpConsole\Test\Storage;

class Session extends \PhpConsole\Test\Storage {

	/**
	 * @return \PhpConsole\Storage
	 */
	protected function initStorage() {
		$_SESSION = array();
		return new \PhpConsole\Storage\Session('__PHP_Console_postponed', false);
	}
}
