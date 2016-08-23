<?php

namespace PhpConsole\Test\Remote;

class AuthTest extends Test {

	public function assertFailedAuthInResponse() {
		$this->assertFalse($this->response->package->auth->isSuccess);
	}

	public function assertSuccessAuthInResponse() {
		$this->assertTrue($this->response->package->auth->isSuccess);
	}

	public function testAuthExistInResponsePackage() {
		$this->setConnectorAuth();
		$this->sendRequest();
		$this->assertFailedAuthInResponse();
	}

	public function testNoMessagesInFailedAuth() {
		$this->setConnectorAuth();
		$this->sendRequest();
		$this->assertFailedAuthInResponse();
		$this->assertRandomMessageInResponse(false);
	}

	public function testAuthFailsOnWrongToken() {
		$this->setConnectorAuth();
		$this->setRequestAuth(null, 'oops');
		$this->sendRequest();
		$this->assertFailedAuthInResponse();
		$this->assertRandomMessageInResponse(false);
	}

	public function testAuthFailsOnWrongPublicKey() {
		$this->setConnectorAuth();
		$this->setRequestAuth('oops');
		$this->sendRequest();
		$this->assertFailedAuthInResponse();
		$this->assertRandomMessageInResponse(false);
	}

	public function testAuthFailsOnWrongIp() {
		$this->setConnectorAuth();
		$this->setRequestAuth(null, static::getClientEmulator()->getSecretKey(), true, '1.1.1.1');
		$this->sendRequest();
		$this->assertFailedAuthInResponse();
		$this->assertRandomMessageInResponse(false);
	}

	public function testAuthSuccessOnNotPublicKeyByIpWithWrongIp() {
		$secretKey = static::getClientEmulator()->getSecretKey();
		$this->setConnectorAuth($secretKey, false);
		$this->setRequestAuth(null, $secretKey, false, '1.1.1.1');
		$this->sendRequest();
		$this->assertSuccessAuthInResponse();
		$this->assertRandomMessageInResponse();
	}

	public function testSuccessAuth() {
		$this->setConnectorAuth();
		$this->setRequestAuth();
		$this->sendRequest();
		$this->assertSuccessAuthInResponse();
		$this->assertRandomMessageInResponse();
	}

	public function testAuthWithPasswordInCustomServerEncoding() {
		if(!extension_loaded('mbstring')) {
			$this->markTestSkipped('There is strange bug using iconv in this test');
			return;
		}
		$password = 'Ёпрст';
		$encoding = 'Windows-1251';
		$encodedPassword = $this->convertEncoding($password, $encoding, 'utf-8');
		$this->request->addScript('set_connector_encoding', array('encoding' => $encoding));
		$this->setConnectorAuth($encodedPassword);
		$this->setRequestAuth(null, $password);
		$this->sendRequest();
		$this->assertSuccessAuthInResponse();
		$this->assertRandomMessageInResponse();
	}
}
