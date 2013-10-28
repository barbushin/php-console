<?php

namespace PhpConsole\Test;

class Dispatcher_Debug extends Dispatcher {

	/** @var  \PhpConsole\Dispatcher\Debug */
	protected $dispatcher;

	protected function initDispatcher(\PhpConsole\Connector $connector) {
		return new \PhpConsole\Dispatcher\Debug($connector, new \PhpConsole\Dumper());
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
			->with($this->callback(function (\PhpConsole\DebugMessage $message) use ($test, $debug) {
				$test->assertContainsRecursive($debug, $message);
				return true;
			}));
		$this->dispatcher->dispatchDebug($debug['data'], implode('.', $debug['tags']));
	}

	public function testDispatchDataIsDumped() {
		$this->dispatcher->setDumper(new \PhpConsole\Dumper(1, 1, 9));
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->equalTo(new \PhpConsole\DebugMessage(array(
				'data' => '123456...',
			))));
		$this->dispatcher->dispatchDebug('1234567890');
	}

	public function testTraceAndSourceDetectionDisabledByDefault() {
		$test = $this;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (\PhpConsole\DebugMessage $message) use ($test) {
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
			->with($this->callback(function (\PhpConsole\DebugMessage $message) use ($test, &$lastCallLine) {
				$test->assertEquals(__FILE__, $message->file);
				$test->assertEquals($message->data['line'], $message->line);
				$test->assertEquals(new \PhpConsole\TraceCall(array(
					'file' => __FILE__,
					'line' => $lastCallLine,
					'call' => $message->data['class'] . '->' . $message->data['method'] . '(' . $lastCallLine . ', NULL, true, \'01234567890123...\', Array[2], stdClass)'
				)), end($message->trace));
				return true;
			}));
		$this->callDispatchDebug($lastCallLine = __LINE__, null, true, '0123456789012345', array(1, 2), new \stdClass());
	}

	protected function callDispatchDebug() {
		$this->dispatcher->dispatchDebug(array('class' => get_class($this), 'method' => __FUNCTION__, 'line' => __LINE__));
	}
}
