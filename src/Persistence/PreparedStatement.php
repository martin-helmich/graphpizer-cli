<?php
namespace Helmich\Graphizer\Persistence;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\ResultSet;

class PreparedStatement {

	/**
	 * @var Client
	 */
	private $client;

	private $cypher;

	/**
	 * @var null
	 */
	private $resultVar;

	/**
	 * @var DebuggerInterface
	 */
	private $debugger;

	public function __construct(Client $client, $cypher, $resultVar = NULL, DebuggerInterface $debugger = NULL) {
		$this->client = $client;
		$this->cypher = $cypher;
		$this->resultVar = $resultVar;
		$this->debugger = $debugger ? $debugger : new NullDebugger();
	}

	/**
	 * @param array $parameters
	 * @return ResultSet|TypedResultRowAdapter[]|\Everyman\Neo4j\Node[]
	 */
	public function execute(array $parameters=[]) {
		foreach ($parameters as $key => $value) {
			if ($value instanceof Node) {
				$parameters[$key] = $value->getId();
			}
		}

		$query = new Query($this->client, $this->cypher, $parameters);
		$result = new TypedResultSetProxy($query->getResultSet());

		$this->debugger->queryExecuted($this->cypher, $parameters);

		if ($this->resultVar !== NULL) {
			return new SinglePropertyFilter($result, $this->resultVar);
		} else {
			return $result;
		}
	}
}