<?php

namespace PhpConsole\Test\Storage;

use PhpConsole\Storage\File;

class FileTest extends Test {

	protected $filePath;

	/**
	 * @param bool $validatePathUnderDocRoot
	 * @return \PhpConsole\Storage
	 */
	protected function initStorage($validatePathUnderDocRoot = false) {
		$this->filePath = realpath(\PhpConsole\Test\TMP_DIR) . '/file_storage_test.data';
		if(file_exists($this->filePath)) {
			unlink($this->filePath);
		}
		return new File($this->filePath, $validatePathUnderDocRoot);
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
