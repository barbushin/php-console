<?php

$exitOnEval = isset($exitOnEval) ? $exitOnEval : true;
$flushDebugMessages = isset($flushDebugMessages) ? $flushDebugMessages : true;

$connector = PhpConsole\Connector::getInstance();

if(isset($evalProvider)) {
	$connector->getEvalDispatcher()->setEvalProvider($evalProvider);
}
$connector->startEvalRequestsListener($exitOnEval, $flushDebugMessages);

