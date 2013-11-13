<?php

/**
 * @desc This script is required to detect actual headers size limit for you Web Server & browser
 * @see https://github.com/barbushin/php-console
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */

if(isset($_GET['size'])) {
	header('X: ' . str_repeat('x', $_GET['size']));
	die('ok');
}

echo 'Server headers limit detection takes less then one minute. Please wait... ';
flush();

set_time_limit(1000);

$size = 5000;
$testDiff = $size;
$testLimit = 1000000;

do {
	$isOk = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '?size=' . ($size + 1)) == 'ok';
	$testDiff = floor($isOk ? $testDiff * 2 : $testDiff / 2);
	$size += $testDiff * ($isOk ? 1 : -1);
}
while($testDiff && !($size > $testLimit && $isOk));

?>

Done.<br />
Your server headers limit is <?= $size ?> bytes.<br />
<br />
Use it to configure PHP Console: <code>PhpConsole\Connector::getInstance()->setHeadersLimit(<?= $size ?>);</code>


