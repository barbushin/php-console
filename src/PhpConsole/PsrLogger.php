<?php

namespace PhpConsole;

use Psr\Log\LogLevel;

/**
 * Implementation of PSR-3 logger interface https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * IMPORTANT: https://github.com/php-fig/log must be installed & autoloaded
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://php-console.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
class PsrLogger extends \Psr\Log\AbstractLogger {

	public static $debugLevels = array(
		LogLevel::NOTICE => 'notice',
		LogLevel::INFO => 'info',
		LogLevel::DEBUG => 'debug',
	);

	public static $errorsLevels = array(
		LogLevel::EMERGENCY => 'PSR_EMERGENCY',
		LogLevel::ALERT => 'PSR_ALERT',
		LogLevel::CRITICAL => 'PSR_CRITICAL',
		LogLevel::ERROR => 'PSR_ERROR',
		LogLevel::WARNING => 'PSR_WARNING',
	);

	/** @var  Connector */
	protected $connector;
	/** @var  Dumper */
	protected $contextDumper;

	public function __construct(Connector $connector = null, Dumper $contextDumper = null) {
		$this->connector = $connector ? : Connector::getInstance();
		$this->contextDumper = $contextDumper ? : $this->connector->getDumper();
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 */
	public function log($level, $message, array $context = array()) {
		if(is_object($message) && is_callable($message, '__toString')) {
			$message = (string)$message;
		}
		$message = $this->fetchMessageContext($message, $context);

		if(isset(static::$debugLevels[$level])) {
			$this->connector->getDebugDispatcher()->dispatchDebug($message, static::$debugLevels[$level], null);
		}
		elseif(isset(static::$errorsLevels[$level])) {
			if(isset($context['exception']) && $context['exception'] instanceof \Exception) {
				$this->connector->getErrorsDispatcher()->dispatchException($context['exception']);
			}
			else {
				$this->connector->getErrorsDispatcher()->dispatchError(static::$errorsLevels[$level], $message, null, null, null);
			}
		}
		else {
			throw new \Psr\Log\InvalidArgumentException('Unknown log level "' . $level . '"');
		}
	}

	protected function fetchMessageContext($message, array $context) {
		$replace = array();
		foreach($context as $key => $value) {
			$replace['{' . $key . '}'] = $this->contextDumper->dump($value);
		}
		return strtr($message, $replace);
	}
}
