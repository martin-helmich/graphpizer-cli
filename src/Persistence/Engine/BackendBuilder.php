<?php
namespace Helmich\Graphizer\Persistence\Engine;

use GuzzleHttp\Client;
use Helmich\Graphizer\Persistence\DebuggerInterface;

/**
 * Helper class for easily building new GraPHPizer backends.
 *
 * @package    Helmich\Graphizer
 * @subpackage Persistence
 */
class BackendBuilder {

	protected $host = 'localhost';

	protected $port = 7474;

//	protected $user          = NULL;
//
//	protected $password      = NULL;

	protected $debugger = NULL;

	protected $clientFactory = NULL;

	/**
	 * @param string $host
	 * @return self
	 */
	public function setHost($host) {
		$this->host = $host;
		return $this;
	}

	/**
	 * @param int $port
	 * @return self
	 */
	public function setPort($port) {
		$this->port = $port;
		return $this;
	}

//	/**
//	 * @param string $user
//	 * @return self
//	 */
//	public function setUser($user) {
//		$this->user = $user;
//		return $this;
//	}
//
//	/**
//	 * @param string $password
//	 * @return self
//	 */
//	public function setPassword($password) {
//		$this->password = $password;
//		return $this;
//	}

	/**
	 * @param DebuggerInterface $debugger
	 * @return self
	 */
	public function setDebugger(DebuggerInterface $debugger) {
		$this->debugger = $debugger;
		return $this;
	}

	/**
	 * @param callable $clientFactory
	 * @return self
	 */
	public function setClientFactory(callable $clientFactory) {
		$this->clientFactory = $clientFactory;
		return $this;
	}

	/**
	 * @return Backend
	 */
	public function build() {
		$client = $this->createClient();

		return new Backend($client, $this->host, $this->port, $this->debugger);
	}

	/**
	 * @return Client
	 * @codeCoverageIgnore
	 */
	private function createClient() {
		if ($this->clientFactory) {
			return call_user_func($this->clientFactory, $this->host, $this->port);
		} else {
			return new Client();
		}
	}
}