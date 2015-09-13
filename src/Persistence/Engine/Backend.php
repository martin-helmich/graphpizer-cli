<?php
namespace Helmich\Graphizer\Persistence\Engine;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Request;
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

	public function __construct(Client $client,
		$host,
		$port,
		DebuggerInterface $debugger = NULL
	) {
		$this->client   = $client;
		$this->host     = $host;
		$this->port     = $port;
		$this->debugger = $debugger ? $debugger : new NullDebugger();

		$this->baseUrl = 'http://' . $this->host . ':' . $this->port;
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

		$response = $this->client->put($this->buildUrl($project), ['json' => $json]);
		$this->knownProjects[$project->getSlug()] = $checksum;

		if ($response->getStatusCode() >= 400) {
			throw new \Exception($response->getBody());
		}

		$this->debugger->projectUpserted($project);
	}

	public function wipe(ProjectConfiguration $project) {
		$response = $this->client->post($this->buildUrl($project, '/wipe'));
		if ($response->getStatusCode() >= 400) {
			throw new \Exception($response->getBody());
		}
	}

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
	 * @param ProjectConfiguration $project
	 * @return BulkOperation
	 */
	public function createBulkOperation(ProjectConfiguration $project) {
		return new JsonBulkOperation($project, $this);
	}

	/**
	 * @param ProjectConfiguration $project
	 * @param string               $filename
	 * @param string               $checksum
	 * @return bool
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
