<?php
namespace Helmich\Graphizer\Persistence\Engine;

use GuzzleHttp\Client;
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

	/** @var string */
	private $project;

	public function __construct(Client $client,
		$host,
		$port,
		$project = 'default',
		DebuggerInterface $debugger = NULL
	) {
		$this->client   = $client;
		$this->host     = $host;
		$this->port     = $port;
		$this->project  = $project;
		$this->debugger = $debugger ? $debugger : new NullDebugger();

		$this->baseUrl = 'http://' . $this->host . ':' . $this->port;
	}

	public function wipe() {
		$response = $this->client->post($this->baseUrl . '/projects/' . $this->project . '/wipe');
		if ($response->getStatusCode() >= 400) {
			throw new \Exception($response->getBody());
		}
	}

	public function import($data) {
		$uri = $this->baseUrl . '/projects/' . $this->project . '/import/start';
		if (!json_encode($data)) {
			var_dump($data);
			var_dump(json_last_error_msg());
			throw new \Exception('Fuck off, PHP!');
		}

		$this->debugger->queryExecuting($uri, $data);

		$response = $this->client->post($uri, ['json' => $data]);
		if ($response->getStatusCode() >= 400) {
			throw new \Exception($response->getBody());
		}

		$this->debugger->queryExecuted($uri, $data);
	}

	/**
	 * @return BulkOperation
	 */
	public function createBulkOperation() {
		return new JsonBulkOperation($this);
	}

	/**
	 * @param string $filename
	 * @param string $checksum
	 * @return bool
	 */
	public function isFileUnchanged($filename, $checksum) {
		$uri = $this->baseUrl . '/projects/' . $this->project . '/files/' . ltrim($filename, '/');

		$response = $this->client->head($uri, ['headers' => ['ETag' => $checksum]]);
		return $response->getStatusCode() == 304;
	}


}
