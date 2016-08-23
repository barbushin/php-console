<?php

$connector = PhpConsole\Connector::getInstance();
$connector->setSourcesBasePath(PhpConsole\Test\Remote\Test::getClientEmulator()->getScriptsDir());
