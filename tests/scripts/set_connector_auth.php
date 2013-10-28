<?php

$_SERVER['REMOTE_ADDR'] = $clientIp;
PhpConsole\Connector::getInstance()->setPassword($password, $publicKeyByIp);
