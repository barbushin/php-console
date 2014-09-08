<?php

namespace PhpConsole\Test\Storage;

class File extends \PhpConsole\Test\Storage {

	protected $filePath;

	/**
	 * @param bool $validatePathUnderDocRoot
	 * @return \PhpConsole\Storage
	 */
	protected function initStorage($validatePathUnderDocRoot = false) {
		$this->filePath = \PhpConsole\Test\BASE_DIR . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'file_storage_test.data';
		if(file_exists($this->filePath)) {
			unlink($this->filePath);
		}
		return new \PhpConsole\Storage\File($this->filePath, $validatePathUnderDocRoot);
	}

	protected function tearDown() {
		parent::tearDown();
		if(file_exists($this->filePath)) {
			unlink($this->filePath);
		}
	}

	/**
	 * @expectedException \Exception
	 */
	public function testPathUnderDocRootThrowsException() {
		$_SERVER['DOCUMENT_ROOT'] = dirname($this->filePath);
		$this->initStorage(true);
	}

	public function testPathNotUnderDocRoot() {
		$_SERVER['DOCUMENT_ROOT'] = $this->filePath . DIRECTORY_SEPARATOR . 'bla';
		$this->initStorage(true);
	}
}
