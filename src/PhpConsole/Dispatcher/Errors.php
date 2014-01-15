<?php

namespace PhpConsole\Dispatcher;

/**
 * Sends system errors and exceptions to connector as client expected messages
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://php-console.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
class Errors extends \PhpConsole\Dispatcher {

	/** @var array PHP errors constants values => names(will be initialized in first call) */
	protected static $errorsConstantsValues = array();
	/** @var array PHP errors constants names */
	protected static $errorsConstantsNames = array(
		'E_STRICT',
		'E_DEPRECATED',
		'E_RECOVERABLE_ERROR',
		'E_NOTICE',
		'E_WARNING',
		'E_ERROR',
		'E_PARSE',
		'E_USER_DEPRECATED',
		'E_USER_NOTICE',
		'E_USER_WARNING',
		'E_USER_ERROR',
		'E_CORE_WARNING',
		'E_CORE_ERROR',
		'E_COMPILE_ERROR',
		'E_COMPILE_WARNING',
	);

	/** @var bool Don't send errors messages with same file, line & class */
	public $ignoreRepeatedSource = true;
	/** @var bool Dispatch $exception->getPrevious() if not empty */
	public $dispatchPreviousExceptions = true;

	/** @var \PhpConsole\ErrorMessage[] */
	protected $sentMessages = array();

	/**
	 * Send error message to client
	 * @param null|integer $code
	 * @param null|string $text
	 * @param null|string $file
	 * @param null|integer $line
	 * @param null|int $callLevel Number of proxy methods between original "error handler method" and this method call
	 */
	public function dispatchError($code = null, $text = null, $file = null, $line = null, $callLevel = 0) {
		if($this->isActive()) {
			$message = new \PhpConsole\ErrorMessage();
			$message->code = $code;
			$message->class = $this->getErrorTypeByCode($code);
			$message->data = $this->dumper->dump($text);
			$message->file = $file;
			$message->line = $line;
			if($callLevel !== null) {
				$message->trace = $this->fetchTrace(debug_backtrace(), $file, $line, $callLevel + 1);
			}
			$this->sendMessage($message);
		}
	}

	/**
	 * Send exception message to client
	 * @param \Exception $exception
	 */
	public function dispatchException(\Exception $exception) {
		if($this->isActive()) {
			if($this->dispatchPreviousExceptions && $exception->getPrevious()) {
				$this->dispatchException($exception->getPrevious());
			}
			$message = new \PhpConsole\ErrorMessage();
			$message->code = $exception->getCode();
			$message->class = get_class($exception);
			$message->data = $this->dumper->dump($exception->getMessage());
			$message->file = $exception->getFile();
			$message->line = $exception->getLine();
			$message->trace = self::fetchTrace($exception->getTrace(), $message->file, $message->line);
			$this->sendMessage($message);
		}
	}

	/**
	 * Send message to PHP Console connector
	 * @param \PhpConsole\Message $message
	 */
	protected function sendMessage(\PhpConsole\Message $message) {
		if(!$this->isIgnored($message)) {
			parent::sendMessage($message);
			$this->sentMessages[] = $message;
		}
	}

	/**
	 * Get PHP error constant name by value
	 * @param int $code
	 * @return string
	 */
	protected function getErrorTypeByCode($code) {
		if(!static::$errorsConstantsValues) {
			foreach(static::$errorsConstantsNames as $constantName) {
				if(defined($constantName)) {
					static::$errorsConstantsValues[constant($constantName)] = $constantName;
				}
			}
		}
		if(isset(static::$errorsConstantsValues[$code])) {
			return static::$errorsConstantsValues[$code];
		}
		return (string)$code;
	}

	/**
	 * Return true if message with same file, line & class was already sent
	 * @param \PhpConsole\ErrorMessage $message
	 * @return bool
	 */
	protected function isIgnored(\PhpConsole\ErrorMessage $message) {
		if($this->ignoreRepeatedSource && $message->file) {
			foreach($this->sentMessages as $sentMessage) {
				if($message->file == $sentMessage->file && $message->line == $sentMessage->line && $message->class == $sentMessage->class) {
					return true;
				}
			}
		}
		return false;
	}
}
