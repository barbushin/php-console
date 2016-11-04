<?php

namespace PhpConsole\Storage;

/**
 * Memcache storage for postponed response data.
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://consle.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 * @codeCoverageIgnore
 */
class Memcache extends ExpiringKeyValue {

	/** @var  \Memcache */
	protected $memcache;

	public function __construct($host = 'localhost', $port = 11211) {
		$this->memcache = new \Memcache();
		if(!$this->memcache->connect($host, $port)) {
			throw new \Exception('Unable to connect to Memcache server');
		}
	}

	/**
	 * Save data by auto-expire key
	 * @param $key
	 * @param string $data
	 * @param int $expire
	 */
	protected function set($key, $data, $expire) {
		$this->memcache->set($key, $data, null, $expire);
	}

	/**
	 * Get data by key if not expired
	 * @param $key
	 * @return string
	 */
	protected function get($key) {
		return $this->memcache->get($key);
	}

	/**
	 * Remove key in store
	 * @param $key
	 * @return mixed
	 */
	protected function delete($key) {
		$this->memcache->delete($key);
	}
}
