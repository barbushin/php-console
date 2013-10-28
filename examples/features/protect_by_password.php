<?php

require_once(__DIR__ . '/../../src/PhpConsole/__autoload.php');

$password = null;
if(!$password) {
	die('Please set $password variable value in ' . __FILE__);
}

$connector = PhpConsole\Connector::getInstance();
$connector->setPassword($password);
// $connector->enableSslOnlyMode(); // PHP Console clients will be always redirected to HTTPS
// $connector->setAllowedIpMasks(array('192.168.*.*'));

$connector->getDebugDispatcher()->dispatchDebug('ok');

echo !$connector->isAuthorized()
	? 'Click on PHP Console "key" icon in address bar and enter the password.'
	: 'Click on PHP Console icon in address bar to logout.';
