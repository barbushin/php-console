<?php

namespace PhpConsole\Test\Storage;

class Memcache extends \PhpConsole\Test\Storage {

	/**
	 * @return \PhpConsole\Storage
	 */
	protected function initStorage() {
		if(!class_exists('Memcache')) {
			$this->markTestSkipped('Memcache extension not installed');
		}
		try {
			return new \PhpConsole\Storage\Memcache();
		}
		catch(\Exception $exception) {
			$this->markTestSkipped('Unable to connect to memcache server');
		}
	}
}
