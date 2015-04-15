<?php
namespace Helmich\Graphizer\Persistence;

use Everyman\Neo4j\Client;

class BackendBuilder {

	protected $host     = 'localhost';

	protected $port     = 7474;

	protected $user     = NULL;

	protected $password = NULL;

	protected $debugger = NULL;

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

	/**
	 * @param null $user
	 * @return self
	 */
	public function setUser($user) {
		$this->user = $user;
		return $this;
	}

	/**
	 * @param null $password
	 * @return self
	 */
	public function setPassword($password) {
		$this->password = $password;
		return $this;
	}

	public function setDebugger(DebuggerInterface $debugger) {
		$this->debugger = $debugger;
		return $this;
	}

	public function build() {
		$client = new Client($this->host, $this->port);

		if ($this->user !== NULL) {
			$client->getTransport()->setAuth($this->user, $this->password);
		}

		return new Backend($client, $this->debugger);
	}
}