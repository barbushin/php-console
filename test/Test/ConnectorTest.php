<?php

namespace PhpConsole\Test;

use PhpConsole\Connector;
use PhpConsole\Dispatcher\Debug;
use PhpConsole\Dispatcher\Errors;
use PhpConsole\Dispatcher\Evaluate;
use PhpConsole\Dumper;
use PhpConsole\EvalProvider;

class ConnectorTest extends Test {

	/** @var  Connector */
	protected $connector;

	protected function setUp() {
		parent::setUp();
		$this->connector = Connector::getInstance();
	}

	public function testIsSingleton() {
		$this->assertIsSingleton('\PhpConsole\Connector');
	}

	public function testIsNotActiveClientByDefault() {
		$this->assertFalse(Connector::getInstance()->isActiveClient());
	}

	public function testIsNotAuthorizedByDefault() {
		$this->assertFalse(Connector::getInstance()->isAuthorized());
	}

	public function testGetDebugDispatcher() {
		$this->assertInstanceOf('\PhpConsole\Dispatcher\Debug', $this->connector->getDebugDispatcher());
	}

	public function testSetDebugDispatcher() {
		$dispatcher = new Debug($this->connector, new Dumper());
		$this->connector->setDebugDispatcher($dispatcher);
		$this->assertEquals(spl_object_hash($dispatcher), spl_object_hash($this->connector->getDebugDispatcher()));
	}

	public function testGetErrorsDispatcher() {
		$this->assertInstanceOf('\PhpConsole\Dispatcher\Errors', $this->connector->getErrorsDispatcher());
	}

	public function testSetErrorsDispatcher() {
		$dispatcher = new Errors($this->connector, new Dumper());
		$this->connector->setErrorsDispatcher($dispatcher);
		$this->assertEquals(spl_object_hash($dispatcher), spl_object_hash($this->connector->getErrorsDispatcher()));
	}

	public function testSetEvalDispatcher() {
		$dispatcher = new Evaluate($this->connector, new EvalProvider(), new Dumper());
		$this->connector->setEvalDispatcher($dispatcher);
		$this->assertEquals(spl_object_hash($dispatcher), spl_object_hash($this->connector->getEvalDispatcher()));
	}

	public function testGetEvalDispatcher() {
		$this->assertInstanceOf('\PhpConsole\Dispatcher\Evaluate', $this->connector->getEvalDispatcher());
	}
}
