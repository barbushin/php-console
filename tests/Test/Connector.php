<?php

namespace PhpConsole\Test;

class Connector extends Test {

	/** @var  \PhpConsole\Connector */
	protected $connector;

	protected function setUp() {
		parent::setUp();
		$this->connector = \PhpConsole\Connector::getInstance();
	}

	public function testIsSingleton() {
		$this->assertIsSingleton('\PhpConsole\Connector');
	}

	public function testIsNotActiveClientByDefault() {
		$this->assertFalse(\PhpConsole\Connector::getInstance()->isActiveClient());
	}

	public function testIsNotAuthorizedByDefault() {
		$this->assertFalse(\PhpConsole\Connector::getInstance()->isAuthorized());
	}

	public function testGetDebugDispatcher() {
		$this->assertInstanceOf('\PhpConsole\Dispatcher\Debug', $this->connector->getDebugDispatcher());
	}

	public function testSetDebugDispatcher() {
		$dispatcher = new \PhpConsole\Dispatcher\Debug($this->connector, new \PhpConsole\Dumper());
		$this->connector->setDebugDispatcher($dispatcher);
		$this->assertEquals(spl_object_hash($dispatcher), spl_object_hash($this->connector->getDebugDispatcher()));
	}

	public function testGetErrorsDispatcher() {
		$this->assertInstanceOf('\PhpConsole\Dispatcher\Errors', $this->connector->getErrorsDispatcher());
	}

	public function testSetErrorsDispatcher() {
		$dispatcher = new \PhpConsole\Dispatcher\Errors($this->connector, new \PhpConsole\Dumper());
		$this->connector->setErrorsDispatcher($dispatcher);
		$this->assertEquals(spl_object_hash($dispatcher), spl_object_hash($this->connector->getErrorsDispatcher()));
	}

	public function testSetEvalDispatcher() {
		$dispatcher = new \PhpConsole\Dispatcher\Evaluate($this->connector, new \PhpConsole\EvalProvider(), new \PhpConsole\Dumper());
		$this->connector->setEvalDispatcher($dispatcher);
		$this->assertEquals(spl_object_hash($dispatcher), spl_object_hash($this->connector->getEvalDispatcher()));
	}

	public function testGetEvalDispatcher() {
		$this->assertInstanceOf('\PhpConsole\Dispatcher\Evaluate', $this->connector->getEvalDispatcher());
	}
}
