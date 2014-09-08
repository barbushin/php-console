<?php

namespace PhpConsole\Dispatcher;

/**
 * Sends debug data to connector as client expected messages
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://php-console.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
class Debug extends \PhpConsole\Dispatcher {

	/** @var bool Autodetect and append trace data to debug */
	public $detectTraceAndSource = false;

	/**
	 * Send debug data message to client
	 * @param mixed $data
	 * @param null|string $tags Tags separated by dot, e.g. "low.db.billing"
	 * @param null|int $callLevel Number of proxy methods between original "debug call" and this method call
	 */
	public function dispatchDebug($data, $tags = null, $callLevel = 0) {
		if($this->isActive()) {
			$message = new \PhpConsole\DebugMessage();
			$message->data = $this->dumper->dump($data);
			if($tags) {
				$message->tags = explode('.', $tags);
			}
			if($this->detectTraceAndSource && $callLevel !== null) {
				$message->trace = $this->fetchTrace(debug_backtrace(), $message->file, $message->line, $callLevel);
			}
			$this->sendMessage($message);
		}
	}
}
