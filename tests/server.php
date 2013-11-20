<?php

namespace PhpConsole\Test;

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);

require_once(__DIR__ . '/config.php');

if(!EXTERNAL_CONNECTIONS_ALLOWED && (!isset($_SERVER['REMOTE_ADDR']) || $_SERVER['REMOTE_ADDR'] != LOCAL_IP)) {
	throw new \Exception('Connection to test server allowed only from local IP');
}

getClientEmulator()->handleClientEmulatorRequest();
