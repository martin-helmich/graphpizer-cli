<?php
namespace Helmich\Graphizer\Persistence;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\ResultSet;

class PreparedStatement {

	private $cypher;

	/**
	 * @var null
	 */
	private $resultVar;

	/**
	 * @var DebuggerInterface
	 */
	private $debugger;

	/**
	 * @var callable
	 */
	private $queryFactory;

	public function __construct(Client $client, $cypher, $resultVar = NULL, DebuggerInterface $debugger = NULL) {
		$this->cypher    = $cypher;
		$this->resultVar = $resultVar;
		$this->debugger  = $debugger ? $debugger : new NullDebugger();

		$this->queryFactory = function (array $parameters) use ($client, $cypher) {
			return new Query($client, $cypher, $parameters);
		};
	}

	public function setQueryFactory(callable $factory) {
		$this->queryFactory = $factory;
	}

	/**
	 * @param array $parameters
	 * @return ResultSet|TypedResultRowAdapter[]|\Everyman\Neo4j\Node[]
	 */
	public function execute(array $parameters = []) {
		foreach ($parameters as $key => $value) {
			if ($value instanceof Node) {
				$parameters[$key] = $value->getId();
			}
		}

		$query  = call_user_func($this->queryFactory, $parameters);
		$result = new TypedResultSetProxy($query->getResultSet());

		$this->debugger->queryExecuted($this->cypher, $parameters);

		if ($this->resultVar !== NULL) {
			return new SinglePropertyFilter($result, $this->resultVar);
		} else {
			return $result;
		}
	}
}