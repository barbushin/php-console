<?php

namespace PhpConsole\Dispatcher;

/**
 * Executes client code and sends result data to connector as client expected messages
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://php-console.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
class Evaluate extends \PhpConsole\Dispatcher {

	/** @var \PhpConsole\EvalProvider */
	protected $evalProvider;

	/**
	 * @param \PhpConsole\Connector $connector
	 * @param \PhpConsole\EvalProvider $evalProvider
	 * @param \PhpConsole\Dumper $dumper
	 */
	public function __construct(\PhpConsole\Connector $connector, \PhpConsole\EvalProvider $evalProvider, \PhpConsole\Dumper $dumper) {
		$this->evalProvider = $evalProvider;
		parent::__construct($connector, $dumper);
	}

	/**
	 * Override eval provider
	 * @param \PhpConsole\EvalProvider $evalProvider
	 */
	public function setEvalProvider(\PhpConsole\EvalProvider $evalProvider) {
		$this->evalProvider = $evalProvider;
	}

	/**
	 * Get eval provider
	 * @return \PhpConsole\EvalProvider
	 */
	public function getEvalProvider() {
		return $this->evalProvider;
	}

	/**
	 * Execute PHP code and send result message in connector
	 * @param $code
	 */
	public function dispatchCode($code) {
		if($this->isActive()) {
			$previousLastError = error_get_last();
			$oldDisplayErrors = ini_set('display_errors', false);
			$result = $this->evalProvider->evaluate($code);
			ini_set('display_errors', $oldDisplayErrors);

			$message = new \PhpConsole\EvalResultMessage();
			$message->return = $this->dumper->dump($result->return);
			$message->output = $this->dumper->dump($result->output);
			$message->time = round($result->time, 6);

			$newLastError = error_get_last();
			if($newLastError && $newLastError != $previousLastError) {
				$this->connector->getErrorsDispatcher()->dispatchError($newLastError ['type'], $newLastError ['message'], $newLastError ['file'], $newLastError ['line'], 999);
			}
			if($result->exception) {
				$this->connector->getErrorsDispatcher()->dispatchException($result->exception);
			}
			$this->sendMessage($message);
		}
	}
}
