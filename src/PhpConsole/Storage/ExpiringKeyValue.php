<?php

namespace PhpConsole\Storage;
use PhpConsole\Storage;

/**
 * Abstract class for key-value stores with key auto-expire support
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://consle.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 * @codeCoverageIgnore
 */
abstract class ExpiringKeyValue extends Storage {

	/**
	 * Save data by auto-expire key
	 * @param $key
	 * @param string $data
	 * @param int $expire
	 */
	abstract protected function set($key, $data, $expire);

	/**
	 * Get data by key if not expired
	 * @param $key
	 * @return string
	 */
	abstract protected function get($key);

	/**
	 * Remove key in store
	 * @param $key
	 * @return mixed
	 */
	abstract protected function delete($key);

	/**
	 * Get postponed data from storage and delete
	 * @param string $key
	 * @return string
	 */
	public function pop($key) {
		$data = $this->get($key);
		if($data) {
			$this->delete($key);
		}
		return $data;
	}

	/**
	 * Save postponed data to storage
	 * @param string $key
	 * @param string $data
	 */
	public function push($key, $data) {
		$this->set($key, $data, $this->keyLifetime);
	}
}
