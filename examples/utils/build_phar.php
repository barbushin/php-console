<?php

/**
 * There is example of creating PHAR archive of PhpConsole
 * So now it can be included in your project just like: require_once('phar://path/to/PhpConsole.phar')
 *
 * @see https://github.com/barbushin/php-console
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */

define('PHP_CONSOLE_DIR', __DIR__ . '/../../src/PhpConsole');
define('PHP_CONSOLE_PHAR_FILEPATH', __DIR__ . '/../PhpConsole.phar');

require_once(PHP_CONSOLE_DIR . '/__autoload.php');
PhpConsole\Handler::getInstance()->start();

if(isset($_GET['download'])) {
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="PhpConsole.phar"');
	readfile(PHP_CONSOLE_PHAR_FILEPATH);
	exit;
}

if(!Phar::canWrite()) {
	throw new Exception('Unable to create PHAR archive, must be phar.readonly=Off option in php.ini');
}
if(!is_writable(dirname(PHP_CONSOLE_PHAR_FILEPATH))) {
	throw new Exception('Directory ' . dirname(PHP_CONSOLE_PHAR_FILEPATH) . ' must be writable');
}
if(file_exists(PHP_CONSOLE_PHAR_FILEPATH)) {
	unlink(PHP_CONSOLE_PHAR_FILEPATH);
}

$phar = new Phar(PHP_CONSOLE_PHAR_FILEPATH);
$phar = $phar->convertToExecutable(Phar::PHAR);
$phar->startBuffering();
$phar->buildFromDirectory(PHP_CONSOLE_DIR, '~' . '/[^_]\w+\.php$~');
$phar->stopBuffering();
$phar->setStub('<?php
Phar::mapPhar("PhpConsole");
spl_autoload_register(function ($class) {
	if(strpos($class, "PhpConsole") === 0) {
		require_once("phar://". str_replace("\\\\", DIRECTORY_SEPARATOR, $class) . ".php");
	}
});
__HALT_COMPILER();
');
$phar->stopBuffering();

$pharPath = realpath(PHP_CONSOLE_PHAR_FILEPATH);
$pharUri = substr($pharPath, strlen($_SERVER['DOCUMENT_ROOT']));

?>

Done. See <a href="?download"><?= $pharPath ?></a><br />
<br />
Now you can include PhpConsole to your project using: <code>require_once('phar://<?= $pharPath ?>');</code>
