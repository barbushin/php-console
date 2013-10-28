<?php

$connector = PhpConsole\Connector::getInstance();

if(isset($evalProvider)) {
	$connector->getEvalDispatcher()->setEvalProvider($evalProvider);
}
$connector->startEvalRequestsListener();

