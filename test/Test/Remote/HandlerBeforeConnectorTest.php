<?php

namespace PhpConsole\Test\Remote;

class HandlerBeforeConnector extends HandlerTest {

	protected function setUpConnector() {
		$this->request->addScript('init_default_handler');
		$this->request->addScript('init_default_connector');
	}
}
