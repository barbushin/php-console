<?php

require_once(__DIR__ . '/../../src/PhpConsole/__autoload.php');

$password = null;
if(!$password) {
	die('Please set $password variable value in ' . __FILE__);
}

$connector = PhpConsole\Connector::getInstance();
$connector->setPassword($password); // Eval requests listener can be started only in password protected mode
// $connector->enableSslOnlyMode(); // PHP Console clients will be always redirected to HTTPS
// $connector->setAllowedIpMasks(array('192.168.*.*'));

$evalProvider = $connector->getEvalDispatcher()->getEvalProvider();
$evalProvider->disableFileAccessByOpenBaseDir(); // means disable functions like include(), require(), file_get_contents() & etc
$evalProvider->addSharedVar('uri', $_SERVER['REQUEST_URI']); // so you can access $_SERVER['REQUEST_URI'] just as $uri in terminal
$evalProvider->addSharedVarReference('post', $_POST);
/*
 $evalProvider->setOpenBaseDirs(array(__DIR__)); // set directories limited for include(), require(), file_get_contents() & etc
 $evalProvider->addCodeHandler(function(&$code) { // modify or validate code before execution
		$code = 'return '. $code;
 });
*/

$connector->startEvalRequestsListener(); // must be called after all configurations

echo !$connector->isAuthorized()
	? 'To access eval terminal you need to authorize. Click on PHP Console "key" icon in address bar and enter the password.'
	: 'Click on PHP Console terminal icon in address bar to access eval terminal and try to execute some PHP code.';
