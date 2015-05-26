<?php
namespace Helmich\Graphizer\Persistence\Neo4j;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;
use Helmich\Graphizer\Persistence\BackendInterface;
use Helmich\Graphizer\Persistence\BulkOperation;
use Helmich\Graphizer\Persistence\DebuggerInterface;
use Helmich\Graphizer\Persistence\NullDebugger;
use Helmich\Graphizer\Persistence\PreparedStatement;
use Persistence\Neo4j\CypherBulkOperation;

class Backend implements BackendInterface {

	/**
	 * @var Client
	 */
	private $client;

	/**
	 * @var DebuggerInterface
	 */
	private $debugger;

	public function __construct(Client $client, DebuggerInterface $debugger = NULL) {
		$this->client = $client;

		if (NULL !== $debugger) {
			$this->debugger = $debugger;
		} else {
			$this->debugger = new NullDebugger();
		}
	}

	public function createNode(array $properties = [], ...$labels) {
		$node = $this->client->makeNode($properties);
		$node->save();
		$node->addLabels(array_map(function($name) { return $this->client->makeLabel($name); }, $labels));

		$this->debugger->nodeCreated($node->getId(), $labels);

		return $node;
	}

	public function labelNode(Node $node, ...$labels){
		$node->addLabels(array_map(function($name) { return $this->client->makeLabel($name); }, $labels));
	}

	public function createQuery($cypher, $resultVar = NULL, $includeStatistics = FALSE) {
		return new PreparedStatement($this->client, $cypher, $resultVar, $includeStatistics, $this->debugger);
	}

	public function execute($cypher, array $args = [], $includeStatistics = FALSE) {
		$this->debugger->queryExecuting($cypher, $args);
		$query = new Query($this->client, $cypher, $args, $includeStatistics);
		$this->client->executeCypherQuery($query);
		$this->debugger->queryExecuted($cypher, $args);
	}

	/**
	 * @return Client
	 */
	public function getClient() {
		return $this->client;
	}

	public function wipe() {
		$this->execute('MATCH (n) OPTIONAL MATCH (n)-[r]-() DELETE n,r');
	}

	/**
	 * @return BulkOperation
	 */
	public function createBulkOperation() {
		return new CypherBulkOperation($this);
	}


}