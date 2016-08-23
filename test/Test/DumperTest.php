<?php

namespace PhpConsole\Test;

use PhpConsole\Dumper;

class DumperTest extends Test {

	/** @var  Dumper */
	protected $dumper;

	protected function setUp() {
		parent::setUp();
		$this->dumper = new Dumper();
	}

	public function testScalarItemSizeLimit() {
		$this->dumper->itemSizeLimit = 5;
		$dumpedString = $this->dumper->dump(str_repeat('x', $this->dumper->itemSizeLimit * 2));
		$this->assertEquals($this->dumper->itemSizeLimit, strlen($dumpedString));
		$this->assertStringEndsWith('...', $dumpedString);
	}

	public function testScalarDumpSizeLimit() {
		$this->dumper->dumpSizeLimit = 5;
		$dumpedString = $this->dumper->dump(str_repeat('x', $this->dumper->dumpSizeLimit * 2));
		$this->assertEquals($this->dumper->dumpSizeLimit, strlen($dumpedString));
		$this->assertStringEndsWith('...', $dumpedString);
	}

	public function testItemsCountLimit() {
		$this->dumper->itemsCountLimit = 3;
		$source = array(1, 2, 3, 4, 5);
		$expected = array(1, 2, 3, '...' => '(displayed 3 of 5)');
		$this->assertEquals($expected, $this->dumper->dump($source));
		$this->assertEquals(array(array($expected)), $this->dumper->dump(array(array($source))));
	}

	public function testLevelLimit() {
		$this->dumper->levelLimit = 2;
		$source = array(
			0,
			1 => array(2 => array(3 => array(4 => array()))),
			array(2, array(3, array(4, array()))),
		);
		$expected = array(
			0 => 0,
			1 =>
			array(
				2 => '(array)',
			),
			2 =>
			array(
				0 => 2,
				1 => '(array)',
			));
		$this->assertEquals($expected, $this->dumper->dump($source));
	}

	public function testMixedDataLimits() {
		$this->dumper->levelLimit = 3;
		$this->dumper->itemsCountLimit = 3;
		$this->dumper->itemSizeLimit = 5;

		$source = new \stdClass();
		$source->null = null;
		$source->scalar = 123456;
		$source->obj = new \stdClass();
		$source->obj->sca = 345678;
		$source->obj->arr = array(array());
		$source->array = array(
			'scalar' => 234567,
			'obj' => new \stdClass(),
			3 => array(33),
			4,
			5
		);

		$expected = array(
			':stdClass' => array(
				'null' => null,
				'scalar' => '12...',
				'obj:stdClass' => array(
					'sca' => '34...',
					'arr' => array(
						0 => '(array)',
					),
				),
				'...' => '(displayed 3 of 4)',
			));

		$this->assertEquals($expected, $this->dumper->dump($source));
	}

	public function testDumpBinaryData() {
		$binaryData = sha1(123, true);
		$this->assertEquals($binaryData, $this->dumper->dump($binaryData));
		$this->dumper->itemSizeLimit = 10;
		$this->assertEquals(substr($binaryData, 0, 7) . '...', $this->dumper->dump($binaryData));
	}

	public function testObjectProtectedPrivateProperties() {
		$this->assertEquals(array(
			':PhpConsole\Test\DumperDraftClass' => array(
				'public' => 321,
				'protected' => 123,
				'private' => 234,
			)), $this->dumper->dump(new DumperDraftClass()));
	}

	public function testObjectsRecursion() {
		$object = new \stdClass();
		$object->obj = $object;
		$this->assertEquals(array(
			':stdClass' => array(
				'obj:stdClass' => '*RECURSION*'
			)), $this->dumper->dump($object));
	}

	public function testFunctionDump() {
		$function = function () {
		};
		$this->assertEquals('(callback function)', $this->dumper->dump($function));
		$this->assertEquals(array('(callback function)'), $this->dumper->dump(array($function)));
	}

	public function testCallbacksDump() {
		$object = new DumperDraftClass();
		$source = array(
			array($object, 'publicMethod'),
			array('PhpConsole\Test\DumperDraftClass', 'staticMethod')
		);
		$this->assertEquals(array(
			0 => '(callback PhpConsole\Test\DumperDraftClass::publicMethod)',
			1 => '(callback PhpConsole\Test\DumperDraftClass::staticMethod)'
		), $this->dumper->dump($source));
	}

	public function testDumpSizeLimit() {
		$this->dumper->dumpSizeLimit = 50;
		$source = array(
			1,
			2 => array(
				21,
				22
			),
			3 => array(33),
			4,
			5
		);
		$expected = array(
			0 => 1,
			2 =>
			array(
				0 => 21,
				'...' => '(displayed 1 of 2)',
			),
			3 => array(
				0 => 33,
			),
			4 => 4,
			5 => 5,
		);
		$this->assertEquals($expected, $this->dumper->dump($source));
	}
}

class DumperDraftClass {

	public $public = 321;
	protected $protected = 123;
	private $private = 234;

	public function publicMethod() {
	}

	public static function staticMethod() {
	}
}
