<?php
/*
 * GraPHPizer source code analytics engine (cli component)
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

namespace Helmich\Graphizer\Persistence\Engine;

use GuzzleHttp\Client;
use Helmich\Graphizer\Configuration\ProjectConfiguration;
use Helmich\Graphizer\Persistence\BackendInterface;
use Helmich\Graphizer\Persistence\BulkOperation;
use Helmich\Graphizer\Persistence\DebuggerInterface;
use Helmich\Graphizer\Persistence\NullDebugger;

class Backend implements BackendInterface {

	/** @var Client */
	private $client;

	/** @var string */
	private $host;

	/** @var int */
	private $port;

	/** @var DebuggerInterface */
	private $debugger;

	/** @var array */
	private $knownProjects = [];

	/**
	 * @param Client            $client   An HTTP client object
	 * @param string            $host     The GraPHPizer server hostname
	 * @param string            $port     The GraPHPizer server port
	 * @param DebuggerInterface $debugger A debugger instance
	 */
	public function __construct(Client $client, $host, $port, DebuggerInterface $debugger = NULL) {
		$this->client   = $client;
		$this->host     = $host;
		$this->port     = $port;
		$this->debugger = $debugger ? $debugger : new NullDebugger();

		$this->baseUrl = 'http://' . $this->host . ':' . $this->port;
	}

	/**
	 * Returns the raw HTTP client
	 *
	 * @return Client
	 */
	public function getClient() {
		return $this->client;
	}

	/**
	 * Creates or updates a project on the remote server
	 *
	 * @param ProjectConfiguration $project The project to create/update
	 * @return void
	 * @throws \Exception
	 */
	public function upsertProject(ProjectConfiguration $project) {
		$json     = $project->jsonSerialize();
		$checksum = sha1(json_encode($json));

		if (isset($this->knownProjects[$project->getSlug()]) && $this->knownProjects[$project->getSlug()] == $checksum) {
			return;
		}

		$response                                 = $this->client->put($this->buildUrl($project), ['json' => $json]);
		$this->knownProjects[$project->getSlug()] = $checksum;

		if ($response->getStatusCode() >= 400) {
			throw new \Exception($response->getBody());
		}

		$this->debugger->projectUpserted($project);
	}

	/**
	 * Removes all data of a project
	 *
	 * @param ProjectConfiguration $project The project for which to delete all data
	 * @return void
	 * @throws \Exception
	 */
	public function wipe(ProjectConfiguration $project) {
		$response = $this->client->post($this->buildUrl($project, '/wipe'));
		if ($response->getStatusCode() >= 400) {
			throw new \Exception($response->getBody());
		}
	}

	/**
	 * Imports an AST data set for a project
	 *
	 * @param ProjectConfiguration $project The project to import data into
	 * @param array                $data    The dataset to import
	 * @return void
	 * @throws \Exception
	 */
	public function import(ProjectConfiguration $project, $data) {
		$uri = $this->buildUrl($project, '/import/start');
		if (!json_encode($data)) {
			throw new \Exception(json_last_error_msg());
		}

		$this->debugger->queryExecuting($uri, $data);

		$response = $this->client->post($uri, ['json' => $data]);
		if ($response->getStatusCode() >= 400) {
			throw new \Exception($response->getBody());
		}

		$this->debugger->queryExecuted($uri, $data);
	}

	/**
	 * Creates a new bulk operation object
	 *
	 * @param ProjectConfiguration $project The project for which to create operations.
	 * @return BulkOperation The bulk operation
	 */
	public function createBulkOperation(ProjectConfiguration $project) {
		return new JsonBulkOperation($project, $this);
	}

	/**
	 * Checks if a file was changed since the last import
	 *
	 * @param ProjectConfiguration $project  The project
	 * @param string               $filename The filename
	 * @param string               $checksum The file's checksum
	 * @return bool TRUE if the file is unchanged, otherwise FALSE
	 */
	public function isFileUnchanged(ProjectConfiguration $project, $filename, $checksum) {
		$uri      = $this->buildUrl($project, '/files/?', array(ltrim($filename, '/')));
		$request  = new Request('HEAD', $uri, ['ETag' => $checksum], NULL, ['exceptions' => FALSE]);
		$response = $this->client->send($request);

		return $response->getStatusCode() == 304;
	}

	/**
	 * Helper function for building escaped URLs.
	 *
	 * @param ProjectConfiguration $project
	 * @param string               $path
	 * @param array                $args
	 * @return string
	 */
	private function buildUrl(ProjectConfiguration $project, $path = '', array $args = []) {
		foreach ($args as $arg) {
			$path = preg_replace(',\?,', urlencode($arg), $path, 1);
		}

		if ($path !== '') {
			$path = '/' . ltrim($path, '/');
		}

		$uri = $this->baseUrl . '/projects/' . urlencode($project->getSlug()) . $path;
		return $uri;
	}


}
