<?php

namespace PhpConsole\Test\Remote;

class HandlerBeforeConnector extends Handler {

	protected function setUpConnector() {
		$this->request->addScript('init_default_handler');
		$this->request->addScript('init_default_connector');
	}
}
