<?php

namespace PhpConsole\Test;

// Set actual to your server values
const SERVER_URL = ''; // URL path to __DIR__ . '/server.php'
const SERVER_KEY = ''; // some random string like kudhu1h3918da

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

$composerAutoloadPath = __DIR__ . '/vendor/autoload.php';
if(!file_exists($composerAutoloadPath)) {
	throw new \Exception('Test vendors not found. Run `composer install` in /tests directory');
}

require_once($composerAutoloadPath);
require_once(__DIR__ . '/../src/PhpConsole/__autoload.php');
