<?php

namespace PhpConsole;

/**
 * Storage for postponed response data
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://php-console.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
abstract class Storage {

	protected $keyLifetime = 60;

	/**
	 * Get postponed data from storage and delete
	 * @param string $key
	 * @return string
	 */
	abstract public function pop($key);

	/**
	 * Save postponed data to storage
	 * @param string $key
	 * @param string $data
	 */
	abstract public function push($key, $data);

	/**
	 * Set maximum key lifetime in seconds
	 * @param int $seconds
	 */
	public function setKeyLifetime($seconds) {
		$this->keyLifetime = $seconds;
	}
}
