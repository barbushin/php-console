<?php

namespace PhpConsole\Test;

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);

require_once(__DIR__ . '/config.php');

getClientEmulator()->handleClientEmulatorRequest();
