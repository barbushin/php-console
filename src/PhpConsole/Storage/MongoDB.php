<?php

namespace PhpConsole\Storage;

/**
 * MongoDB storage for postponed response data.
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://php-console.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
class MongoDB extends ExpiringKeyValue {

	/** @var  \MongoClient */
	protected $mongoClient;
	/** @var  \MongoCollection */
	protected $mongoCollection;

	public function __construct($server = 'mongodb://localhost:27017', $db = 'phpconsole', $collection = 'phpconsole') {
		$this->mongoClient = new \MongoClient($server);
		if(!$this->mongoClient) {
			throw new \Exception('Unable to connect to MongoDB server');
		}

		$this->mongoCollection = $this->mongoClient->selectCollection($db, $collection);
		if(!$this->mongoCollection) {
			throw new \Exception('Unable to get collection');
		}

		$this->mongoCollection->ensureIndex(array(
			'expireAt' => 1,
		), array(
			'background' => true,
			'name' => 'TTL',
			'expireAfterSeconds' => 0,
		));
	}

	/**
	 * Save data by auto-expire key
	 * @param $key
	 * @param string $data
	 * @param int $expire
	 */
	protected function set($key, $data, $expire) {
		$this->mongoCollection->update(array(
			'key' => $key
		), array(
			'key' => $key,
			'data' => $data,
			'expireAt' => new \MongoDate(time() + $expire)
		), array(
			'upsert' => true
		));
	}

	/**
	 * Get data by key if not expired
	 * @param $key
	 * @return string
	 */
	protected function get($key) {
		$record = $this->mongoCollection->findOne(array('key' => $key));
		if($record && is_array($record) && array_key_exists('data', $record)) {
			return $record['data'];
		}
	}

	/**
	 * Remove key in store
	 * @param $key
	 * @return mixed
	 */
	protected function delete($key) {
		return $this->mongoCollection->remove(array('key' => $key));
	}
}
