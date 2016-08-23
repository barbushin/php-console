<?php

namespace PhpConsole\Test\Remote;

use PhpConsole\Auth;
use PhpConsole\Connector;
use PhpConsole\EvalProvider;

class EvaluateTest extends Test {

	protected function setUp() {
		parent::setUp();
		$this->randomOutputMustPresentInResponse = false;
	}

	protected function setUpConnector() {
		parent::setUpConnector();
		$this->request->addScript('init_default_handler');
	}

	protected function setRequestEval($code, $signature = null, Auth $auth = null) {
		if(!$auth) {
			$auth = $this->setRequestAuth();
		}
		$this->request->postData[Connector::POST_VAR_NAME]['eval'] = array(
			'data' => $code,
			'signature' => $signature ? : $auth->getSignature($code),
		);
	}

	public function testIsEvalNotEnabledInResponse() {
		$this->sendRequest();
		$this->assertEmpty($this->response->package->isEvalEnabled);
	}

	public function testIsEvalEnabledInResponse() {
		$this->setConnectorAuth();
		$this->setRequestAuth();
		$this->request->addScript('set_connector_eval_enabled');
		$this->sendRequest();
		$this->assertTrue($this->response->package->isEvalEnabled);
	}

	public function testEnableEvalInNotAuthModeThrowsException() {
		$this->request->addScript('set_connector_eval_enabled');
		$this->sendRequest();
		$this->assertMessageInResponse(array(
			'type' => 'error',
			'class' => 'Exception',
			'data' => 'Eval dispatcher is allowed only in password protected mode. See PhpConsole\Connector::getInstance()->setPassword(...)',
		));
	}

	public function testEvalResultInResponse() {
		$this->setConnectorAuth();
		$this->request->addScript('set_connector_eval_enabled');
		$this->setRequestEval('echo 321; return 123;');

		$this->sendRequest();

		$this->assertMessageInResponse(array(
			'type' => 'eval_result',
			'return' => 123,
			'output' => '321',
		));

		$this->assertEmpty($this->findMessagesInResponse(array(
			'type' => 'error'
		)));
	}

	public function testFlushDebugMessagesEnabled() {
		$this->setConnectorAuth();
		$this->request->addScript('set_connector_eval_enabled');
		$this->setRequestEval('return 123');
		$this->sendRequest();
		$this->assertRandomMessageInResponse(false);
	}

	public function testFlushDebugMessagesDisabled() {
		$this->setConnectorAuth();
		$this->request->addScript('set_connector_eval_enabled', array(
			'flushDebugMessages' => false
		));
		$this->setRequestEval('return 123');
		$this->sendRequest();
		$this->assertRandomMessageInResponse();
	}

	public function testExitOnEvalDisabled() {
		$this->randomOutputMustPresentInResponse = true;
		$this->setConnectorAuth();
		$this->request->addScript('set_connector_eval_enabled', array(
			'exitOnEval' => false
		));
		$this->setRequestEval('return 123');
		$this->sendRequest();
	}

	public function testNoEvalIfAuthFails() {
		$this->setConnectorAuth('oops');
		$this->request->addScript('set_connector_eval_enabled');
		$this->setRequestEval('echo 321; return 123;');

		$this->sendRequest();

		$this->assertEmpty($this->findMessageInResponse(array(
			'type' => 'eval_result',
		)));
	}

	public function testEvalErrorIsHandled() {
		$this->setConnectorAuth();
		$this->request->addScript('set_connector_eval_enabled');
		$this->setRequestEval('oops()');
		$this->sendRequest();
		$this->assertMessageInResponse(array(
			'type' => 'error',
			'data' => 'Call to undefined function oops()'
		));
	}

	public function testEvalExceptionIsHandled() {
		$this->setConnectorAuth();
		$this->request->addScript('set_connector_eval_enabled');
		$this->setRequestEval('throw new Exception(123)');
		$this->sendRequest();
		$this->assertMessageInResponse(array(
			'type' => 'error',
			'class' => 'Exception',
		));
	}

	public function testDisableFileAccessByOpenBaseDir() {
		$evalProvider = new EvalProvider();
		$evalProvider->disableFileAccessByOpenBaseDir();
		$this->setConnectorAuth();
		$this->request->addScript('set_connector_eval_enabled', array(
			'evalProvider' => $evalProvider
		));
		$this->setRequestEval('echo file_get_contents("' . __FILE__ . '");');
		$this->sendRequest();

		$this->assertMessageInResponse(array(
			'type' => 'eval_result',
			'return' => null,
			'output' => '',
		));

		$this->assertMessageInResponse(array(
			'type' => 'error',
			'code' => E_WARNING,
		));
	}

	public function tesSetOpenBaseDirs() {
		$evalProvider = new EvalProvider();
		$evalProvider->setOpenBaseDirs(__DIR__);
		$this->setConnectorAuth();
		$this->request->addScript('set_connector_eval_enabled', array(
			'evalProvider' => $evalProvider
		));
		$this->setRequestEval('return file_get_contents("' . __FILE__ . '");');
		$this->sendRequest();

		$this->assertMessageInResponse(array(
			'type' => 'eval_result',
			'return' => __FILE__,
			'output' => '',
		));
	}

	public function testEvalWithCustomServerEncoding() {
		$string = 'Ёпрст';
		$encoding = 'Windows-1251';
		$this->request->addScript('set_connector_encoding', array('encoding' => $encoding));
		$this->setConnectorAuth();
		$this->request->addScript('set_connector_eval_enabled');
		$this->setRequestEval("return mb_convert_encoding(mb_convert_encoding('$string', 'utf-8', '$encoding'), '$encoding', 'utf-8')");
		$this->sendRequest();
		$this->assertMessageInResponse(array(
			'type' => 'eval_result',
			'return' => $string,
		));
	}
}
