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

namespace Helmich\Graphizer\Analyzer\Metric;

use Helmich\Graphizer\Persistence\Neo4j\Backend;

class CyclomaticComplexity implements Metric {

	use AggregateableTrait;

	/** @var \Helmich\Graphizer\Persistence\Neo4j\Backend */
	protected $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function evaluate() {
		$this->backend->execute(
			'MATCH          (m:Method)
			 OPTIONAL MATCH (m)-[:DEFINED_IN]->(:Stmt_ClassMethod)-[:SUB|HAS*]->(b) WHERE
			     b:Stmt_If OR
			     b:Stmt_ElseIf OR
			     b:Stmt_Case
			 WITH m, COUNT(b) + 1 AS cc
			 SET m.cyclomaticComplexity = cc'
		);

		$this->aggregate('cyclomaticComplexity');
		$this->aggregate('cyclomaticComplexity', 'cyclomaticComplexityPerMethod', 'AVG');
	}

	public function getPropertyKey() {
		return 'cyclomaticComplexity';
	}

	public function getName() {
		return 'cyclometric complexity';
	}

	/**
	 * @return \Helmich\Graphizer\Persistence\Neo4j\Backend
	 */
	protected function getBackend() {
		return $this->backend;
	}
}