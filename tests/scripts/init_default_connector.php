<?php

$connector = PhpConsole\Connector::getInstance();
$connector->setSourcesBasePath(PhpConsole\Test\getClientEmulator()->getScriptsBaseDir());
