<?php

namespace PhpConsole\Test;

class Dispatcher_Evaluate extends Dispatcher {

	/** @var  \PhpConsole\Dispatcher\Evaluate */
	protected $dispatcher;

	protected function initDispatcher(\PhpConsole\Connector $connector) {
		return new \PhpConsole\Dispatcher\Evaluate($connector, new \PhpConsole\EvalProvider(), new \PhpConsole\Dumper());
	}

	public function testDispatchMessageIsSent() {
		$this->connector->expects($this->once())->method('sendMessage');
		$this->dispatcher->dispatchCode('return 123');
	}

	public function testDispatchIgnoredOnNotActiveClient() {
		$this->isDispatcherActive = false;
		$this->connector->expects($this->never())->method('sendMessage');
		$this->dispatcher->dispatchCode('return 123');
	}

	public function testDispatchMessageData() {
		$test = $this;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (\PhpConsole\EvalResultMessage $message) use ($test) {
				$test->assertContainsRecursive(array(
					'type' => 'eval_result',
					'return' => 321,
					'output' => 123,
				), $message);
				$test->assertTrue($message->time > 0 && $message->time < 1);
				return true;
			}));

		$this->dispatcher->dispatchCode('echo 123; usleep(1000); return 321;');
	}

	public function testDispatchDataIsDumped() {
		$dumper = new \PhpConsole\Dumper(1, 1, 10);
		$this->dispatcher->setDumper($dumper);

		$actualString = str_repeat('x', $dumper->itemSizeLimit + 10);
		$dumpedString = $dumper->dump($actualString);
		$this->assertTrue(strlen($dumpedString) < strlen($actualString));

		$test = $this;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (\PhpConsole\EvalResultMessage $message) use ($test, $dumpedString) {
				$test->assertEquals($dumpedString, $message->return);
				$test->assertEquals($dumpedString, $message->output);
				return true;
			}));

		$this->dispatcher->dispatchCode('echo "' . $actualString . '"; return "' . $actualString . '";');
	}

	public function testGetEvalProvider() {
		$this->assertInstanceOf('\PhpConsole\EvalProvider', $this->dispatcher->getEvalProvider());
	}

	public function testSetEvalProvider() {
		$evalProvider = new \PhpConsole\EvalProvider();
		$this->dispatcher->setEvalProvider($evalProvider);
		$this->assertEquals(spl_object_hash($evalProvider), spl_object_hash($this->dispatcher->getEvalProvider()));
	}

	public function testEvalErrorIsDispatched() {
		$test = $this;

		$this->connector->expects($this->at(2))
			->method('sendMessage')
			->with($this->callback(function ($message) use ($test) {
				$test->assertContainsRecursive(array(
					'type' => 'error',
					'code' => E_PARSE,
					'line' => 1,
				), $message);
				return true;
			}));

		$this->connector->expects($this->at(3))
			->method('sendMessage')
			->with($this->callback(function (\PhpConsole\Message $message) use ($test) {
				$test->assertContainsRecursive(array(
					'type' => 'eval_result',
					'return' => null,
					'output' => null,
				), $message);
				return true;
			}));

		$this->dispatcher->dispatchCode('if(');
	}

	public function testEvalExceptionIsDispatched() {
		$test = $this;

		$this->connector->expects($this->at(2))
			->method('sendMessage')
			->with($this->callback(function (\PhpConsole\Message $message) use ($test) {
				$test->assertContainsRecursive(array(
					'type' => 'error',
					'class' => 'Exception',
					'data' => 321,
					'line' => 1,
				), $message);
				return true;
			}));

		$this->connector->expects($this->at(3))
			->method('sendMessage')
			->with($this->callback(function (\PhpConsole\Message $message) use ($test) {
				$test->assertContainsRecursive(array(
					'type' => 'eval_result',
					'return' => null,
					'output' => 123,
				), $message);
				return true;
			}));

		$this->dispatcher->dispatchCode('echo 123; throw new Exception(321)');
	}
}
