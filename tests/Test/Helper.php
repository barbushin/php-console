<?php

namespace PhpConsole\Test;

class Helper extends Test {

	/** @var  \PhpConsole\Connector */
	protected $connector;
	/** @var  \PhpConsole\Dispatcher\Debug|\PHPUnit_Framework_MockObject_MockObject */
	protected $debugDispatcher;

	protected function setUp() {
		$this->connector = \PhpConsole\Connector::getInstance();
		$this->setProtectedProperty($this->connector, 'isActiveClient', true);

		$this->debugDispatcher = $this->getMockBuilder('\PhpConsole\Dispatcher\Debug')
			->setConstructorArgs(array($this->connector, new \PhpConsole\Dumper()))
			->setMethods(array('dispatchDebug'))
			->getMock();
		$this->connector->setDebugDispatcher($this->debugDispatcher);
	}

	public function testCallNotRegisteredDebugNotDispatcher() {
		$this->debugDispatcher->expects($this->never())
			->method('dispatchDebug');
		\PhpConsole\Helper::debug(123);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testGetConnectorBeforeRegisterThrowsException() {
		\PhpConsole\Helper::getConnector();
	}

	/**
	 * @expectedException \Exception
	 */
	public function testGetHandlerBeforeRegisterThrowsException() {
		\PhpConsole\Helper::getHandler();
	}

	/**
	 * @expectedException \Exception
	 */
	public function testDoubleRegisterThrowsException() {
		\PhpConsole\Helper::register();
		\PhpConsole\Helper::register();
	}

	public function testGetDefaultConnector() {
		$connector = \PhpConsole\Helper::register();
		$this->assertTrue($connector instanceof \PhpConsole\Connector);
		$this->assertTrue(\PhpConsole\Helper::getConnector() instanceof \PhpConsole\Connector);
	}

	public function testIsRegisteredReturnsFalse() {
		$this->assertFalse(\PhpConsole\Helper::isRegistered());
	}

	public function testIsRegisteredReturnsTrue() {
		\PhpConsole\Helper::register();
		$this->assertTrue(\PhpConsole\Helper::isRegistered());
	}

	public function testGetCustomConnector() {
		\PhpConsole\Helper::register($this->connector);
		$this->assertEquals(spl_object_hash($this->connector), spl_object_hash(\PhpConsole\Helper::getConnector()));
	}

	public function testGetDefaultHandler() {
		\PhpConsole\Helper::register();
		$this->assertTrue(\PhpConsole\Helper::getHandler() instanceof \PhpConsole\Handler);
	}

	public function testGetCustomHandler() {
		$handler = \PhpConsole\Handler::getInstance();
		\PhpConsole\Helper::register($this->connector, $handler);
		$this->assertEquals(spl_object_hash($handler), spl_object_hash(\PhpConsole\Helper::getHandler()));
	}

	public function testDebug() {
		$this->debugDispatcher->expects($this->once())
			->method('dispatchDebug')
			->with($this->equalTo(123), $this->equalTo('db'));

		\PhpConsole\Helper::register($this->connector);
		/** @noinspection PhpUndefinedMethodInspection */
		\PhpConsole\Helper::debug(123, 'db');
	}

	public function testCallStaticDebug() {
		$this->debugDispatcher->expects($this->once())
			->method('dispatchDebug')
			->with($this->equalTo(123), $this->equalTo('db'));

		\PhpConsole\Helper::register($this->connector);
		/** @noinspection PhpUndefinedMethodInspection */
		\PhpConsole\Helper::db(123);
	}

	public function testShortHelperLoaded() {
		\PhpConsole\Helper::register($this->connector);
		$this->assertEquals('PhpConsole\Helper', get_parent_class('PC'));
	}

	public function testConstructDisabled() {
		foreach(array('PhpConsole\Helper', 'PC') as $class) {
			$method = new \ReflectionMethod($class, '__construct');
			$this->assertTrue($method->isProtected() || $method->isPrivate());
		}
	}
}
