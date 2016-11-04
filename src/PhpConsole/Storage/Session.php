<?php

namespace PhpConsole\Storage;

/**
 * $_SESSION storage for postponed response data. Is used by default.
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://consle.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
class Session extends AllKeysList {

	protected $sessionKey;

	/**
	 * @param string $sessionKey Key name in $_SESSION variable
	 * @param bool $autoStart Start session if it's not started
	 */
	public function __construct($sessionKey = '__PHP_Console_postponed', $autoStart = true) {
        	if($autoStart && (defined('PHP_SESSION_ACTIVE') ? session_status() != PHP_SESSION_ACTIVE : !session_id()) && !headers_sent()) {
        		session_start();
		}
		register_shutdown_function('session_write_close'); // force saving session data if session handler is overridden
		$this->sessionKey = $sessionKey;
	}

	protected function getKeysData() {
		return isset($_SESSION[$this->sessionKey]) ? $_SESSION[$this->sessionKey] : array();
	}

	protected function saveKeysData(array $keysData) {
		$_SESSION[$this->sessionKey] = $keysData;
	}
}
