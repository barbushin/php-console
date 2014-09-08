<?php

namespace PhpConsole {

	/**
	 * There is adapter of PhpConsole v1.x to v3.x
	 * It's for users that just want to migrate from PhpConsole v1 to v3 without any code changes
	 *
	 * Usage:
	 *
	 * 1. Register PhpConsole class emulator
	 *
	 * require_once('/path/to/src/PhpConsole/__autoload.php');
	 * \PhpConsole\OldVersionAdapter::register();
	 *
	 * 2. Call PhpConsole v1.x methods as is:
	 *
	 * $pc = PhpConsole::getInstance();
	 * $pc->start($handleErrors = true, $handleExceptions = true, $sourceBasePath = null);
	 * PhpConsole::debug('message', 'some,tags');
	 * debug('message', 'some,tags');
	 *
	 * IMPORTANT: This adapter will be removed in PhpConsole > v3, so it's strongly recommended to migrate your code using original PhpConsole v3 methods
	 *
	 * @package PhpConsole
	 * @version 3.1
	 * @link http://php-console.com
	 * @author Sergey Barbushin http://linkedin.com/in/barbushin
	 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
	 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
	 */
	class OldVersionAdapter {

		public static $callOldErrorHandler = true;
		public static $callOldExceptionsHandler = true;

		/** @var OldVersionAdapter|null */
		protected static $instance;

		private function __construct() {
		}

		/**
		 * This method must be called just to force \PhpConsole class initialization
		 * @param Connector|null $connector
		 * @param Handler|null $handler
		 * @throws \Exception
		 * @return Connector
		 */
		public static function register(Connector $connector = null, Handler $handler = null) {
		}

		/**
		 * Start PhpConsole v1 handler
		 * @param bool $handleErrors
		 * @param bool $handleExceptions
		 * @param null|string $sourceBasePath
		 */
		public static function start($handleErrors = true, $handleExceptions = true, $sourceBasePath = null) {
			if(self::$instance) {
				die('PhpConsole already started');
			}
			self::$instance = new static();

			$handler = Handler::getInstance();
			$handler->setHandleErrors($handleErrors);
			$handler->setHandleExceptions($handleExceptions);
			$handler->setCallOldHandlers(self::$callOldErrorHandler || self::$callOldExceptionsHandler);
			$handler->start();

			$connector = $handler->getConnector();
			$connector->setSourcesBasePath($sourceBasePath);
		}

		public static function getInstance() {
			if(!self::$instance) {
				throw new \Exception('PhpConsole not started');
			}
			return self::$instance;
		}

		/**
		 * @return Connector
		 */
		public function getConnector() {
			return Connector::getInstance();
		}

		/**
		 * @return Handler
		 */
		public function getHandler() {
			return Handler::getInstance();
		}

		public function handleError($code = null, $message = null, $file = null, $line = null) {
			$this->getHandler()->handleError($code, $message, $file, $line, null, 1);
		}

		public function handleException(\Exception $exception) {
			$this->getHandler()->handleException($exception);
		}

		public static function debug($message, $tags = 'debug') {
			Handler::getInstance()->debug($message, str_replace(',', '.', $tags), 1);
		}
	}
}

namespace {

	if(!class_exists('PhpConsole', false)) {
		class PhpConsole extends \PhpConsole\OldVersionAdapter {

		}
	}

	if(!function_exists('debug')) {
		function debug($message, $tags = 'debug') {
			\PhpConsole\Handler::getInstance()->debug($message, $tags, 1);
		}
	}
}
