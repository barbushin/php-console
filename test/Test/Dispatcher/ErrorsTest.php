<?php

namespace PhpConsole\Test\Dispatcher;

use PhpConsole\Connector;
use PhpConsole\Dispatcher\Errors;
use PhpConsole\Dumper;
use PhpConsole\ErrorMessage;

class ErrorsTest extends Test {

	/** @var  Errors */
	protected $dispatcher;

	protected function initDispatcher(Connector $connector) {
		return new Errors($connector, new Dumper());
	}

	public function testDispatchErrorMessageData() {
		$test = $this;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (ErrorMessage $message) use ($test) {
				$test->assertContainsRecursive(array(
					'type' => 'error',
					'code' => E_WARNING,
					'class' => 'E_WARNING',
					'data' => 'error_text',
					'file' => __FILE__,
					'line' => 100,
				), $message);
				return true;
			}));
		$this->dispatcher->dispatchError(E_WARNING, 'error_text', __FILE__, 100);
	}

	public function testDispatchErrorMessageIsDumped() {
		$test = $this;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (ErrorMessage $message) use ($test) {
				$test->assertEquals('123456...', $message->data);
				return true;
			}));
		$this->dispatcher->setDumper(new Dumper(1, 1, 9));
		$this->dispatcher->dispatchError(null, 1234567890);
	}

	public function testDispatchErrorActualTrace() {
		$test = $this;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (ErrorMessage $message) use ($test) {
				$lastCall = end($message->trace);
				$test->assertContains(__CLASS__, $lastCall->call);
				$test->assertContains($test->getName(), $lastCall->call);
				return true;
			}));
		$this->dispatcher->dispatchError();
	}

	public function testDispatchExceptionMessageData() {
		$test = $this;
		$exception = new \Exception('exception_test', 100);
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (ErrorMessage $message) use ($test, $exception) {
				$test->assertContainsRecursive(array(
					'type' => 'error',
					'code' => $exception->getCode(),
					'class' => get_class($exception),
					'data' => $exception->getMessage(),
					'file' => $exception->getFile(),
					'line' => $exception->getLine(),
				), $message);
				return true;
			}));
		$this->dispatcher->dispatchException($exception);
	}

	public function testDispatchExceptionMessageIsDumped() {
		$test = $this;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (ErrorMessage $message) use ($test) {
				$test->assertEquals('123456...', $message->data);
				return true;
			}));
		$this->dispatcher->setDumper(new Dumper(1, 1, 9));
		$this->dispatcher->dispatchException(new \Exception(1234567890));
	}

	public function testDispatchExceptionActualTrace() {
		$test = $this;
		$exception = new \Exception();
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (ErrorMessage $message) use ($test) {
				$lastCall = end($message->trace);
				$test->assertContains(__CLASS__, $lastCall->call);
				$test->assertContains($test->getName(), $lastCall->call);
				return true;
			}));
		$this->dispatcher->dispatchException($exception);
	}

//	public function testDispatchPreviousExceptions() {
//		$this->connector->expects($this->exactly(3))->method('sendMessage');
//		$this->dispatcher->dispatchException(new \Exception(null, 0, new \Exception(null, 0, new \Exception())));
//	}

	public function testDisableDispatchPreviousExceptions() {
		$this->connector->expects($this->once())->method('sendMessage');
		$this->dispatcher->dispatchPreviousExceptions = false;
		$this->dispatcher->dispatchException(new \Exception(null, 0,
			new \Exception(null, 0,
				new \Exception())));
	}

	public function testDispatchErrorIgnoreRepeatedSource() {
		$this->connector->expects($this->once())->method('sendMessage');
		$this->dispatcher->dispatchError(null, null, __FILE__, 100);
		$this->dispatcher->dispatchError(null, null, __FILE__, 100);
	}

	public function testDispatchErrorNotIgnoreRepeatedSourceDifferentClass() {
		$this->connector->expects($this->exactly(2))->method('sendMessage');
		$this->dispatcher->dispatchError(E_WARNING, null, __FILE__, 100);
		$this->dispatcher->dispatchError(E_NOTICE, null, __FILE__, 100);
	}

	public function testDispatchErrorWithDisabledIgnoreRepeatedSource() {
		$this->dispatcher->ignoreRepeatedSource = false;
		$this->connector->expects($this->exactly(2))->method('sendMessage');
		$this->dispatcher->dispatchError(null, null, __FILE__, 100);
		$this->dispatcher->dispatchError(null, null, __FILE__, 100);
	}

	public function testDispatchExceptionIgnoreRepeatedSource() {
		$this->connector->expects($this->once())->method('sendMessage');
		$exception = new \Exception();
		$this->dispatcher->dispatchException($exception);
		$this->dispatcher->dispatchException($exception);
	}

	public function testDispatchExceptionNotIgnoreRepeatedSourceDifferentClass() {
		$this->connector->expects($this->exactly(2))->method('sendMessage');
		$exception1 = new \Exception('', 0, $exception2 = new Dispatcher_ErrorsDraftException());
		$this->dispatcher->dispatchException($exception1);
		$this->dispatcher->dispatchException($exception2);
	}

	public function testDispatchExceptionWithDisabledIgnoreRepeatedSource() {
		$this->dispatcher->ignoreRepeatedSource = false;
		$this->connector->expects($this->exactly(2))->method('sendMessage');
		$exception = new \Exception();
		$this->dispatcher->dispatchException($exception);
		$this->dispatcher->dispatchException($exception);
	}
}

class Dispatcher_ErrorsDraftException extends \Exception {

}
