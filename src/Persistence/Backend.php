<?php
namespace Helmich\Graphizer\Persistence;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;

class Backend {

	/**
	 * @var Client
	 */
	private $client;

	public function __construct(Client $client) {
		$this->client = $client;
	}

	public function createNode(array $properties = [], ...$labels) {
		$node = $this->client->makeNode($properties);
		$node->save();
		$node->addLabels(array_map(function($name) { return $this->client->makeLabel($name); }, $labels));

		return $node;
	}

	public function labelNode(Node $node, ...$labels){
		$node->addLabels(array_map(function($name) { return $this->client->makeLabel($name); }, $labels));
	}

	public function createQuery($cypher, $resultVar = NULL) {
		return new PreparedStatement($this->client, $cypher, $resultVar);
	}

	public function execute($cypher, array $args=[]) {
		$query = new Query($this->client, $cypher, $args);
		$this->client->executeCypherQuery($query);
	}

	public function wipe() {
		$this->execute('MATCH (n) OPTIONAL MATCH (n)-[r]-() DELETE n,r');
	}
}