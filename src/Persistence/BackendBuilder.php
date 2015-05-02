<?php

/*
 * GraPHPizer - Store PHP syntax trees in a Neo4j database
 * Copyright (C) 2015  Martin Helmich <kontakt@martin-helmich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Helmich\Graphizer\Persistence;

use Everyman\Neo4j\Client;

/**
 * Helper class for easily building new Neo4j backends.
 *
 * @package    Helmich\Graphizer
 * @subpackage Persistence
 */
class BackendBuilder {

	protected $host          = 'localhost';

	protected $port          = 7474;

	protected $user          = NULL;

	protected $password      = NULL;

	protected $debugger      = NULL;

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

	/**
	 * @param string $user
	 * @return self
	 */
	public function setUser($user) {
		$this->user = $user;
		return $this;
	}

	/**
	 * @param string $password
	 * @return self
	 */
	public function setPassword($password) {
		$this->password = $password;
		return $this;
	}

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

		if ($this->user !== NULL) {
			$client->getTransport()->setAuth($this->user, $this->password);
		}

		return new Backend($client, $this->debugger);
	}

	/**
	 * @return Client
	 * @codeCoverageIgnore
	 */
	private function createClient() {
		if ($this->clientFactory) {
			return call_user_func($this->clientFactory, $this->host, $this->port);
		} else {
			return new Client($this->host, $this->port);
		}
	}
}