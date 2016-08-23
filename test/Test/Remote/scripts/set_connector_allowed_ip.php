<?php

$_SERVER['REMOTE_ADDR'] = $clientIp;
PhpConsole\Connector::getInstance()->setAllowedIpMasks($ipMasks);
