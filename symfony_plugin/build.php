<?php

$name = 'stPhpConsolePlugin';
$summary = 'Display PHP errors/debugs in Google Chrome console or by notification popups.';
$description = 'The stPhpConsolePlugin integrates Symfony Framework with Google Chrome extension "PHP Console" and PhpConsole class.

PhpConsole catches all kind of errors/exceptions/debug messages and sends them to Google Chrome extension PHP Console,
that displays them in Google Chrome console or by notification popups.';

$version = '1.0.1';
$state = 'stable';
$packageXmlFilename = 'package.xml';
$packageFilepath = 'packages/' . $name . '-' . $version . '.tgz';

$files = array(
	'README',
	'LICENSE',
	'PhpConsole\example.php',
	'PhpConsole\PhpConsole.php',
	'PhpConsole\changelog.txt',
	'PhpConsole\license.txt',
	'PhpConsole\readme.txt',
	'config\php_console.yml',
	'config\stPhpConsolePluginConfiguration.class.php');

$filesLines = array();
foreach($files as &$file) {
	$filePath = $name . '/' . $file;
	$filesLines[] = '<file md5sum="' . md5(file_get_contents($filePath)) . '" name="' . str_replace('\\', '/', $file) . '" role="data"/>';
	$file = $filePath;
}

$packageData = '<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://pear.php.net/dtd/package-2.0" xmlns:tasks="http://pear.php.net/dtd/tasks-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" packagerversion="1.9.1" version="2.0" xsi:schemaLocation="http://pear.php.net/dtd/tasks-1.0 http://pear.php.net/dtd/tasks-1.0.xsd http://pear.php.net/dtd/package-2.0 http://pear.php.net/dtd/package-2.0.xsd">
 <name>' . $name . '</name>
 <channel>plugins.symfony-project.org</channel>
 <summary>' . $summary . '</summary>
 <description>' . $description . '</description>
 <lead>
  <name>Sergey Barbushin</name>
  <user>barbushin</user>
  <email>barbushin@gmail.com</email>
  <active>yes</active>
 </lead>
 <date>' . date('Y-m-d') . '</date>
 <time>' . date('H:i:s') . '</time>
 <version>
  <release>' . $version . '</release>
  <api>' . $version . '</api>
 </version>
 <stability>
  <release>' . $state . '</release>
  <api>' . $state . '</api>
 </stability>
 <license uri="http://www.symfony-project.org/license">MIT license</license>
 <notes>-</notes>
 <contents>
  <dir name="/">
   ' . implode("\n   ", $filesLines) . '
  </dir>
 </contents>
 <dependencies>
  <required>
   <php>
    <min>5.2.4</min>
   </php>
   <pearinstaller>
    <min>1.4.1</min>
   </pearinstaller>
  </required>
 </dependencies>
 <phprelease/>
</package>';

require_once 'Archive\Tar.php';

if(is_file($packageFilepath)) {
	unlink($packageFilepath);
}
$tar = new Archive_Tar($packageFilepath, 'gz');
$tar->addModify($files, $name . '-' . $version, $name);

file_put_contents($packageXmlFilename, $packageData);
$tar->add($packageXmlFilename);
unlink($packageXmlFilename);

echo 'done';
