<?php

if(extension_loaded('mbstring')) {
	ini_set('mbstring.internal_encoding', $encoding);
	ini_set('mbstring.http_output', $encoding);
	ini_set('mbstring.func_overload', 2);
}

PhpConsole\Connector::getInstance()->setServerEncoding($encoding);
