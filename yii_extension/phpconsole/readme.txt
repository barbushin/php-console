PhpConsoleYii extension for Yii Framework
============================================

This extension integrates YII with Google Chrome extension "PHP Console":
https://chrome.google.com/extensions/detail/nfhmhhlpfleoednkpnnnkolmclajemef

Requirements
------------

* Yii Framework project
* Install Google Chrome extension PHP Console https://chrome.google.com/extensions/detail/nfhmhhlpfleoednkpnnnkolmclajemef
* Set output_buffering setting to true in php.ini (optional)

Installation
------------

1. Download and extract the "phpconsole" folder to your extensions directory (i.e. /protected/extensions).
2. Modify your config file (i.e. /protected/config/main.php)

##### config file code 

    // ....

	'preload' => array('log'),
	
	'components' => array(

		// ...

		'log' => array(
			'class' => 'CLogRouter',
			'routes' => array(
				array(
					'class' => 'ext.phpconsole.PhpConsoleYiiExtension',
					'handleErrors' => true,
					'handleExceptions' => true,
					'basePathToStrip' => $_SERVER['DOCUMENT_ROOT']
				)
			)
		)
	) 

    // ...


Usage
-----

Try this code in some controller:

// log using Yii methods
Yii::log('There is some error', CLogger::LEVEL_ERROR, 'app,context');
Yii::log('There is some warning', CLogger::LEVEL_WARNING);
Yii::log('There is some debug message', CLogger::LEVEL_INFO);

// log using PHP Console debug method
debug('Short way to debug directly in PHP Console', 'some,debug,tags');
echo $test_E_NOTICE;
throw new Exception('There is some not catched exception');


Resources
---------

PhpConsoleYii homepage: http://www.yiiframework.com/extension/php-console
PhpConsoleYii SVN repository: https://php-console.googlecode.com/svn/trunk/yii_extension
Google Chrome extension "PHP Console": https://chrome.google.com/extensions/detail/nfhmhhlpfleoednkpnnnkolmclajemef
PhpConsole class homepage: http://code.google.com/p/php-console