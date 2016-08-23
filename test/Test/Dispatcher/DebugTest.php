<?php

namespace PhpConsole\Test\Dispatcher;

use PhpConsole\Connector;
use PhpConsole\DebugMessage;
use PhpConsole\Dispatcher\Debug;
use PhpConsole\Dumper;
use PhpConsole\TraceCall;

class DebugTest extends Test {

	/** @var  Debug */
	protected $dispatcher;

	protected function initDispatcher(Connector $connector) {
		return new Debug($connector, new Dumper());
	}

	public function testDispatchMessageIsSent() {
		$this->connector->expects($this->once())->method('sendMessage');
		$this->dispatcher->dispatchDebug(123);
	}

	public function testDispatchIgnoredOnNotActiveClient() {
		$this->isDispatcherActive = false;
		$this->connector->expects($this->never())->method('sendMessage');
		$this->dispatcher->dispatchDebug(123);
	}

	public function testDispatchDebugTagsAndData() {
		$debug = array(
			'data' => 123,
			'tags' => array('t', 'a', 'g', 's'),
		);
		$test = $this;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (DebugMessage $message) use ($test, $debug) {
				$test->assertContainsRecursive($debug, $message);
				return true;
			}));
		$this->dispatcher->dispatchDebug($debug['data'], implode('.', $debug['tags']));
	}

	public function testDispatchDataIsDumped() {
		$this->dispatcher->setDumper(new Dumper(1, 1, 9));
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->equalTo(new DebugMessage(array(
				'data' => '123456...',
			))));
		$this->dispatcher->dispatchDebug('1234567890');
	}

	public function testTraceAndSourceDetectionDisabledByDefault() {
		$test = $this;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (DebugMessage $message) use ($test) {
				$test->assertEmpty($message->trace);
				return true;
			}));
		$this->dispatcher->dispatchDebug(123);
	}

	public function testSourceAndTraceDetection() {
		$lastCallLine = null;
		$test = $this;
		$this->dispatcher->detectTraceAndSource = true;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (DebugMessage $message) use ($test, &$lastCallLine) {
				$actualCall = end($message->trace);
				$test->assertEquals(__FILE__, $message->file);
				$test->assertEquals($message->data['line'], $message->line);
				$test->assertEquals(new TraceCall(array(
					'file' => __FILE__,
					'line' => $lastCallLine,
					'call' => $actualCall->call
				)), $actualCall);
				return true;
			}));

		$dispatcher = $this->dispatcher;
		$func = function () use ($dispatcher) {
			$dispatcher->dispatchDebug(array('line' => __LINE__));
		};
		$func($lastCallLine = __LINE__, null, true, '0123456789012345', array(1, 2), new \stdClass());
	}

	public function testIgnoreCallsByNumber() {
		$test = $this;
		$actualTraceCalls = count(debug_backtrace());
		$ignoreTraceCalls = 3;
		$this->dispatcher->detectTraceAndSource = true;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (DebugMessage $message) use ($test, $ignoreTraceCalls, $actualTraceCalls) {
				$test->assertEquals($actualTraceCalls - $ignoreTraceCalls, count($message->trace));
				return true;
			}));
		$this->dispatcher->dispatchDebug(null, null, $ignoreTraceCalls);
	}

	public function testIgnoreCallsByClassNames() {
		$test = $this;
		$actualTraceCalls = count(debug_backtrace());
		$ignoreTraceClasses = array('PhpConsole\Test', 'ReflectionMethod');
		$this->dispatcher->detectTraceAndSource = true;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (DebugMessage $message) use ($test, $ignoreTraceClasses, $actualTraceCalls) {
				$test->assertEquals($actualTraceCalls - count($ignoreTraceClasses), count($message->trace));
				return true;
			}));
		$this->dispatcher->dispatchDebug(null, null, $ignoreTraceClasses);
	}
}
