<?php

if(extension_loaded('mbstring')) {
	if(version_compare(phpversion(), '5.6.0', '>')) {
		ini_set('mbstring.default_charset', $encoding);
	}
	else {
		ini_set('mbstring.internal_encoding', $encoding);
		ini_set('mbstring.http_output', $encoding);
	}
	ini_set('mbstring.func_overload', 2);
}

PhpConsole\Connector::getInstance()->setServerEncoding($encoding);
