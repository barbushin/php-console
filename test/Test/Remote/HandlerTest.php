<?php

namespace PhpConsole\Test\Remote;

abstract class HandlerTest extends Test {

	public function testNotFatalErrorsHandling() {
		$this->request->addScript('trigger_not_fatal_errors');
		$this->sendRequest();
		$this->assertRandomMessageInResponse();

		$message = $this->findMessageInResponse(array('code' => E_NOTICE));
		$this->assertContainsRecursive(array(
			'type' => 'error',
			'code' => E_NOTICE,
			'class' => 'E_NOTICE',
			'data' => 'Undefined variable: x',
			'file' => $this->clientEmulator->getScriptPath('trigger_not_fatal_errors'),
			'line' => 5,
		), $message);
		$lastCall = end($message['trace']);
		$this->assertEquals($lastCall['file'], $this->clientEmulator->getScriptPath('trigger_not_fatal_errors'));

		$message = $this->findMessageInResponse(array('code' => E_WARNING));
		$this->assertContainsRecursive(array(
			'type' => 'error',
			'code' => 2,
			'class' => 'E_WARNING',
			'data' => 'file_get_contents(/not-exists): failed to open stream: No such file or directory',
			'file' => $this->clientEmulator->getScriptPath('trigger_not_fatal_errors'),
			'line' => 6,
		), $message);
		$lastCall = end($message['trace']);
		$this->assertEquals($lastCall['file'], $this->clientEmulator->getScriptPath('trigger_not_fatal_errors'));
	}

	public static function provideFatalScriptsError() {
		return static::getAssocTwoArgsProviderData(array(
			'trigger_fatal_error' => array(
				array(
					'type' => 'error',
					'data' => 'undefined',
				)),
			'trigger_parse_error' => array(
				array(
					'type' => 'error',
					'data' => 'syntax error',
				)),
			'trigger_compile_error' => array(
				array(
					'type' => 'error',
					'code' => E_COMPILE_ERROR,
					'class' => 'E_COMPILE_ERROR',
					'data' => 'require_once',
				),
				array(
					'type' => 'error',
					'code' => E_WARNING,
					'class' => 'E_WARNING',
					'data' => 'require_once',
				)),
			'trigger_memory_limit_error' => array(
				array(
					'type' => 'error',
					'code' => E_ERROR,
					'class' => 'E_ERROR',
					'data' => 'Allowed memory size',
				)),
		));
	}

	/**
	 * @dataProvider provideFatalScriptsError
	 * @param $scriptAlias
	 * @param array $expectedMessages
	 */
	public function testFatalErrorsHandling($scriptAlias, array $expectedMessages) {
		$this->randomOutputMustPresentInResponse = false;
		$this->request->addScript($scriptAlias);
		$this->sendRequest();
		$this->assertRandomMessageInResponse(true);

		foreach($expectedMessages as $expectedMessage) {
			$message = $this->findMessageInResponse(isset($expectedMessage['code'])
				? array('code' => $expectedMessage['code'])
				: array('type' => 'error')
			);

			$this->assertContains($expectedMessage['data'], $message['data']);
			unset($expectedMessage['data']);

			$expectedMessage['file'] = $this->clientEmulator->getScriptPath($scriptAlias);
			$this->assertContainsRecursive($expectedMessage, $message);
		}
	}

	/**
	 * @group slow
	 */
	public function testMaxExecutionErrorHandling() {
		$this->testFatalErrorsHandling('trigger_max_execution_time_error', array(array(
			'type' => 'error',
			'code' => E_ERROR,
			'class' => 'E_ERROR',
			'data' => 'Maximum execution time',
		)));
	}

	public function testUncaughtExceptionHandling() {
		$scriptAlias = 'trigger_exception';
		$this->randomOutputMustPresentInResponse = false;
		$expectedMessage = array(
			'data' => 'oops',
			'code' => 100,
			'class' => 'Exception',
			'file' => $this->clientEmulator->getScriptPath($scriptAlias),
			'line' => 4
		);
		$this->request->addScript($scriptAlias, array(
			'message' => $expectedMessage['data'],
			'code' => $expectedMessage['code'],
		));
		$this->sendRequest();
		$this->assertRandomMessageInResponse(true);

		$message = $this->findMessageInResponse(array(
			'code' => $expectedMessage['code']
		));
		$this->assertContainsRecursive($expectedMessage, $message);

		$lastCall = end($message['trace']);
		$this->assertContainsRecursive(array(
			'file' => $this->clientEmulator->getScriptPath($scriptAlias),
			'line' => 7,
		), $lastCall);
		$this->assertContains('closure', $lastCall['call']);
	}
}
