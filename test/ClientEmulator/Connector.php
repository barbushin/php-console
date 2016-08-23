<?php

namespace PhpConsole\ClientEmulator;

use PhpConsole\ServerAuthStatus;

class Connector {

	const POST_VAR_NAME = '__PHP_Console_emulator';
	const HEADERS_LIMIT = 90000; // default CURL headers limit

	protected $serverWrapperUrl;
	protected $secretKey;
	protected $scriptsDir;

	public function __construct($scriptsDir, $tmpDir, $serverWrapperUrl = null) {
		$this->serverWrapperUrl = $serverWrapperUrl;
		$this->secretKey = $this->initSecretKey(realpath($tmpDir));
		$this->scriptsDir = realpath($scriptsDir);
	}

	/**
	 * @return string
	 */
	public function getSecretKey(): string {
		return $this->secretKey;
	}

	protected function initSecretKey($tmpDir) {
		$filePath = $tmpDir . '/php-console_' . md5(__FILE__) . '.key';
		if(!file_exists($filePath)) {
			$key = mt_rand() . mt_rand();
			file_put_contents($filePath, $key);
		}
		else {
			$key = file_get_contents($filePath);
		}
		return $key;
	}

	public function getScriptsDir() {
		return $this->scriptsDir;
	}

	public function getPostDataSignature($rawPostData) {
		return hash('sha256', $rawPostData . $this->secretKey);
	}

	/**
	 * @param Request $request
	 * @param bool $postponedResponseId
	 * @param null $postponedOutput
	 * @return Response
	 * @throws RequestFailed
	 * @throws \Exception
	 */
	public function sendRequest(Request $request, $postponedResponseId = null, $postponedOutput = null) {
		if(!$this->serverWrapperUrl) {
			throw new \Exception('Unable to send request because server wrapper URL is not specified');
		}

		$clientData = $request->getClientData();
		if($clientData) {
			$request->cookies[\PhpConsole\Connector::CLIENT_INFO_COOKIE] = base64_encode(json_encode($clientData));
		}

		if($postponedResponseId) {
			$request->postData[\PhpConsole\Connector::POST_VAR_NAME] = array(
				'getPostponedResponse' => $postponedResponseId
			);
		}

		$request->postData[static::POST_VAR_NAME] = array(
			'scripts' => $request->getScripts(),
		);

		$postData = $request->postData;
		array_walk_recursive($postData, function (&$item) {
			if(!is_object($item)) {
				$item = base64_encode($item);
			}
		});
		$rawPostData = serialize($postData);

		$url = $request->isSsl ? str_replace('http://', 'https://', $this->serverWrapperUrl) : $this->serverWrapperUrl;

		$curlOptions = array(
			CURLOPT_URL => $url . '?signature=' . $this->getPostDataSignature($rawPostData),
			CURLOPT_CONNECTTIMEOUT => 2,
			CURLOPT_TIMEOUT => 5,
			CURLOPT_HEADER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $rawPostData,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 3,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
		);

		if($request->cookies) {
			$cookiesData = array();
			foreach($request->cookies as $name => $value) {
				$cookiesData[] = rawurlencode($name) . '=' . rawurlencode($value);
			}
			$curlOptions[CURLOPT_COOKIE] = implode('; ', $cookiesData);
		}

		$curlHandle = curl_init();
		curl_setopt_array($curlHandle, $curlOptions);
		$responseData = curl_exec($curlHandle);
		$code = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
		$error = curl_error($curlHandle);

		$responseHeaders = substr($responseData, 0, curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE));
		if(substr($responseHeaders, -1) !== "\n") { // because curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE) is bugged on some PHP versions
			$responseHeaders = substr($responseData, 0, strpos($responseData, PHP_EOL . PHP_EOL));
		}

		$responseOutput = substr($responseData, strlen($responseHeaders));
		curl_close($curlHandle);

		$response = new Response();
		$response->code = $code;
		$response->output = (string)($postponedResponseId ? $postponedOutput : $responseOutput);
		$response->headerData = $this->parseHeaderData($responseHeaders);
		$response->cookies = $this->parseCookies($responseHeaders);

