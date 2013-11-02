<?php

require_once(__DIR__ . '/../../src/PhpConsole/__autoload.php');

$password = null;
if(!$password) {
	die('Please set $password variable value in ' . __FILE__);
}

$connector = PhpConsole\Helper::register();

if($connector->isActiveClient()) {
	// Init errors & exceptions handler
	$handler = PC::getHandler();
	$handler->start(); // start handling PHP errors & exceptions

	$connector->setSourcesBasePath($_SERVER['DOCUMENT_ROOT']); // so files paths on client will be shorter (optional)
	$connector->setPassword($password); // protect access by password
	// $connector->enableSslOnlyMode(); // PHP Console clients will be always redirected to HTTPS
	// $connector->setAllowedIpMasks(array('192.168.*.*'));

	// Enable eval provider
	$evalProvider = $connector->getEvalDispatcher()->getEvalProvider();
	$evalProvider->disableFileAccessByOpenBaseDir(); // means disable functions like include(), require(), file_get_contents() & etc
	$evalProvider->addSharedVar('uri', $_SERVER['REQUEST_URI']); // so you can access $_SERVER['REQUEST_URI'] just as $uri in terminal
	$evalProvider->addSharedVarReference('post', $_POST);
	/*
	 $evalProvider->setOpenBaseDirs(array(__DIR__)); // set directories limited for include(), require(), file_get_contents() & etc
	 $evalProvider->addCodeHandler(function(&$code) { // modify or validate code before execution
			$code = 'return '. $code;
	 });
	*/
	$connector->startEvalRequestsListener(); // must be called after all configurations
}

// Trigger E_WARNING error with backtrace

class ErrorTestClass {

	public function __construct() {
		$this->method1(array(1, 2), new stdClass()); // this arguments will be displayed in error backtrace
	}

	protected function method1($a, $b) {
		$this->method2('some long string argument');
	}

	public function method2($c) {
		file_get_contents('not_existed.file'); // E_WARNING error
	}
}

new ErrorTestClass();

// Trigger JavaScript error

echo '<script type="text/javascript">alert(undefinedVar);</script>';

// Debug some data

class DebugExample {

	private $privateProperty = 1;
	protected $protectedProperty = 2;
	public $publicProperty = 3;
	public $selfProperty;

	public function __construct() {
		$this->selfProperty = $this;
	}

	public function someMethod() {
	}
}

PC::debug(array(
	'null' => null,
	'boolean' => true,
	'longString' => '11111111112222222222333333333344444444445',
	'someObject' => new DebugExample(),
	'someCallback' => array(new DebugExample(), 'someMethod'),
	'someClosure' => function () {
	},
	'someResource' => fopen(__FILE__, 'r'),
	'manyItemsArray' => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11),
	'deepLevelArray' => array(1 => array(2 => array(3))),
), 'some.test');

echo !$connector->isAuthorized()
	? 'To access eval terminal you need to authorize. Click on PHP Console "key" icon in address bar and enter the password.'
	: 'See errors & debug data in JavaScript Console(Ctrl+Shift+J).<br/>Click on PHP Console terminal icon in address bar to access eval terminal and try to execute some PHP code.';