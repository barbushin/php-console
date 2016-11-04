<?php

namespace PhpConsole;

/**
 * PHP Console client authorization credentials & validation class
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://consle.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 * @codeCoverageIgnore
 */
class Auth {

	const PASSWORD_HASH_SALT = 'NeverChangeIt:)';

	protected $publicKeyByIp;
	protected $passwordHash;

	/**
	 * @param string $password Common password for all clients
	 * @param bool $publicKeyByIp Set public key depending on client IP
	 */
	public function __construct($password, $publicKeyByIp = true) {
		$this->publicKeyByIp = $publicKeyByIp;
		$this->passwordHash = $this->getPasswordHash($password);
	}

	protected final function hash($string) {
		return hash('sha256', $string);
	}

	/**
	 * Get password hash like on client
	 * @param $password
	 * @return string
	 */
	protected final function getPasswordHash($password) {
		return $this->hash($password . self::PASSWORD_HASH_SALT);
	}

	/**
	 * Get authorization result data for client
	 * @codeCoverageIgnore
	 * @param ClientAuth|null $clientAuth
	 * @return ServerAuthStatus
	 */
	public final function getServerAuthStatus(ClientAuth $clientAuth = null) {
		$serverAuthStatus = new ServerAuthStatus();
		$serverAuthStatus->publicKey = $this->getPublicKey();
		$serverAuthStatus->isSuccess = $clientAuth && $this->isValidAuth($clientAuth);
		return $serverAuthStatus;
	}

	/**
	 * Check if client authorization data is valid
	 * @codeCoverageIgnore
	 * @param ClientAuth $clientAuth
	 * @return bool
	 */
	public final function isValidAuth(ClientAuth $clientAuth) {
		return $clientAuth->publicKey === $this->getPublicKey() && $clientAuth->token === $this->getToken();
	}

	/**
	 * Get client unique identification
	 * @return string
	 */
	protected function getClientUid() {
		$clientUid = '';
		if($this->publicKeyByIp) {
			if(isset($_SERVER['REMOTE_ADDR'])) {
				$clientUid .= $_SERVER['REMOTE_ADDR'];
			}
			if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$clientUid .= $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
		}
		return $clientUid;
	}

	/**
	 * Get authorization session public key for current client
	 * @return string
	 */
	protected function getPublicKey() {
		return $this->hash($this->getClientUid() . $this->passwordHash);
	}

	/**
	 * Get string signature for current password & public key
	 * @param $string
	 * @return string
	 */
	public final function getSignature($string) {
		return $this->hash($this->passwordHash . $this->getPublicKey() . $string);
	}

	/**
	 * Get expected valid client authorization token
	 * @return string
	 */
	private final function getToken() {
		return $this->hash($this->passwordHash . $this->getPublicKey());
	}
}
