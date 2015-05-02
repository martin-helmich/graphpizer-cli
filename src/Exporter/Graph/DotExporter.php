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

namespace Helmich\Graphizer\Exporter\Graph;

use Everyman\Neo4j\Label;
use Helmich\Graphizer\Persistence\Backend;

class DotExporter implements ExporterInterface {

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	private function quoteIdentifier($id) {
		return '"' . str_replace('"', '\\"', $id) . '"';
	}

	public function export($withMethods = FALSE, $withProperties = FALSE, $pretty = FALSE) {
		$output = "digraph {\n";

		$q = $this->backend->createQuery('MATCH c WHERE c:Class OR c:Interface OR c:Trait RETURN c', 'c');
		foreach ($q->execute() as $node) {
			$output .= "    {$this->quoteIdentifier($node->getProperty('fqcn'))};\n";
		}

		$q =
			$this->backend->createQuery('
				MATCH (a)-[r]->(b) WHERE (a:Class OR a:Interface OR a:Trait) AND (b:Class OR b:Interface OR b:Trait) RETURN a, b, r UNION
				MATCH (a)-[r]->(t:Type)-[:IS]->(b) WHERE (a:Class OR a:Interface OR a:Trait) AND (b:Class OR b:Interface OR b:Trait) RETURN a, b, r'
			);
		foreach ($q->execute() as $row) {
			$r       = $row->relationship('r');
			$output .= "    {$this->quoteIdentifier($r->getStartNode()->getProperty('fqcn'))} -> {$this->quoteIdentifier($r->getEndNode()->getProperty('fqcn'))} [label=\"{$r->getType()}\"];\n";
		}

		$output .= "}";
		return $output;
	}
}