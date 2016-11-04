<?php

namespace PhpConsole {

	/**
	 * Makes more easy access to debug dispatcher method.
	 *
	 * Usage:
	 * 1. Call \PhpConsole\Helper::register();
	 * 2. Call PC::debug($sql, 'db') or PC::db($sql)
	 *
	 * It will be the same as calling Handler::getInstance()->debug($var, 'db')
	 *
	 * @package PhpConsole
	 * @version 3.1
	 * @link http://consle.com
	 * @author Sergey Barbushin http://linkedin.com/in/barbushin
	 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
	 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
	 */
	class Helper {

		/** @var Connector|null */
		private static $connector;
		/** @var Handler|null */
		private static $handler;
		/** @var  bool */
		protected static $isActive;

		private function __construct() {
		}

		/**
		 * This method must be called to make class "PC" available
		 * @param Connector|null $connector
		 * @param Handler|null $handler
		 * @throws \Exception
		 * @return Connector
		 */
		public static function register(Connector $connector = null, Handler $handler = null) {
			if(static::$connector) {
				throw new \Exception('Helper already registered');
			}
			self::$handler = $handler;
			self::$connector = $connector ? : Connector::getInstance();
			self::$isActive = self::$connector->isActiveClient();
			return self::$connector;
		}

		/**
		 * Check if method Helper::register() was called before
		 * @return bool
		 */
		public static function isRegistered() {
			return isset(self::$connector);
		}

		/**
		 * Get actual helper connector instance
		 * @return Connector
		 * @throws \Exception
		 */
		public static function getConnector() {
			if(!self::$connector) {
				throw new \Exception('Helper is not registered. Call ' . get_called_class() . '::register()');
			}
			return self::$connector;
		}

		/**
		 * Get actual handler instance
		 * @return Handler
		 * @throws \Exception
		 */
		public static function getHandler() {
			if(!self::$connector) {
				throw new \Exception('Helper is not registered. Call ' . get_called_class() . '::register()');
			}
			if(!self::$handler) {
				self::$handler = Handler::getInstance();
			}
			return self::$handler;
		}

		/**
		 * Analog of Handler::getInstance()->debug(...) method
		 * @param mixed $data
		 * @param string|null $tags Tags separated by dot, e.g. "low.db.billing"
		 * @param int|array $ignoreTraceCalls Ignore tracing classes by name prefix `array('PhpConsole')` or fixed number of calls to ignore
		 */
		public static function debug($data, $tags = null, $ignoreTraceCalls = 0) {
			if(self::$isActive) {
				self::$connector->getDebugDispatcher()->dispatchDebug($data, $tags, is_numeric($ignoreTraceCalls) ? $ignoreTraceCalls + 1 : $ignoreTraceCalls);
			}
		}

		/**
		 * Short access to analog of Handler::getInstance()->debug(...) method
		 * You can access it like PC::tagName($debugData, $additionalTags = null)
		 * @param string $tags
		 * @param $args
		 */
		public static function __callStatic($tags, $args) {
			if(isset($args[1])) {
				$tags .= '.' . $args[1];
			}
			static::debug(isset($args[0]) ? $args[0] : null, $tags, 1);
		}
	}
}

namespace {

	use PhpConsole\Helper;

	if(!class_exists('PC', false)) {
		/**
		 * Helper short class name in global namespace
		 */
		class PC extends Helper {

		}
	}
}
