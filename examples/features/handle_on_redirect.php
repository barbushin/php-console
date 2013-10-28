<?php

require_once(__DIR__ . '/../../src/PhpConsole/__autoload.php');

$handler = PhpConsole\Handler::getInstance();
$handler->start();
$handler->getConnector()->setSourcesBasePath($_SERVER['DOCUMENT_ROOT']);

$redirectNum = isset($_GET['num']) ? $_GET['num'] + 1 : 1;

if($redirectNum < 4) {
	if($redirectNum == 2) {
		echo ${'oops' . $redirectNum};
	}
	$handler->debug('Debug message in redirect №' . $redirectNum);
	header('Location: ?num=' . $redirectNum);
}
else {
	$handler->debug('Debug message in current page');
	echo '
		There was 3 redirects before this page is displayed, and you can see all handled error & debug messages in collapsed blocks in JavaScript Console(Ctrl+Shift+J).
		If there was a error in redirected page, so console block will be expanded.
	';
}

