<?php

namespace PhpConsole\Test;

abstract class Dispatcher extends Test {

	/** @var \PhpConsole\Connector|\PHPUnit_Framework_MockObject_MockObject */
	protected $connector;
	/** @var \PhpConsole\Dispatcher|\PHPUnit_Framework_MockObject_MockObject */
	protected $dispatcher;
	protected $isDispatcherActive = true;

	/**
	 * @param \PhpConsole\Connector $connector
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	abstract protected function initDispatcher(\PhpConsole\Connector $connector);

	protected function setUp() {
		parent::setUp();
		$this->connector = $this->initConnectorMock();
		$this->dispatcher = $this->initDispatcher($this->connector);
	}

	protected function initConnectorMock() {
		$connector = $this->getMockBuilder('\PhpConsole\Connector')
			->disableOriginalConstructor()
			->setMethods(array('sendMessage', 'isActiveClient'))
			->getMock();

		$isDispatcherActive =& $this->isDispatcherActive;
		$connector->expects($this->any())
			->method('isActiveClient')
			->will($this->returnCallback(function () use (&$isDispatcherActive) {
				return $isDispatcherActive;
			}));

		return $connector;
	}
}
