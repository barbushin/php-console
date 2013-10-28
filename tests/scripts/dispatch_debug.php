<?php

PhpConsole\Connector::getInstance()->getDebugDispatcher()->dispatchDebug($data
	, isset($tags) ? $tags : null
	, !empty($withTraceAndSource)
	, isset($skipTraceCalls) ? $skipTraceCalls : 1);
