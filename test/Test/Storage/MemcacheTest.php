<?php

namespace PhpConsole\Test\Storage;

use PhpConsole\Storage\Memcache;

class MemcacheTest extends Test {

	/**
	 * @return \PhpConsole\Storage
	 */
	protected function initStorage() {
		if(!class_exists('Memcache')) {
			$this->markTestSkipped('Memcache extension not installed');
		}
		try {
			return new Memcache();
		}
		catch(\Exception $exception) {
			$this->markTestSkipped('Unable to connect to memcache server');
		}
	}
}
