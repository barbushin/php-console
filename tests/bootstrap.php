<?php

require_once(__DIR__ . '/config.php');

class_exists('PhpConsole\Connector'); // required to force autoload PhpConsole\Connector, to init models classes that can be used in tests before initializing PhpConsole\Connector
