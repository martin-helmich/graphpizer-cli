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

namespace Helmich\Graphizer\Modeler;

use Helmich\Graphizer\Persistence\Backend;

class DependencyAnalyzer {

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function run() {
		$this->backend->execute(
			'MATCH (p1)-[:CONTAINS_CLASS]->(c1)-->(c2)<-[:CONTAINS_CLASS]-(p2)
			      WHERE (c1:Class OR c1:Interface OR c1:Trait) AND (c2:Class OR c2:Interface OR c2:Trait) AND p1 <> p2
			 MERGE (p1)-[:DEPENDS_ON]->(p2)'
		);
		$this->backend->execute(
			'MATCH (p1)-[:CONTAINS_CLASS]->(c1)-->(:Type)-[:IS]->(c2)<-[:CONTAINS_CLASS]-(p2)
			      WHERE (c1:Class OR c1:Interface OR c1:Trait) AND (c2:Class OR c2:Interface OR c2:Trait) AND p1 <> p2
			 MERGE (p1)-[:DEPENDS_ON]->(p2)'
		);
		$this->backend->execute(
			'MATCH (p1)-[:CONTAINS_CLASS]->(c1)-->(:Type)-[:IS_COLLECTION_OF]->(:Type)-[:IS]->(c2)<-[:CONTAINS_CLASS]-(p2)
			      WHERE (c1:Class OR c1:Interface OR c1:Trait) AND (c2:Class OR c2:Interface OR c2:Trait) AND p1 <> p2
			 MERGE (p1)-[:DEPENDS_ON]->(p2)'
		);
	}
} 