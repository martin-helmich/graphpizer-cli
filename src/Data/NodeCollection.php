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

namespace Helmich\Graphizer\Data;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Helmich\Graphizer\Persistence\BackendInterface;
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

	public function __construct(BackendInterface $backend, Node $node) {
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