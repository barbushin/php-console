<?php

namespace PhpConsole\Test;

use PhpConsole\EvalProvider;

class EvalProviderTest extends Test {

	/** @var  EvalProvider */
	protected $evalProvider;

	protected function setUp() {
		parent::setUp();
		$this->evalProvider = new EvalProvider();
	}

	public static function provideEvalCodeAndExpectedReturns() {
		return array(
			// forceEndingSemicolon
			array('return 123', 123),
			array('return 123;', 123),
			array('if(true) { return 123; }', 123),
			// trimPhpTags
			array('<?php return 123; ?>', 123),
			array('<?PHP return 123; ?>', 123),
			array('<? return 123; ?>', 123),
			array('<? return 123;', 123),
			// output
			array('echo 123', null, 123),
			array('print_r(array(1,2))', null, print_r(array(1, 2), true)),
		);
	}

	/**
	 * @dataProvider provideEvalCodeAndExpectedReturns
	 */
	public function testEvaluate($code, $expectedReturn, $expectedOutput = '') {
		$result = $this->evalProvider->evaluate($code);
		$this->assertEquals($expectedReturn, $result->return);
		$this->assertEquals($expectedOutput, $result->output);
		$this->assertNull($result->exception);
	}

	public function testEvalExecuteMethodIsStatic() {
		$this->assertFalse($this->evalProvider->evaluate('return isset($this)')->return);
	}

	public function testTimeInResult() {
		$time = $this->evalProvider->evaluate('usleep(10000)')->time;
		$this->assertTrue($time > 0 && $time < 1);
	}

	public function testSharedVarOverwritesGlobal() {
		$_POST = array(123);
		$this->evalProvider->addSharedVar('_POST', array(321));
		$this->assertEquals(321, $this->evalProvider->evaluate('return $_POST[0]')->return);
	}

	public function testGlobalVarsBackup() {
		$_POST = array(123);
		$this->evalProvider->evaluate('$_POST = array(321)');
		$this->assertEquals(array(123), $_POST);
	}

	public function testAddSharedVar() {
		$this->evalProvider->addSharedVar('asd', 123);
		$this->assertEquals(123, $this->evalProvider->evaluate('return $asd')->return);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testAddSameSharedVarThrowsException() {
		$this->evalProvider->addSharedVar('asd', 123);
		$this->evalProvider->addSharedVar('asd', 123);
	}

	public function testAddSharedVarReference() {
		$var = 123;
		$this->evalProvider->addSharedVarReference('asd', $var);
		$var = 321;
		$this->assertEquals(321, $this->evalProvider->evaluate('return $asd')->return);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testAddSameSharedVarReferenceThrowsException() {
		$var = 123;
		$this->evalProvider->addSharedVar('asd', $var);
		$this->evalProvider->addSharedVarReference('asd', $var);
	}

	public function testAddCodeHandler() {
		$this->evalProvider->addCodeHandler(function (&$code) {
			$code = 'return 321';
		});
		$this->assertEquals(321, $this->evalProvider->evaluate('return 123')->return);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testAddNotCallableCodeHandlerThrowsException() {
		$this->evalProvider->addCodeHandler(123);
	}

	public function testThrowedInCodeExceptionIsCaught() {
		$result = $this->evalProvider->evaluate('throw new \Exception(123)');
		$this->assertEquals(123, $result->exception->getMessage());
	}
}
