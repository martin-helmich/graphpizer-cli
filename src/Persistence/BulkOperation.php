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

use Helmich\Graphizer\Persistence\Op\NodeMatcher;
use Helmich\Graphizer\Persistence\Op\Operation;

/**
 * Helper class for executing a (potentially very large) set of queries
 *
 * @package    Helmich\Graphizer
 * @subpackage Persistence
 */
class BulkOperation {

	/**
	 * @var Operation[]
	 */
	protected $operations = [];

	/**
	 * @var Backend
	 */
	protected $backend;

	/**
	 * @var int
	 */
	private $chunkSize;

	/**
	 * @param Backend $backend
	 * @param int     $chunkSize
	 */
	public function __construct(Backend $backend, $chunkSize = 1000) {
		$this->backend   = $backend;
		$this->chunkSize = $chunkSize;
	}

	/**
	 * @param Operation $operation
	 */
	public function push(Operation $operation) {
		$this->operations[] = $operation;
	}

	/**
	 * @return TypedResultRowAdapter[]
	 */
	public function evaluate() {
		if (0 === count($this->operations)) {
			return NULL;
		}

		/** @var Operation[][] $chunks */
		$chunks     = array_chunk($this->operations, $this->chunkSize);
		$lastResult = NULL;

		foreach ($chunks as $chunk) {
			$cypher     = '';
			$arguments  = [];
			$knownNodes = [];

			foreach ($chunk as $operation) {
				if ($operation instanceof NodeMatcher) {
					$knownNodes[$operation->getId()] = $operation->getId();
				}

				foreach ($operation->getRequiredNodes() as $requiredNode) {
					if (!isset($knownNodes[$requiredNode->getId()])) {
						$knownNodes[$requiredNode->getId()] = $requiredNode->getId();

						$matcher   = $requiredNode->getMatcher();
						$cypher    = $matcher->toCypher() . "\n" . $cypher;
						$arguments = array_merge($arguments, $matcher->getArguments());
					}
				}

				$arguments = array_merge($arguments, $operation->getArguments());
				$cypher .= $operation->toCypher() . "\n";
			}

			$query      = $this->backend->createQuery($cypher);
			$lastResult = $query->execute($arguments);
		}

		return $lastResult;
	}
}