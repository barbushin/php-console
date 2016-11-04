<?php

namespace PhpConsole\Dispatcher;
use PhpConsole\DebugMessage;
use PhpConsole\Dispatcher;

/**
 * Sends debug data to connector as client expected messages
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://consle.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
class Debug extends Dispatcher {

	/** @var bool Autodetect and append trace data to debug */
	public $detectTraceAndSource = false;

	/**
	 * Send debug data message to client
	 * @param mixed $data
	 * @param null|string $tags Tags separated by dot, e.g. "low.db.billing"
	 * @param int|array $ignoreTraceCalls Ignore tracing classes by name prefix `array('PhpConsole')` or fixed number of calls to ignore
	 */
	public function dispatchDebug($data, $tags = null, $ignoreTraceCalls = 0) {
		if($this->isActive()) {
			$message = new DebugMessage();
			$message->data = $this->dumper->dump($data);
			if($tags) {
				$message->tags = explode('.', $tags);
			}
			if($this->detectTraceAndSource && $ignoreTraceCalls !== null) {
				$message->trace = $this->fetchTrace(debug_backtrace(), $message->file, $message->line, $ignoreTraceCalls);
			}
			$this->sendMessage($message);
		}
	}
}
