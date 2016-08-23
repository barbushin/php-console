<?php

namespace PhpConsole\Test\Remote;

use PhpConsole\Auth;
use PhpConsole\Client;
use PhpConsole\ClientAuth;
use PhpConsole\ClientEmulator\Connector;
use PhpConsole\ClientEmulator\Request;
use PhpConsole\ClientEmulator\RequestFailed;

abstract class Test extends \PhpConsole\Test\Test {

	/** @var Connector */
	protected $clientEmulator;

	/** @var Request */
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

	/**
	 * @return Connector
	 */
	public static function getClientEmulator() {
		static $client;
		if(!$client) {
			$client = new Connector(__DIR__ . '/scripts', \PhpConsole\Test\TMP_DIR, isset($_ENV['PC_TEST_SERVER']) ? $_ENV['PC_TEST_SERVER'] : null);
		}
		return $client;
	}

	protected function setUpRequestDefaults() {
		$this->request->setClientData(new Client(array(
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
		$this->clientEmulator = static::getClientEmulator();
		$this->request = new Request();
		$this->uniqueTestString = mt_rand() . mt_rand();
		$this->setUpRequestDefaults();
	}

	protected function onNotSuccessfulTest(\Exception $exception) {
		$request = $this->request;
		$response = $this->response;
		if($exception instanceof RequestFailed) {
			$request = $exception->response;
			$response = $exception->response;
		}
		print_r($response);
		print_r($request);
		parent::onNotSuccessfulTest($exception);
	}

	protected function getAuthPublicKey($secretKey = null, $publicKeyByIp = true, $clientIp = '127.0.0.1') {
		$secretKey = $secretKey ? $secretKey : static::getClientEmulator()->getSecretKey();
		$request = new Request();
		$request->setClientData(new Client(array(
			'protocol' => \PhpConsole\Connector::SERVER_PROTOCOL
		)));
		$request->addScript('init_default_connector');
		$this->setConnectorAuth($secretKey, $publicKeyByIp, $clientIp, $request);
		$response = $this->clientEmulator->sendRequest($request);
		return $response->package->auth->publicKey;
	}

	public function setRequestAuth($publicKey = null, $secretKey = null, $publicKeyByIp = true, $clientIp = '127.0.0.1') {
		$secretKey = $secretKey ? $secretKey : static::getClientEmulator()->getSecretKey();
		$auth = new Auth($secretKey, $publicKeyByIp);
		$_SERVER['REMOTE_ADDR'] = $clientIp;
		$this->request->setClientData(new Client(array(
			'protocol' => \PhpConsole\Connector::SERVER_PROTOCOL,
			'auth' => new ClientAuth(array(
				'publicKey' => $publicKey ?: $this->getAuthPublicKey($secretKey, $publicKeyByIp, $clientIp),
				'token' => $this->callProtectedMethod($auth, 'getToken')
			))
		)));
		return $auth;
	}

	protected function setConnectorAuth($password = null, $publicKeyByIp = true, $clientIp = '127.0.0.1', Request $request = null) {
		if($password === null) {
			$password = static::getClientEmulator()->getSecretKey();
		}
		$request = $request ?: $this->request;
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

	protected function convertEncoding($string, $toEncoding, $fromEncoding) {
		if(extension_loaded('mbstring')) {
			return mb_convert_encoding($string, $toEncoding, $fromEncoding);
		}
		else {
			return iconv($fromEncoding, $toEncoding, $string);
		}
	}
}
