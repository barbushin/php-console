<?php

namespace PhpConsole\ClientEmulator;

class Response {

	public $code;
	public $cookies = array();
	public $headerData;
	public $isPostponed = false;
	public $output;
	/** @var \PhpConsole\Response */
	public $package;
}
