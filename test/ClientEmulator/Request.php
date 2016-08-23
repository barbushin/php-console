<?php

namespace PhpConsole\ClientEmulator;

use PhpConsole\Client;

class Request {

	public $isSsl = false;
	public $cookies = array();
	public $postData = array();

	/** @var Client|null */
	protected $clientData;
	protected $scripts = array();

	public function setClientData(Client $clientData) {
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
