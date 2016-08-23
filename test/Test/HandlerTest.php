<?php

namespace PhpConsole\Test;

use PhpConsole\DebugMessage;
use PhpConsole\ErrorMessage;
use PhpConsole\Handler;

class HandlerTest extends Test {

	/** @var  Handler */
	protected $handler;
	/** @var \PhpConsole\Connector|\PHPUnit_Framework_MockObject_MockObject */
	protected $connector;

	protected function setUp() {
		parent::setUp();
		$this->handler = Handler::getInstance();

		$this->connector = $this->getMockBuilder('\PhpConsole\Connector')
			->disableOriginalConstructor()
			->setMethods(array('sendMessage', 'onShutDown', 'isActiveClient'))
			->getMock();

		$this->connector->expects($this->any())
			->method('isActiveClient')
			->will($this->returnValue(true));

		$this->setProtectedProperty($this->handler, 'connector', $this->connector);
	}

	public function testGetConnector() {
		$this->assertInstanceOf('\PhpConsole\Connector', $this->handler->getConnector());
	}

	public function testIsSingleton() {
		$this->assertIsSingleton('\PhpConsole\Handler', $this->handler);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testSetHandleErrorsBeforeStartThrowsException() {
		$this->handler->start();
		$this->handler->setHandleErrors(true);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testSetHandleExceptionsBeforeStartThrowsException() {
		$this->handler->start();
		$this->handler->setHandleExceptions(true);
	}

	public function testInitErrorsHandler() {
		$this->handler->start();
		$this->assertEmpty(ini_get('display_errors'));
		$this->assertEmpty(ini_get('html_errors'));
		$this->assertEquals(E_ALL | E_STRICT, error_reporting());
		$this->assertEquals(array($this->handler, 'handleError'), set_error_handler($this->getProtectedProperty($this->handler, 'oldErrorsHandler')));
	}

	public function testInitExceptionsHandler() {
		$this->handler->start();
		$this->assertEquals(array($this->handler, 'handleException'), set_exception_handler($this->getProtectedProperty($this->handler, 'oldExceptionsHandler')));
	}

	/**
	 * @expectedException \Exception
	 */
	public function testDoubleStartThrowsException() {
		$this->handler->start();
		$this->handler->start();
	}

	public function testNoStartNoErrorsHandling() {
		$this->connector->expects($this->never())->method('sendMessage');
		$this->handler->handleError(1);
	}

	public function testNoStartNoExceptionsHandling() {
		$this->connector->expects($this->never())->method('sendMessage');
		$this->handler->handleException(new \Exception());
	}

	public function assertOldErrorsHandlerCalls($isEnabled) {
		$oldErrorsHandlerCalls = 0;
		set_error_handler(function () use (&$oldErrorsHandlerCalls) {
			$oldErrorsHandlerCalls++;
		});
		$this->handler->start();
		trigger_error(123);
		$this->assertEquals($isEnabled ? 1 : 0, $oldErrorsHandlerCalls);
	}

	public function assertOldExceptionsHandlerCalls($isEnabled) {
		$oldExceptionsHandlerCalls = 0;
		set_exception_handler(function () use (&$oldExceptionsHandlerCalls) {
			$oldExceptionsHandlerCalls++;
		});
		$this->handler->start();
		$this->handler->handleException(new \Exception());
		$this->assertEquals($isEnabled ? 1 : 0, $oldExceptionsHandlerCalls);
	}

	public function testOldErrorsHandlerIsCalledByDefault() {
		$this->assertOldErrorsHandlerCalls(true);
	}

	public function testOldErrorsHandlerCallDisable() {
		$this->handler->setCallOldHandlers(false);
		$this->assertOldErrorsHandlerCalls(false);
	}

	public function testOldExceptionsHandlerIsCalledByDefault() {
		$this->assertOldExceptionsHandlerCalls(true);
	}

	public function testOldExceptionsHandlerCallDisable() {
		$this->handler->setCallOldHandlers(false);
		$this->assertOldExceptionsHandlerCalls(false);
	}

	public function testHandleErrorData() {
		$test = $this;
		$error = null;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (ErrorMessage $message) use ($test, &$error) {
				$lastCall = end($message->trace);
				$test->assertContainsRecursive($error, $message);
				$test->assertContains(__CLASS__, $lastCall->call);
				$test->assertContains($test->getName(), $lastCall->call);
				return true;
			}));
		$this->handler->start();
		$error = array(
			'type' => 'error',
			'code' => E_USER_WARNING,
			'class' => 'E_USER_WARNING',
			'data' => 'err',
			'file' => __FILE__,
			'line' => __LINE__ + 2,
		);
		$this->handler->handleError($error['code'], $error['data'], __FILE__, __LINE__);
	}

	public function testHandleExceptionData() {
		$test = $this;
		$exception = new \Exception('exception_test', 100);
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (ErrorMessage $message) use ($test, $exception) {
				$lastCall = end($message->trace);
				$test->assertContainsRecursive(array(
					'type' => 'error',
					'code' => $exception->getCode(),
					'class' => get_class($exception),
					'data' => $exception->getMessage(),
					'file' => $exception->getFile(),
					'line' => $exception->getLine(),
				), $message);
				$test->assertContains(__CLASS__, $lastCall->call);
				$test->assertContains($test->getName(), $lastCall->call);

				return true;
			}));
		$this->handler->start();
		$this->handler->handleException($exception);
	}

	public function testHandleDebug() {
		$debug = null;
		$test = $this;

		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (DebugMessage $message) use ($test, &$debug) {
				$lastCall = end($message->trace);
				$test->assertContainsRecursive($debug, $message);
				$test->assertContains(__CLASS__, $lastCall->call);
				$test->assertContains($test->getName(), $lastCall->call);
				return true;
			}));
		$this->handler->start();
		$this->handler->getConnector()->getDebugDispatcher()->detectTraceAndSource = true;
		$debug = array(
			'type' => 'debug',
			'data' => 'data',
			'tags' => array('t', 'a', 'g', 's'),
			'file' => __FILE__,
			'line' => __LINE__ + 2,
		);
		$this->handler->debug($debug['data'], implode('.', $debug['tags']));
	}

	public function testRecursiveErrorsHandlingLimit() {
		$handler = $this->handler;
		set_error_handler(function () use ($handler) {
			$handler->handleError();
		});
		$this->connector->getErrorsDispatcher()->ignoreRepeatedSource = false;
		$this->connector->expects($this->exactly(Handler::ERRORS_RECURSION_LIMIT))->method('sendMessage');
		$this->handler->start();
		$this->handler->handleError();
	}

	public function testRecursiveExceptionsHandlingLimit() {
		$handler = $this->handler;
		set_exception_handler(function () use ($handler) {
			$handler->handleException(new \Exception());
		});
		$this->connector->getErrorsDispatcher()->ignoreRepeatedSource = false;
		$this->connector->expects($this->exactly(Handler::ERRORS_RECURSION_LIMIT))->method('sendMessage');
		$this->handler->start();
		$this->handler->handleException(new \Exception());
	}

	/**
	 * @expectedException \Exception
	 */
	public function testSetErrorsHandlerLevelBeforeStartThrowsException() {
		$this->handler->start();
		$this->handler->setErrorsHandlerLevel(E_USER_NOTICE);
	}

	public function testSetErrorsHandlerLevel() {
		$this->handler->setErrorsHandlerLevel(E_ALL ^ E_USER_NOTICE);
		$this->handler->start();
		trigger_error('hehe', E_USER_NOTICE);
		$this->connector->expects($this->never())->method('sendMessage');
	}
}
