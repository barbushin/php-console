<?php

/**
 *
 * @package stPhpConsolePlugin
 * @subpackage config
 * @author Barbushin Sergey <barbushin@gmail.com>
 *
 * @desc Sending messages to Google Chrome console
 *
 * To use this plugin you need to install Google Chrome extension "PHP Console":
 * https://chrome.google.com/extensions/detail/nfhmhhlpfleoednkpnnnkolmclajemef
 *
 */
class stPhpConsolePluginConfiguration extends sfPluginConfiguration {

	const CONFIG_PATH = 'config/php_console.yml';

	protected $enabled = true;
	protected $callOldErrorHandler = true;
	protected $callOldExceptionsHandler = true;
	protected $handleErrors = true;
	protected $handleExceptions = true;
	protected $logEventsNames = array();
	protected $basePathToStrip;

	public function initialize() {
		$config = sfDefineEnvironmentConfigHandler::getConfiguration($this->configuration->getConfigPaths(self::CONFIG_PATH));
		foreach($config as $option => $value) {
			$this->$option = $value;
		}
		if($this->enabled) {
			if(!isset($this->basePathToStrip)) {
				$this->basePathToStrip = dirname($_SERVER['DOCUMENT_ROOT']);
			}
			$this->initPhpConsole();
			$this->initDispatcher();
		}
	}

	protected function initPhpConsole() {
		require_once(dirname(dirname(__FILE__)) . '/PhpConsole/PhpConsole.php');
		PhpConsole::$callOldErrorHandler = $this->callOldErrorHandler;
		PhpConsole::$callOldErrorHandler = $this->callOldExceptionsHandler;
		PhpConsole::start($this->handleErrors, $this->handleExceptions, $this->basePathToStrip);
	}

	protected function initDispatcher() {
		if($this->handleExceptions) {
			$this->dispatcher->connect('application.throw_exception', array($this, 'handleExceptionEvent'));
		}
		if($this->logEventsNames) {
			foreach($this->logEventsNames as $eventName) {
				$this->dispatcher->connect($eventName, array($this, 'handleEvent'));
			}
		}
	}

	public function handleExceptionEvent(sfEvent $event) {
		$exception = $event->getSubject();
		if($exception instanceof Exception) {
			PhpConsole::getInstance()->handleException($exception);
		}
	}

	public function handleEvent(sfEvent $event) {
		$subject = $event->getSubject();
		if(is_scalar($subject) || $subject === null) {
			PhpConsole::debug($subject, $event->getName());
		}
	}
}