		try {
			if($error || ($code != 200 && $code != 204 && $code != 500)) {
				throw new \Exception('Connection to "' . $this->serverWrapperUrl . '" failed with code "' . $code . '" and error: ' . $error . '" and response: "' . $responseOutput, $code);
			}
			$packageEncodedData = $postponedResponseId ? $responseOutput : $response->headerData;
			if($packageEncodedData) {
				$packageData = $this->jsonDecode($packageEncodedData);
				if(!empty($packageData['isPostponed'])) {
					$request->cookies = $response->cookies;
					$response = $this->sendRequest($request, $packageData['id'], $responseOutput);
					$response->isPostponed = true;
					return $response;
				}
				$response->package = $this->initResponsePackage($packageData);
			}
		}
		catch(\Exception $exception) {
			throw new RequestFailed($exception, $request, $response);
		}

		return $response;
	}

	public function getScriptPath($alias) {
		return $this->scriptsDir . DIRECTORY_SEPARATOR . basename($alias) . '.php';
	}

	public function handleClientEmulatorRequest() {
		$_POST = unserialize(file_get_contents('php://input'));
		if($_POST === false) {
			throw new \Exception('Wrong format of raw POST data');
		}
		array_walk_recursive($_POST, function (&$item) {
			if(!is_object($item)) {
				$item = base64_decode($item);
			}
		});
		if(!isset($_GET['signature']) || $this->getPostDataSignature(file_get_contents('php://input')) != $_GET['signature']) {
			throw new \Exception('Wrong request signature');
		}
		if(!empty($_POST[static::POST_VAR_NAME]['scripts'])) {
			foreach($_POST[static::POST_VAR_NAME]['scripts'] as $script) {
				$scriptPath = $this->getScriptPath($script['alias']);
				if(!is_file($scriptPath)) {
					throw new \Exception('Script with alias "' . $script['alias'] . '" not found in ' . $scriptPath);
				}
				$this->runScript($script['alias'], isset($script['params']) ? $script['params'] : array());
			}
		}
	}

	protected function runScript($_alias, $_params) {
		extract($_params, EXTR_SKIP);
		require($this->getScriptPath($_alias));
	}

	protected function parseHeaderData($headersData) {
		if(preg_match_all('/\n\s*' . preg_quote(\PhpConsole\Connector::HEADER_NAME) . '\s*:\s*(.*?)[\r\n]/', $headersData, $m)) {
			if(count($m[1]) > 1) {
				throw new \Exception('There is more than one "' . \PhpConsole\Connector::HEADER_NAME . '" header');
			}
			return rawurldecode($m[1][0]);
		}
		elseif(preg_match_all('/\n\s*' . preg_quote(\PhpConsole\Connector::POSTPONE_HEADER_NAME) . '\s*:\s*(.*?)[\r\n]/', $headersData, $m)) {
			if(count($m[1]) > 1) {
				throw new \Exception('There is more than one "' . \PhpConsole\Connector::POSTPONE_HEADER_NAME . '" header');
			}
			return rawurldecode($m[1][0]);
		}
	}

	protected function parseCookies($headersData) {
		$cookies = array();
		preg_match_all('/Set-Cookie:\s*(.*?)=(.*?);/i', $headersData, $m);
		foreach($m[1] as $i => $name) {
			if($m[2][$i] != 'deleted') {
				$cookies[$name] = rawurldecode($m[2][$i]);
			}
		}
		return $cookies;
	}

	/**
	 *
	 * @param array $packageData
	 * @throws \Exception
	 * @return Response|null
	 */
	protected function initResponsePackage(array $packageData) {
		$package = new \PhpConsole\Response($packageData);
		if($package->auth) {
			$package->auth = new ServerAuthStatus($package->auth);
		}
		return $package;
	}

	protected function jsonDecode($json) {
		$data = @json_decode($json, true);
		if(!$data || json_last_error()) {
			throw new \Exception('Decoding json failed with error code ' . json_last_error() . '. JSON: ' . $json);
		}
		return $data;
	}
}

class RequestFailed extends \Exception {

	public $request;
	public $response;

	function __construct(\Exception $previous, Request $request, Response $response) {
		$this->request = $request;
		$this->response = $response;
		parent::__construct('ClientEmulator request failed with error: ' . $previous->getMessage(), 0, $previous);
	}
}
