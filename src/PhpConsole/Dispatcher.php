<?php

namespace PhpConsole;

/**
 * Abstract class of dispatchers that sends different kind data to connector as client expected messages
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://php-console.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
abstract class Dispatcher {

	/** @var  Connector */
	protected $connector;
	/** @var Dumper */
	protected $dumper;

	/**
	 * @param Connector $connector
	 * @param Dumper $dumper
	 */
	public function __construct(Connector $connector, Dumper $dumper) {
		$this->connector = $connector;
		$this->setDumper($dumper);
	}

	/**
	 * Override default dumper
	 * @param Dumper $dumper
	 */
	public function setDumper(Dumper $dumper) {
		$this->dumper = $dumper;
	}

	/**
	 * Check if dispatcher is active to send messages
	 * @return bool
	 */
	public function isActive() {
		return $this->connector->isActiveClient();
	}

	/**
	 * Send message to PHP Console connector
	 * @param Message $message
	 */
	protected function sendMessage(Message $message) {
		$this->connector->sendMessage($message);
	}

	/**
	 * Convert backtrace to array of TraceCall with source file & line detection
	 * @param array $trace Standard PHP backtrace array
	 * @param null|string $file Reference to var that will contain source file path
	 * @param null|string $line Reference to var that will contain source line number
	 * @param int $skipTraceCalls Last trace calls that will be stripped in result
	 * @return TraceCall[]
	 */
	protected function fetchTrace(array $trace, &$file = null, &$line = null, $skipTraceCalls = 0) {
		foreach($trace as $i => $call) {
			if(!$file && $i == $skipTraceCalls && isset($call['file'])) {
				$file = $call['file'];
				$line = $call['line'];
			}
			if($i < $skipTraceCalls || (isset($call['file']) && $call['file'] == $file && $call['line'] == $line)) {
				unset($trace[$i]);
			}
		}

		$traceCalls = array();
		foreach(array_reverse($trace) as $call) {
			$args = array();
			if(isset($call['args'])) {
				foreach($call['args'] as $arg) {
					if(is_object($arg)) {
						$args[] = get_class($arg);
					}
					elseif(is_array($arg)) {
						$args[] = 'Array[' . count($arg) . ']';
					}
					else {
						$arg = var_export($arg, 1);
						$args[] = strlen($arg) > 15 ? substr($arg, 0, 15) . '...\'' : $arg;
					}
				}
			}

			$traceCall = new TraceCall();
			$traceCall->call = (isset($call['class']) ? $call['class'] . $call['type'] : '') . $call['function'] . '(' . implode(', ', $args) . ')';
			if(isset($call['file'])) {
				$traceCall->file = $call['file'];
			}
			if(isset($call['line'])) {
				$traceCall->line = $call['line'];
			}
			$traceCalls[] = $traceCall;
		}
		return $traceCalls;
	}
}
