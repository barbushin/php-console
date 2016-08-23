<?php namespace PhpConsole\Test;

define('PhpConsole\Test\BASE_DIR', realpath(__DIR__ . '/..'));
define('PhpConsole\Test\TMP_DIR', BASE_DIR . '/build');

if(!is_dir(TMP_DIR)) {
	mkdir(TMP_DIR);
}

require_once BASE_DIR . "/vendor/autoload.php";

class_exists('PhpConsole\Connector'); // required to force autoload PhpConsole\Connector, to init models classes that can be used in tests before initializing PhpConsole\Connector