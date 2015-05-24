<?php
namespace Helmich\Graphizer\Data;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Helmich\Graphizer\Persistence\Neo4j\Backend;
use Helmich\Graphizer\Persistence\PreparedStatement;

class NodeCollection {

	const NODE_NAME = 'Collection';

	/**
	 * @var Node
	 */
	private $node;

	/**
	 * @var Backend
	 */
	private $backend;

	/**
	 * @var PreparedStatement
	 */
	static private $stmt;

	public function __construct(Backend $backend, Node $node) {
		$this->assertNodeIsCollection($node);
		$this->node    = $node;
		$this->backend = $backend;

		if (static::$stmt === NULL) {
			$cypher       = 'MATCH (n)-[r:HAS]->(x) WHERE id(n)={node} RETURN x ORDER BY r.ordering ASC';
			static::$stmt = $backend->createQuery($cypher, 'x');
		}
	}

	/**
	 * @return \Everyman\Neo4j\Node[]
	 */
	public function getChildNodes() {
		return static::$stmt->execute(['node' => $this->node]);
	}

	/**
	 * @param Node $node
	 */
	private function assertNodeIsCollection(Node $node) {
		$isCollection = FALSE;
		foreach ($node->getLabels() as $label) {
			/** @var Label $label */
			if ($label->getName() === self::NODE_NAME) {
				$isCollection = TRUE;
			}
		}
		if ($isCollection === FALSE) {
			throw new \InvalidArgumentException('Node #' . $node->getId() . ' is no collection!');
		}
	}
}