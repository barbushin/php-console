<?php

require_once(__DIR__ . '/../../src/PhpConsole/__autoload.php');

PhpConsole\Connector::getInstance(); // initialize connection with PHP Console client

echo 'See JavaScript Notification popup. You can disable JavaScript errors notification in PHP Console options.';

?>

<script type="text/javascript">
	alert(undefinedVar);
</script>
