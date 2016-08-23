<?php

namespace PhpConsole\Storage;

/**
 * File storage for postponed response data. Use it if session handler in your project is overridden.
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://php-console.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
class File extends AllKeysList {

	protected $filePath;
	protected $fileHandler;

	/**
	 * @param string $filePath Writable path for postponed data storage (should not be under DOCUMENT_ROOT)
	 * @param bool $validatePathNotUnderDocRoot Throw \Exception if $filePath is not under DOCUMENT_ROOT
	 * @throws \Exception
	 */
	public function __construct($filePath, $validatePathNotUnderDocRoot = true) {
		if(!file_exists($filePath)) {
			if(file_put_contents($filePath, '') === false) {
				throw new \Exception('Unable to write file ' . $filePath);
			}
		}
		$this->filePath = realpath($filePath);

		if($validatePathNotUnderDocRoot && $this->isPathUnderDocRoot()) {
			throw new \Exception('Path ' . $this->filePath . ' is under DOCUMENT_ROOT. It\'s insecure!');
		}
	}

	protected function isPathUnderDocRoot() {
		return !empty($_SERVER['DOCUMENT_ROOT']) && strpos($this->filePath, $_SERVER['DOCUMENT_ROOT']) === 0;
	}

	protected function initFileHandler() {
		$this->fileHandler = fopen($this->filePath, 'a+b');
		if(!$this->fileHandler) {
			throw new \Exception('Unable to read/write file ' . $this->filePath);
		}
		while(!flock($this->fileHandler, LOCK_EX | LOCK_NB)) {
			usleep(10000);
		}
		fseek($this->fileHandler, 0);
	}

	/**
	 * @throws \Exception
	 * @return array
	 */
	protected function getKeysData() {
		return json_decode(fgets($this->fileHandler), true) ? : array();
	}

	/**
	 * @param array $keysData
	 */
	protected function saveKeysData(array $keysData) {
		ftruncate($this->fileHandler, 0);
		fwrite($this->fileHandler, json_encode($keysData, defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : null));
	}

	protected function closeFileHandler() {
		if($this->fileHandler) {
			flock($this->fileHandler, LOCK_UN);
			fclose($this->fileHandler);
			$this->fileHandler = null;
		}
	}

	public function pop($key) {
		$this->initFileHandler();
		$result = parent::pop($key);
		$this->closeFileHandler();
		return $result;
	}

	public function push($key, $data) {
		$this->initFileHandler();
		parent::push($key, $data);
		$this->closeFileHandler();
	}

	public function __destruct() {
		$this->closeFileHandler();
	}
}
