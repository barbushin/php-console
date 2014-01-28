<?php

namespace PhpConsole;

/**
 * Overrides PHP errors and exceptions handlers, so all errors, exceptions and debug messages will be sent to PHP Console client
 * By default all handled errors and exceptions will be passed to previously defined handlers
 *
 * You will need to install Google Chrome extension "PHP Console"
 * https://chrome.google.com/webstore/detail/php-console/nfhmhhlpfleoednkpnnnkolmclajemef
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://php-console.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
class Handler {

	const ERRORS_RECURSION_LIMIT = 3;

	/** @var Handler */
	protected static $instance;

	/** @var Connector */
	protected $connector;
	protected $isStarted = false;
	protected $isHandling = false;
	protected $errorsHandlerLevel;
	protected $handleErrors = true;
	protected $handleExceptions = true;
	protected $callOldHandlers = true;
	protected $oldErrorsHandler;
	protected $oldExceptionsHandler;
	protected $recursiveHandlingLevel = 0;

	/**
	 * @return static
	 */
	public static function getInstance() {
		if(!self::$instance) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	protected function __construct() {
		$this->connector = Connector::getInstance();
	}

	private final function __clone() {
	}

	/**
	 * Start errors & exceptions handlers
	 * @throws \Exception
	 */
	public function start() {
		if($this->isStarted) {
			throw new \Exception(get_called_class() . ' is already started, use ' . get_called_class() . '::getInstance()->isStarted() to check it.');
		}
		$this->isStarted = true;

		if($this->handleErrors) {
			$this->initErrorsHandler();
		}
		if($this->handleExceptions) {
			$this->initExceptionsHandler();
		}
	}

	/**
	 * Validate that method is called before start. Required for handlers configuration methods.
	 * @throws \Exception
	 */
	protected function checkIsCalledBeforeStart() {
		if($this->isStarted) {
			throw new \Exception('This method can be called only before ' . get_class($this) . '::start()');
		}
	}

	/**
	 * Enable or disable errors handler
	 * @param bool $isEnabled
	 */
	public function setHandleErrors($isEnabled) {
		$this->checkIsCalledBeforeStart();
		$this->handleErrors = $isEnabled;
	}

	/**
	 * Enable or disable exceptions handler
	 * @param bool $isEnabled
	 */
	public function setHandleExceptions($isEnabled) {
		$this->checkIsCalledBeforeStart();
		$this->handleExceptions = $isEnabled;
	}

	/**
	 * Enable or disable calling overridden  errors & exceptions
	 * @param bool $isEnabled
	 */
	public function setCallOldHandlers($isEnabled) {
		$this->callOldHandlers = $isEnabled;
	}

	/**
	 * @return Connector
	 */
	public function getConnector() {
		return $this->connector;
	}

	/**
	 * Check if PHP Console handler is started
	 * @return bool
	 */
	public function isStarted() {
		return $this->isStarted;
	}

	/**
	 * Override PHP exceptions handler to PHP Console handler
	 */
	protected function initExceptionsHandler() {
		$this->oldExceptionsHandler = set_exception_handler(array($this, 'handleException'));
	}

	/**
	 * Set custom errors handler level like E_ALL ^ E_STRICT
	 * But, anyway, it's strongly recommended to configure ignore some errors type in PHP Console extension options
	 * IMPORTANT: previously old error handler will be called only with new errors level
	 * @param int $level see http://us1.php.net/manual/ru/function.error-reporting.php
	 */
	public function setErrorsHandlerLevel($level) {
		$this->checkIsCalledBeforeStart();
		$this->errorsHandlerLevel = $level;
	}

	/**
	 * Override PHP errors handler to PHP Console handler
	 */
	protected function initErrorsHandler() {
		ini_set('display_errors', false);
		ini_set('html_errors', false);
		error_reporting($this->errorsHandlerLevel ? : E_ALL | E_STRICT);
		$this->oldErrorsHandler = set_error_handler(array($this, 'handleError'));
		register_shutdown_function(array($this, 'checkFatalErrorOnShutDown'));
		$this->connector->registerFlushOnShutDown();
	}

	/**
	 * Method is called by register_shutdown_function(), it's required to handle fatal PHP errors. Never call it manually.
	 */
	public function checkFatalErrorOnShutDown() {
		$error = error_get_last();
		if($error) {
			ini_set('memory_limit', memory_get_usage(true) + 1000000); // if memory limit exceeded
			$this->callOldHandlers = false;
			$this->handleError($error['type'], $error['message'], $error['file'], $error['line'], null, 1);
		}
	}

	/**
	 * Handle error data
	 * @param int|null $code
	 * @param string|null $text
	 * @param string|null $file
	 * @param int|null $line
	 * @param null $context
	 * @param int $skipCallsLevel Number of proxy methods between original "error handler method" and this method call
	 */
	public function handleError($code = null, $text = null, $file = null, $line = null, $context = null, $skipCallsLevel = 0) {
		if(!$this->isStarted || error_reporting() === 0 || $this->isHandlingDisabled()) {
			return;
		}
		$this->onHandlingStart();
		$this->connector->getErrorsDispatcher()->dispatchError($code, $text, $file, $line, $skipCallsLevel + 1);
		if($this->oldErrorsHandler && $this->callOldHandlers) {
			call_user_func_array($this->oldErrorsHandler, array($code, $text, $file, $line, $context));
		}
		$this->onHandlingComplete();
	}

	/**
	 * Method is called before handling any error or exception
	 */
	protected function onHandlingStart() {
		$this->recursiveHandlingLevel++;
	}

	/**
	 * Method is called after handling any error or exception
	 */
	protected function onHandlingComplete() {
		$this->recursiveHandlingLevel--;
	}

	/**
	 * Check if errors/exception handling is disabled
	 * @return bool
	 */
	protected function isHandlingDisabled() {
		return $this->recursiveHandlingLevel >= static::ERRORS_RECURSION_LIMIT;
	}

	/**
	 * Handle exception object
	 * @param \Exception $exception
	 */
	public function handleException(\Exception $exception) {
		if(!$this->isStarted || $this->isHandlingDisabled()) {
			return;
		}
		try {
			$this->onHandlingStart();
			$this->connector->getErrorsDispatcher()->dispatchException($exception);
			if($this->oldExceptionsHandler && $this->callOldHandlers) {
				call_user_func($this->oldExceptionsHandler, $exception);
			}
		}
		catch(\Exception $internalException) {
			$this->handleException($internalException);
		}
		$this->onHandlingComplete();
	}

	/**
	 * Handle debug data
	 * @param mixed $data
	 * @param string|null $tags Tags separated by dot, e.g. "low.db.billing"
	 * @param int $skipTraceCalls Number of proxy methods between original "debug method call" and this method call
	 */
	public function debug($data, $tags = null, $skipTraceCalls = 0) {
		if($this->connector->isActiveClient()) {
			$this->connector->getDebugDispatcher()->dispatchDebug($data, $tags, $skipTraceCalls + 1);
		}
	}
}
