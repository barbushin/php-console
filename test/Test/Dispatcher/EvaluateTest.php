<?php

namespace PhpConsole\Test\Dispatcher;

use PhpConsole\Connector;
use PhpConsole\Dispatcher\Evaluate;
use PhpConsole\Dumper;
use PhpConsole\EvalProvider;
use PhpConsole\EvalResultMessage;
use PhpConsole\Message;

class EvaluateTest extends Test {

	/** @var  Evaluate */
	protected $dispatcher;

	protected function initDispatcher(Connector $connector) {
		return new Evaluate($connector, new EvalProvider(), new Dumper());
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
			->with($this->callback(function (EvalResultMessage $message) use ($test) {
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
		$dumper = new Dumper(1, 1, 10);
		$this->dispatcher->setDumper($dumper);

		$actualString = str_repeat('x', $dumper->itemSizeLimit + 10);
		$dumpedString = $dumper->dump($actualString);
		$this->assertTrue(strlen($dumpedString) < strlen($actualString));

		$test = $this;
		$this->connector->expects($this->once())
			->method('sendMessage')
			->with($this->callback(function (EvalResultMessage $message) use ($test, $dumpedString) {
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
		$evalProvider = new EvalProvider();
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
					'line' => 1,
				), $message);
				return true;
			}));

		$this->connector->expects($this->at(3))
			->method('sendMessage')
			->with($this->callback(function (Message $message) use ($test) {
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
			->with($this->callback(function (Message $message) use ($test) {
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
			->with($this->callback(function (Message $message) use ($test) {
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
