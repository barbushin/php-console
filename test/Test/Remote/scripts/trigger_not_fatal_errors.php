<?php


$x = function () {
	echo $x;
	file_get_contents('/not-exists');
};
$x();
