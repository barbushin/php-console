<?php

namespace PhpConsole\ClientEmulator;

class Request {

	public $isSsl = false;
	public $cookies = array();
	public $postData = array();

	/** @var \PhpConsole\Client|null */
	protected $clientData;
	protected $scripts = array();

	public function setClientData(\PhpConsole\Client $clientData) {
		$this->clientData = $clientData;
	}

	public function getClientData() {
		return $this->clientData;
	}

	public function addScript($alias, array $params = array()) {
		$this->scripts[] = array(
			'alias' => $alias,
			'params' => $params
		);
	}

	public function getScripts() {
		return $this->scripts;
	}
}
