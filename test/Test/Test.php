<?php

namespace PhpConsole\Test;

use SebastianBergmann\Comparator\ComparisonFailure;

abstract class Test extends \PHPUnit_Framework_TestCase {

	protected static function getOneArgProviderData(array $oneColumnArray) {
		$calls = array();
		foreach($oneColumnArray as $arg1) {
			$calls[] = array($arg1);
		}
		return $calls;
	}

	protected static function getAssocTwoArgsProviderData(array $assocArray) {
		$calls = array();
		foreach($assocArray as $arg1 => $arg2) {
			$calls[] = array($arg1, $arg2);
		}
		return $calls;
	}

	protected function setProtectedProperty($object, $property, $value) {
		$propertyRef = new \ReflectionProperty($object, $property);
		$propertyRef->setAccessible(true);
		$propertyRef->setValue($object, $value);
	}

	protected function getProtectedProperty($objectOrClass, $property) {
		if(is_object($objectOrClass)) {
			$propertyRef = new \ReflectionProperty($objectOrClass, $property);
			$propertyRef->setAccessible(true);
			return $propertyRef->getValue($objectOrClass);
		}
		else {
			$classRef = new \ReflectionClass($objectOrClass);
			$properties = $classRef->getDefaultProperties();
			if(!isset($properties[$property])) {
				throw new \Exception('Property "' . $property . '" not found in class "' . $objectOrClass . '"');
			}
			return $properties[$property];
		}
	}

	protected function callProtectedMethod($object, $method, array &$args = array()) {
		$method = new \ReflectionMethod($object, $method);
		$method->setAccessible(true);
		return $args ? $method->invokeArgs($object, $args) : $method->invoke($object);
	}

	public function assertContainsRecursive($needle, $haystack) {
		if(is_object($needle)) {
			$needle = get_object_vars($needle);
		}
		if(is_object($haystack)) {
			$haystack = get_object_vars($haystack);
		}
		if(!is_array($needle) || !is_array($haystack)) {
			throw new \Exception('Arguments can by type of array or object');
		}
		$replacedHaystack = array_replace_recursive($haystack, $needle);
		if($haystack != $replacedHaystack) {
			throw new \PHPUnit_Framework_ExpectationFailedException('Failed asserting that two arrays are equal.',
				new ComparisonFailure($haystack, $replacedHaystack, print_r($haystack, true), print_r($replacedHaystack, true)));
		}
	}

	protected function assertIsSingleton($class, $instance = null) {
		foreach(array('__construct', '__clone') as $methodName) {
			$method = new \ReflectionMethod($class, $methodName);
			$this->assertTrue($method->isProtected() || $method->isPrivate());
		}
		$getInstance = function () use ($class) {
			return call_user_func(array($class, 'getInstance'));
		};
		if($instance) {
			$this->assertEquals(spl_object_hash($instance), spl_object_hash($getInstance()));
		}
		$this->assertEquals(spl_object_hash($getInstance()), spl_object_hash($getInstance()));
	}
}
