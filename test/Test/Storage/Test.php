<?php

namespace PhpConsole\Test\Storage;

abstract class Test extends \PhpConsole\Test\Test {

	/** @var  \PhpConsole\Storage */
	protected $storage;

	/**
	 * @return \PhpConsole\Storage
	 */
	abstract protected function initStorage();

	protected function setUp() {
		parent::setUp();
		$this->storage = $this->initStorage();
	}

	protected function generateKey() {
		return mt_rand() . mt_rand() . mt_rand();
	}

	public function testPush() {
		$key = $this->generateKey();
		$data = $this->generateKey();
		$this->storage->push($key, $data);
		$this->assertEquals($data, $this->storage->pop($key));
	}

	public function testPop() {
		$key = $this->generateKey();
		$data = $this->generateKey();
		$this->storage->push($key, $data);
		$this->storage->pop($key);
		$this->assertNull($this->storage->pop($key));
	}

	/**
	 * @group slow
	 */
	public function testSetKeyLifetime() {
		$key = $this->generateKey();
		$this->storage->setKeyLifetime(1);
		$this->storage->push($key, 123);
		sleep(2);
		$this->storage->push($this->generateKey(), 123);
		$this->assertNull($this->storage->pop($key));
	}
}
