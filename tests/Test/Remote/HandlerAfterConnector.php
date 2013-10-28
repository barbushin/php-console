<?php

namespace PhpConsole\Test\Remote;

class HandlerAfterConnector extends Handler {

	protected function setUpConnector() {
		$this->request->addScript('init_default_connector');
		$this->request->addScript('init_default_handler');
	}
}
