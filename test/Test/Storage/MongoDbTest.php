<?php

namespace PhpConsole\Test\Storage;

use PhpConsole\Storage\MongoDB;

class MongoDbTest extends Test {

	/**
	 * @return \PhpConsole\Storage
	 */
	protected function initStorage() {
		if(!class_exists('MongoClient')) {
			$this->markTestSkipped('Mongo extension not installed');
		}
		try {
			return new MongoDB();
		}
		catch(\Exception $exception) {
			$this->markTestSkipped('Unable to connect to MongoDB server');
		}
		return null;
	}
}
