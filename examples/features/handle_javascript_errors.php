<?php

require_once(__DIR__ . '/../../src/PhpConsole/__autoload.php');

$connector = PhpConsole\Connector::getInstance(); // initialize connection with PHP Console client
$connector->setSourcesBasePath($_SERVER['DOCUMENT_ROOT']);

echo 'See JavaScript Notification popup. You can disable JavaScript errors notification in PHP Console options.';

?>

<script type="text/javascript" src="handle_javascript_errors.js"></script>
<script type="text/javascript">
	alert(undefinedVar);
</script>
