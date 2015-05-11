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

use Helmich\Graphizer\Persistence\Backend;

trait AggregateableTrait {

	/** @var Backend */
	protected $backend;

	protected function aggregate($propertyName, $targetPropertyName = NULL, $aggregation = 'SUM') {
		if (NULL === $targetPropertyName) {
			$targetPropertyName = $propertyName;
		}
		$this->backend->execute(
			"MATCH          (c) WHERE c:Class OR c:Trait
			 OPTIONAL MATCH (c)-[:HAS_METHOD]->(m:Method)
			 WITH c, {$aggregation}(m.{$propertyName}) AS cc
			 SET c.{$targetPropertyName} = cc"
		);
	}
}