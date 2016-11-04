<?php

namespace PhpConsole;

/**
 * Convert any type of var to string or array with different kind of limits
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://consle.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
class Dumper {

	/** @var int Maximum array or object nested dump level */
	public $levelLimit;
	/** @var  int Maximum same level array items or object properties number */
	public $itemsCountLimit;
	/** @var  int Maximum length of any string or array item */
	public $itemSizeLimit;
	/** @var int|null Maximum approximate size of dump result formatted in JSON */
	public $dumpSizeLimit;
	/** @var bool Convert callback items to (callback SomeClass::someMethod) strings */
	public $detectCallbacks = true;

	/**
	 * @param int $levelLimit Maximum array or object nested dump level
	 * @param int $itemsCountLimit Maximum same level array items or object properties number
	 * @param int $itemSizeLimit Maximum length of any string or array item
	 * @param int $dumpSizeLimit Maximum approximate size of dump result formatted in JSON. Default is $itemsCountLimit * $itemSizeLimit
	 */
	public function __construct($levelLimit = 5, $itemsCountLimit = 100, $itemSizeLimit = 50000, $dumpSizeLimit = 500000) {
		$this->levelLimit = $levelLimit;
		$this->itemsCountLimit = $itemsCountLimit;
		$this->itemSizeLimit = $itemSizeLimit;
		$this->dumpSizeLimit = $dumpSizeLimit;
	}

	/**
	 * Convert any type of var to string or array applying all actual limits & transformations
	 * @param mixed $var
	 * @return string|array
	 */
	public function dump($var) {
		$this->dumpVarData($var, $this->levelLimit);
		return $var;
	}

	/**
	 * Recursively convert any type of var to string or array applying all actual limits & transformations
	 * @param $data
	 * @param $levelLimit
	 * @param bool $rootCall
	 */
	protected function dumpVarData(&$data, $levelLimit, $rootCall = true) {
		static $sizeLeft,
		$objectsHashes = array(),
		$origQueue = array(),
		$refQueue = array(),
		$levelsQueue = array();

		if($rootCall) {
			$sizeLeft = $this->dumpSizeLimit ? : 999999999;
		}

		if(is_object($data)) {
			if($data instanceof \Closure) {
				$data = '(callback function)';
				return;
			}
			if($rootCall) {
				$data = array('' => $data);
				return $this->dumpVarData($data, $levelLimit + 1);
			}
			$objectsHashes[] = spl_object_hash($data);
			$dataArray = array();
			foreach((array)$data as $key => $value) {
				$nullPos = strrpos($key, chr(0));
				if($nullPos) {
					$dataArray[substr($key, $nullPos + 1)] = $value;
				}
				else {
					$dataArray[$key] = $value;
				}
			}
			$data = $dataArray;
		}

		if(is_array($data)) {

			if($this->detectCallbacks && count($data) == 2 && is_callable($data)) {
				list($class, $method) = $data;
				$data = '(callback ' . (is_object($class) ? get_class($class) : $class) . '::' . $method . ')';
				$sizeLeft -= strlen($data) + 4;
				return;
			}

			$i = 0;
			$dataArray = array();
			foreach($data as $k => &$v) {
				if(($this->itemsCountLimit && $i >= $this->itemsCountLimit) || $sizeLeft <= 0) {
					break;
				}
				if(is_array($v) || is_object($v)) {
					if($levelLimit > 1) {
						$origQueue[] = $v;
						$refQueue[] =& $v;
						$levelsQueue[] = $levelLimit;
					}
					if(is_object($v) && !$v instanceof \Closure) {
						$k .= ':' . get_class($v);
						$hash = spl_object_hash($v);
						if(in_array($hash, $objectsHashes)) {
							$v = '*RECURSION*';
						}
						else {
							$v = '(object)';
							$objectsHashes[] = $hash;
						}
					}
					else {
						$v = '(array)';
					}
					$sizeLeft -= strlen($k) + strlen($v) + 8;
				}
				else {
					$sizeLeft -= strlen($k) + 4;
					$this->dumpVarData($v, $levelLimit - 1, false);
				}
				$dataArray[$k] =& $v;
				$i++;
			}

			if($i != count($data)) {
				$dataArray['...'] = '(displayed ' . $i . ' of ' . count($data) . ')';
			}
			$data = $dataArray;

			if(!$rootCall) {
				return;
			}

			do {
				$origData = array_shift($origQueue);
				$level = array_shift($levelsQueue);
				$refData =& $refQueue[0];
				array_shift($refQueue);
				$sizeLeft += strlen($refData);
				if($refData !== '*RECURSION*') {
					$this->dumpVarData($origData, $level - 1, false);
					$refData = $origData;
				}
			}
			while(count($origQueue) && $sizeLeft >= 0);

			if($rootCall) {
				$levelsQueue = $origQueue = $refQueue = $objectsHashes = array();
			}
		}
		// scalar or resource
		else {
			if(!is_scalar($data) && $data !== null) {
				if(is_resource($data)) {
					$data = '(' . strtolower((string)$data) . ' ' . get_resource_type($data) . ')';
					$sizeLeft -= strlen($data);
					return;
				}
				$data = var_export($data, true);
			}
			if(strlen($data) > $this->itemSizeLimit) {
				$data = substr($data, 0, $this->itemSizeLimit - 3) . '...';
			}
			if(strlen($data) > $sizeLeft) {
				$data = substr($data, 0, $sizeLeft - 3) . '...';
			}
			$sizeLeft -= strlen($data);
		}
	}
}
