<?php

namespace PhpConsole\Test;

use PhpConsole\Connector;
use PhpConsole\Dumper;
use PhpConsole\Handler;
use PhpConsole\Helper;

class HelperTest extends Test {

	/** @var  Connector */
	protected $connector;
	/** @var  \PhpConsole\Dispatcher\Debug|\PHPUnit_Framework_MockObject_MockObject */
	protected $debugDispatcher;

	protected function setUp() {
		$this->connector = Connector::getInstance();
		$this->setProtectedProperty($this->connector, 'isActiveClient', true);

		$this->debugDispatcher = $this->getMockBuilder('\PhpConsole\Dispatcher\Debug')
			->setConstructorArgs(array($this->connector, new Dumper()))
			->setMethods(array('dispatchDebug'))
			->getMock();
		$this->connector->setDebugDispatcher($this->debugDispatcher);
	}

	public function testCallNotRegisteredDebugNotDispatcher() {
		$this->debugDispatcher->expects($this->never())
			->method('dispatchDebug');
		Helper::debug(123);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testGetConnectorBeforeRegisterThrowsException() {
		Helper::getConnector();
	}

	/**
	 * @expectedException \Exception
	 */
	public function testGetHandlerBeforeRegisterThrowsException() {
		Helper::getHandler();
	}

	/**
	 * @expectedException \Exception
	 */
	public function testDoubleRegisterThrowsException() {
		Helper::register();
		Helper::register();
	}

	public function testGetDefaultConnector() {
		$connector = Helper::register();
		$this->assertTrue($connector instanceof Connector);
		$this->assertTrue(Helper::getConnector() instanceof Connector);
	}

	public function testIsRegisteredReturnsFalse() {
		$this->assertFalse(Helper::isRegistered());
	}

	public function testIsRegisteredReturnsTrue() {
		Helper::register();
		$this->assertTrue(Helper::isRegistered());
	}

	public function testGetCustomConnector() {
		Helper::register($this->connector);
		$this->assertEquals(spl_object_hash($this->connector), spl_object_hash(Helper::getConnector()));
	}

	public function testGetDefaultHandler() {
		Helper::register();
		$this->assertTrue(Helper::getHandler() instanceof Handler);
	}

	public function testGetCustomHandler() {
		$handler = Handler::getInstance();
		Helper::register($this->connector, $handler);
		$this->assertEquals(spl_object_hash($handler), spl_object_hash(Helper::getHandler()));
	}

	public function testDebug() {
		$this->debugDispatcher->expects($this->once())
			->method('dispatchDebug')
			->with($this->equalTo(123), $this->equalTo('db'));

		Helper::register($this->connector);
		/** @noinspection PhpUndefinedMethodInspection */
		Helper::debug(123, 'db');
	}

	public function testCallStaticDebug() {
		$this->debugDispatcher->expects($this->once())
			->method('dispatchDebug')
			->with($this->equalTo(123), $this->equalTo('db'));

		Helper::register($this->connector);
		/** @noinspection PhpUndefinedMethodInspection */
		Helper::db(123);
	}

	public function testShortHelperLoaded() {
		Helper::register($this->connector);
		$this->assertEquals('PhpConsole\Helper', get_parent_class('PC'));
	}

	public function testConstructDisabled() {
		foreach(array('PhpConsole\Helper', 'PC') as $class) {
			$method = new \ReflectionMethod($class, '__construct');
			$this->assertTrue($method->isProtected() || $method->isPrivate());
		}
	}
}
