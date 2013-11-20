<?php

namespace PhpConsole\Test\Remote;

abstract class Test extends \PhpConsole\Test\Test {

	/** @var \PhpConsole\ClientEmulator\Connector */
	protected $clientEmulator;

	/** @var \PhpConsole\ClientEmulator\Request */
	protected $request;

	/** @var \PhpConsole\ClientEmulator\Response|null */
	protected $response;

	/** @var bool|null */
	protected $randomOutputMustPresentInResponse = true;
	/** @var bool|null */
	protected $uniqueTestString;

	protected $beforeRequestSendCallbacks = array();
	protected $afterRequestSendCallbacks = array();
	protected $notHandledErrorsInResponse;

	protected function setUpRequestDefaults() {
		$this->request->setClientData(new \PhpConsole\Client(array(
			'protocol' => \PhpConsole\Connector::SERVER_PROTOCOL
		)));
		$this->setUpConnector();
		$this->addRandomMessageToRequest();
	}

	protected function setUpConnector() {
		$this->request->addScript('init_default_connector');
	}

	protected function beforeRequestSend() {
		$this->addRandomOutputToRequest();
	}

	protected function afterRequestSent() {
		if(!$this->response) {
			throw new \Exception('Request was not sent');
		}
		if($this->randomOutputMustPresentInResponse !== null) {
			$this->assertRandomOutputInResponse($this->randomOutputMustPresentInResponse);
		}
		$this->notHandledErrorsInResponse = count($this->findMessagesInResponse(array(
			'type' => 'error'
		)));
	}

	protected function tearDown() {
		parent::tearDown();
		$this->assertEquals(0, $this->notHandledErrorsInResponse);
	}

	public function assertMessageInResponse(array $propertiesValue, $onlyOne = true) {
		$this->assertNotEmpty($onlyOne ? $this->findMessageInResponse($propertiesValue) : $this->findMessagesInResponse($propertiesValue));
	}

	public function addRandomOutputToRequest() {
		$this->request->addScript('print', array(
			'string' => $this->uniqueTestString
		));
	}

	public function assertRandomOutputInResponse($isPresent = true) {
		if($isPresent) {
			if($this->randomOutputMustPresentInResponse) {
				$this->assertEquals($this->uniqueTestString, $this->response->output);
			}
			else {
				$this->assertNotContains($this->uniqueTestString, $this->response->output);
			}
		}
	}

	protected function addRandomMessageToRequest() {
		$this->request->addScript('dispatch_debug', array(
			'data' => $this->uniqueTestString,
			'tags' => 'test' . $this->uniqueTestString,
		));
	}

	public function assertRandomMessageInResponse($isPresent = true) {
		$message = $this->findMessageInResponse(array(
			'data' => $this->uniqueTestString,
			'tags' => 'test' . $this->uniqueTestString,
		));
		if($isPresent) {
			$this->assertNotEmpty($message);
		}
		else {
			$this->assertEmpty($message);
		}
	}

	protected final function sendRequest() {
		$this->beforeRequestSend();
		$this->response = $this->clientEmulator->sendRequest($this->request);
		$this->afterRequestSent();
		return $this->response;
	}

	protected function setUp() {
		$this->notHandledErrorsInResponse = 0;
		$this->clientEmulator = \PhpConsole\Test\getClientEmulator();
		$this->request = new \PhpConsole\ClientEmulator\Request();
		$this->uniqueTestString = mt_rand() . mt_rand();
		$this->setUpRequestDefaults();
	}

	protected function onNotSuccessfulTest(\Exception $exception) {
		$request = $this->request;
		$response = $this->response;
		if($exception instanceof \PhpConsole\ClientEmulator\RequestFailed) {
			$request = $exception->response;
			$response = $exception->response;
		}
		print_r($response);
		print_r($request);
		parent::onNotSuccessfulTest($exception);
	}

	protected function getAuthPublicKey($secretKey = \PhpConsole\Test\SERVER_KEY, $publicKeyByIp = true, $clientIp = \PhpConsole\Test\LOCAL_IP) {
		$request = new \PhpConsole\ClientEmulator\Request();
		$request->setClientData(new \PhpConsole\Client(array(
			'protocol' => \PhpConsole\Connector::SERVER_PROTOCOL
		)));
		$request->addScript('init_default_connector');
		$this->setConnectorAuth($secretKey, $publicKeyByIp, $clientIp, $request);
		$response = $this->clientEmulator->sendRequest($request);
		return $response->package->auth->publicKey;
	}

	public function setRequestAuth($publicKey = null, $password = \PhpConsole\Test\SERVER_KEY, $publicKeyByIp = true, $clientIp = \PhpConsole\Test\LOCAL_IP) {
		$auth = new \PhpConsole\Auth($password, $publicKeyByIp);
		$_SERVER['REMOTE_ADDR'] = $clientIp;
		$this->request->setClientData(new \PhpConsole\Client(array(
			'protocol' => \PhpConsole\Connector::SERVER_PROTOCOL,
			'auth' => new \PhpConsole\ClientAuth(array(
				'publicKey' => $publicKey ? : $this->getAuthPublicKey($password, $publicKeyByIp, $clientIp),
				'token' => $this->callProtectedMethod($auth, 'getToken')
			))
		)));
		return $auth;
	}

	protected function setConnectorAuth($password = \PhpConsole\Test\SERVER_KEY, $publicKeyByIp = true, $clientIp = \PhpConsole\Test\LOCAL_IP, \PhpConsole\ClientEmulator\Request $request = null) {
		$request = $request ? : $this->request;
		$request->addScript('set_connector_auth', array(
			'password' => $password,
			'publicKeyByIp' => $publicKeyByIp,
			'clientIp' => $clientIp,
		));
	}

	public function findMessagesInResponse(array $propertiesValues) {
		if(isset($propertiesValues['tags'])) {
			$propertiesValues['tags'] = explode('.', $propertiesValues['tags']);
		}
		$messages = array();
		if($this->response->package) {
			foreach($this->response->package->messages as $message) {
				$isMatch = true;
				foreach($propertiesValues as $property => $value) {
					if(!array_key_exists($property, $message) || $message[$property] !== $value) {
						$isMatch = false;
						break;
					}
				}
				if($isMatch) {
					if($message['type'] == 'error') {
						$this->notHandledErrorsInResponse--;
					}
					$messages[] = $message;
				}
			}
		}
		return $messages;
	}

	/**
	 * @param array $propertiesValues
	 * @return null|\PhpConsole\ErrorMessage|\PhpConsole\DebugMessage|\PhpConsole\EvalResultMessage
	 * @throws \Exception
	 */
	public function findMessageInResponse(array $propertiesValues) {
		$messages = $this->findMessagesInResponse($propertiesValues);
		if($messages) {
			if(count($messages) > 1) {
				throw new \Exception('There is more than one matched messages');
			}
			return reset($messages);
		}
	}

	function convertEncoding($string, $toEncoding, $fromEncoding) {
		if(extension_loaded('mbstring')) {
			return mb_convert_encoding($string, $toEncoding, $fromEncoding);
		}
		else {
			return iconv($fromEncoding, $toEncoding, $string);
		}
	}
}
