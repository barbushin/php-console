<?php

namespace PhpConsole\Test\Storage;

class MongoDB extends \PhpConsole\Test\Storage {

	/**
	 * @return \PhpConsole\Storage
	 */
	protected function initStorage() {
		if(!class_exists('MongoClient')) {
			$this->markTestSkipped('Mongo extension not installed');
		}
		try {
			return new \PhpConsole\Storage\MongoDB();
		}
		catch(\Exception $exception) {
			$this->markTestSkipped('Unable to connect to MongoDB server');
		}
		return null;
	}
}
