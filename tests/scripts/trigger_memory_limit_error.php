<?php

ini_set('memory_limit', '5M');

$step = 100;
$max = 10000000;
$bigStr = '';
$appendStr = str_repeat('x', $step);

for($i = 0; $i < $max; $i += $step) {
	$bigStr .= $appendStr;
}
