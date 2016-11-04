<?php

namespace PhpConsole\Storage;
use PhpConsole\Storage;

/**
 * Abstract class for stores that manipulates with all keys data in memory
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://consle.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
abstract class AllKeysList extends Storage {

	/**
	 * Get all postponed keys data
	 * @return array
	 */
	abstract protected function getKeysData();

	/**
	 * Save all postponed keys data
	 * @param array $keysData
	 */
	abstract protected function saveKeysData(array $keysData);

	/**
	 * Get postponed data from storage and delete
	 * @param string $key
	 * @return string
	 */
	public function pop($key) {
		$keysData = $this->getKeysData();
		if(isset($keysData[$key])) {
			$keyData = $keysData[$key]['data'];
			unset($keysData[$key]);
			$this->saveKeysData($keysData);
			return $keyData;
		}
	}

	/**
	 * Save postponed data to storage
	 * @param string $key
	 * @param string $data
	 */
	public function push($key, $data) {
		$keysData = $this->getKeysData();
		$this->clearExpiredKeys($keysData);
		$keysData[$key] = array(
			'time' => time(),
			'data' => $data
		);
		$this->saveKeysData($keysData);
	}

	/**
	 * Remove postponed data that is out of limit
	 * @param array $keysData
	 */
	protected function clearExpiredKeys(array &$keysData) {
		$expireTime = time() - $this->keyLifetime;
		foreach($keysData as $key => $item) {
			if($item['time'] < $expireTime) {
				unset($keysData[$key]);
			}
		}
	}
}
