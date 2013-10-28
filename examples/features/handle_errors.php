<?php

require_once(__DIR__ . '/../../src/PhpConsole/__autoload.php');

$handler = PhpConsole\Handler::getInstance();
$handler->start(); // start handling PHP errors & exceptions
$handler->getConnector()->setSourcesBasePath($_SERVER['DOCUMENT_ROOT']); // so files paths on client will be shorter (optional)

// Example of handling error with some backtrace

class ErrorTestClass {

	public function __construct() {
		$this->method1(array(1, 2), new stdClass()); // this arguments will be displayed in error backtrace
	}

	protected function method1($a, $b) {
		$this->method2('some long string argument');
	}

	public function method2($c) {
		echo $undefinedVar; // E_NOTICE error
		file_get_contents('not_existed.file'); // E_WARNING error
	}
}

new ErrorTestClass();

// Example of handling some caught exception

try {
	throw new Exception('Some caught exception');
}
catch(Exception $exception) {
	$handler->handleException($exception);
}

echo 'See errors & exceptions messages in JavaScript Console(Ctrl+Shift+J) and in Notification popups. Click on PHP Console icon in address bar to see configuration options.';

// Example of handling some custom class uncaught exception

class ExampleException extends Exception {

}

throw new ExampleException('Some uncaught exception with custom class');


