<?php

namespace PhpConsole;

/**
 * Abstract class of dispatchers that sends different kind data to connector as client expected messages
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://consle.com
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
	 * @param int|array $ignoreTraceCalls Ignore tracing classes by name prefix `array('PhpConsole')` or fixed number of calls to ignore
	 * @return TraceCall[]
	 */
	protected function fetchTrace(array $trace, &$file = null, &$line = null, $ignoreTraceCalls = 0) {
		$ignoreByNumber = is_numeric($ignoreTraceCalls) ? $ignoreTraceCalls : 0;
		$ignoreByClassPrefixes = is_array($ignoreTraceCalls) ? array_merge($ignoreTraceCalls, array(__NAMESPACE__)) : null;

		foreach($trace as $i => $call) {
			if(!$file && $i == $ignoreTraceCalls && isset($call['file'])) {
				$file = $call['file'];
				$line = $call['line'];
			}
			if($ignoreByClassPrefixes && isset($call['class'])) {
				foreach($ignoreByClassPrefixes as $classPrefix) {
					if(strpos($call['class'], $classPrefix) !== false) {
						unset($trace[$i]);
						continue;
					}
				}
			}
			if($i < $ignoreByNumber || (isset($call['file']) && $call['file'] == $file && $call['line'] == $line)) {
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

			if(strpos($call['function'], '{closure}')) {
				$call['function'] = '{closure}';
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
