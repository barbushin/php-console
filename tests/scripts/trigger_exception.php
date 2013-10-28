<?php

$triggerException = function () use ($message, $code) {
	throw new Exception($message, $code);
};

$triggerException();
