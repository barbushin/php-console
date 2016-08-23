<?php

namespace PhpConsole\Test\Remote;

use PhpConsole\Client;
use PhpConsole\Connector;

class ConnectorTest extends Test {

	public function testServerCookieSet() {
		$this->sendRequest();
		$this->assertTrue(isset($this->response->cookies[Connector::SERVER_COOKIE]));
		$this->assertEquals(Connector::SERVER_PROTOCOL, $this->response->cookies[Connector::SERVER_COOKIE]);
	}

	public static function provideDifferentProtocolVersions() {
		return static::getOneArgProviderData(array(
			Connector::SERVER_PROTOCOL - 1,
			Connector::SERVER_PROTOCOL + 1,
			'x'
		));
	}

	/**
	 * @dataProvider provideDifferentProtocolVersions
	 * @param $protocolVersion
	 */
	public function testServerWorksWithDifferentClientVersions($protocolVersion) {
		$this->request->setClientData(new Client(array(
			'protocol' => $protocolVersion
		)));
		$this->sendRequest();
	}

	public function testNotPostponeIfHeadersLimitNotExceeded() {
		$this->request->addScript('dispatch_debug', array(
			'data' => 'asd'
		));
		$this->sendRequest();
		$this->assertFalse($this->response->isPostponed);
	}

	public function testPostponeIfHeadersLimitExceeded() {
		$headersLimit = \PhpConsole\ClientEmulator\Connector::HEADERS_LIMIT;
		$messageSizeLimit = Connector::getInstance()->getDumper()->itemSizeLimit;
		for($size = 0; $size < $headersLimit; $size += $messageSizeLimit) {
			$this->request->addScript('dispatch_debug', array(
				'data' => str_repeat('x', $messageSizeLimit)
			));
		}
		$this->sendRequest();
		$this->assertTrue($this->response->isPostponed);
		$this->assertMessageInResponse(array(
			'data' => str_repeat('x', $messageSizeLimit)
		), false);
	}

	public function testStringEncoding() {
		$string = 'Ёпрст';
		$encoding = 'Windows-1251';
		$encodedString = $this->convertEncoding($string, $encoding, 'utf-8');
		$this->request->addScript('set_connector_encoding', array('encoding' => $encoding));
		$this->request->addScript('dispatch_debug', array('data' => $encodedString));
		$this->sendRequest();
		$this->assertNotEmpty($this->findMessageInResponse(array('data' => $string)));
	}

	public function testArrayStringEncodingConvert() {
		$string = 'Ёпрст';
		$encoding = 'Windows-1251';
		$encodedData = array($this->convertEncoding($string, $encoding, 'utf-8'));
		$this->request->addScript('set_connector_encoding', array('encoding' => $encoding));
		$this->request->addScript('dispatch_debug', array('data' => $encodedData));
		$this->sendRequest();
		$this->assertNotEmpty($this->findMessageInResponse(array('data' => array($string))));
	}

	public function testSourcesBasePathInResponse() {
		$this->sendRequest();
		$this->assertNotEmpty($this->response->package->sourcesBasePath);
	}

	public function testGetBackDataInResponse() {
		$data = array(1, 2, 3);
		$this->request->postData[Connector::POST_VAR_NAME]['getBackData'] = $data;
		$this->sendRequest();
		$this->assertEquals($data, $this->response->package->getBackData);
	}

	public function testDocRootInResponse() {
		$this->sendRequest();
		$this->assertNotEmpty($this->response->package->docRoot);
		$this->assertContains(realpath($this->response->package->docRoot), \PhpConsole\Test\BASE_DIR);
	}

	public function testIsLocalInResponse() {
		$this->sendRequest();
		$this->assertTrue($this->response->package->isLocal);
	}

	/**
	 * @group ssl
	 */
	public function testSslOnlyNoDataByHttp() {
		$this->request->addScript('set_connector_ssl_only');
		$this->sendRequest();
		$this->assertRandomMessageInResponse(false);
		$this->assertContainsRecursive(array(
			'protocol' => 5,
			'auth' => null,
			'docRoot' => null,
			'sourcesBasePath' => null,
			'getBackData' => null,
			'isSslOnlyMode' => true,
			'isEvalEnabled' => false,
			'messages' => array()), $this->response->package);
	}

	/**
	 * @group ssl
	 */
	public function testSslOnlyDataByHttps() {
		$this->request->addScript('set_connector_ssl_only');
		$this->request->isSsl = true;
		$this->sendRequest();
		$this->assertRandomMessageInResponse(true);
	}

	public static function provideSetAllowedIpMasksArgs() {
		return array(
			// IPv4
			array('10.0.0.1', '10.0.0.1', true),
			array('10.0.0.1', '10.0.0.2', false),
			array('10.0.0.1', null, false),
			array('10.0.0.*', '10.0.0.2', true),
			array('10.0.0.*', '10.0.1.2', false),
			array('10.0.*.*', '10.0.1.2', true),
			array(array('10.0.*.*', '11.0.*.*'), '10.0.1.2', true),
			array(array('10.0.*.*', '12.0.*.*'), '13.0.1.2', false),
			// IPv6
			array('2001:0:5ef5:79fb:28ac:173b:dad2:96e0', '2001:0:5ef5:79fb:28ac:173b:dad2:96e0', true),
			array('2001:0:5ef5:79fb:28ac:173b:dad2:96e0', '2001:0:5ef5:79fb:28ac:173b:dad2:96e1', false),
			array('2001:0:5ef5:79fb:28ac:173b:dad2:96e0', null, false),
			array('2001:0:5ef5:79fb:28ac:173b:dad2:*', '2001:0:5ef5:79fb:28ac:173b:dad2:96e0', true),
			array('2001:0:5ef5:79fb:28ac:173b:dad2:*', '2001:0:5ef5:79fb:28ac:173b:dad3:96e0', false),
			array('2001:0:5ef5:79fb:28ac:173b:*:*', '2001:0:5ef5:79fb:28ac:173b:dad2:96e0', true),
			array(array('2001:0:5ef5:79fb:28ac:173b:*:*', '2002:0:5ef5:79fb:28ac:173b:*:*'), '2001:0:5ef5:79fb:28ac:173b:dad3:96e0', true),
			array(array('2001:0:5ef5:79fb:28ac:173b:*:*', '2002:0:5ef5:79fb:28ac:173b:*:*'), '2003:0:5ef5:79fb:28ac:173b:dad3:96e0', false),
		);
	}

	/**
	 * @dataProvider provideSetAllowedIpMasksArgs
	 * @param $clientIp
	 * @param $allowedIpMask
	 * @param $expectIsAllowed
	 */
	public function testSetAllowedIpMasks($allowedIpMask, $clientIp, $expectIsAllowed) {
		$this->request->addScript('set_connector_allowed_ip', array(
			'clientIp' => $clientIp,
			'ipMasks' => is_array($allowedIpMask) ? $allowedIpMask : array($allowedIpMask)
		));
		$this->sendRequest();
		$this->assertRandomMessageInResponse($expectIsAllowed);
	}
}
