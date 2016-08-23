<?php

PhpConsole\Connector::getInstance()->getDebugDispatcher()->dispatchDebug($data
	, isset($tags) ? $tags : null
	, isset($skipTraceCalls) ? $skipTraceCalls : 1);
