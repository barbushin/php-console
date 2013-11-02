<?php

require_once(__DIR__ . '/../../src/PhpConsole/__autoload.php');

if(PhpConsole\Connector::getInstance()->isActiveClient()) {
	// ... any PHP Console initialization & configuration code
}

// if you're calling PC::debug() or any other PC class methods in your code, so PhpConsole\Helper::register() must be called anyway
PhpConsole\Helper::register();

echo 'If you want to make PHP Console initialization more lightweight on your server, then you should execute initialization code only if <code>PhpConsole\Connector->getInstance()->isActiveClient()</code> returns <code>true</code>';
