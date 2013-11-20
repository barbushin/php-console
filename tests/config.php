<?php

namespace PhpConsole\Test;

// Configure it up to your server
const SERVER_URL = 'http://localhost/php-console/tests/server.php'; // URL path to __DIR__ . '/server.php'
const SERVER_KEY = null; // some very very unique password :)

// leave it as is
const LOCAL_IP = '127.0.0.1'; // local client IP
const EXTERNAL_CONNECTIONS_ALLOWED = false; // allow SERVER_URL to be available only from local IP
const BASE_DIR = __DIR__;

if(!SERVER_URL || !SERVER_KEY) {
	throw new \Exception('SERVER_URL & SERVER_KEY constants must be specified. See ' . __FILE__);
}

/**
 * @return \PhpConsole\ClientEmulator\Connector
 */
function getClientEmulator() {
	static $clientEmulator;
	if(!$clientEmulator) {
		$clientEmulator = new \PhpConsole\ClientEmulator\Connector(SERVER_URL, SERVER_KEY, BASE_DIR . '/scripts');
	}
	return $clientEmulator;
}

spl_autoload_register(function ($class) {
	if(preg_match('~^PhpConsole\\\\((Test|ClientEmulator)\\\\.+)$~', $class, $m)) {
		/** @noinspection PhpIncludeInspection */
		require_once(__DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', '/', $m[1]) . '.php');
	}
});

if(!class_exists('PHPUnit_Framework_TestCase')) {
	require_once(__DIR__ . '/vendor/autoload.php');
}

require_once(__DIR__ . '/../src/PhpConsole/__autoload.php');
