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

namespace Helmich\Graphizer\Exporter\Graph\Dot;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Relationship;

class CompactRenderingStrategy implements RenderingStrategy {

	use EdgeRenderer;

	public function renderClassLikeNode(Node $node) {
		return sprintf(
			'%s [label="%s", shape=circle]',
			$this->quoteIdentifier($node->getProperty('fqcn')),
			$this->classLabel($node)
		);
	}

	private function classLabel(Node $node) {
		/** @var Label $label */
		foreach ($node->getLabels() as $label) {
			if ($label->getName() === 'Class') {
				return 'C';
			} else if ($label->getName() === 'Trait') {
				return 'T';
			} else if ($label->getName() === 'Interface') {
				return 'I';
			}
		}

		return '';
	}

	public function renderRelationship(Relationship $relationship) {
		return sprintf(
			'%s -> %s [label="", arrowhead=%s, style=%s]',
			$this->quoteIdentifier($relationship->getStartNode()->getProperty('fqcn')),
			$this->quoteIdentifier($relationship->getEndNode()->getProperty('fqcn')),
			$this->getArrowheadShape($relationship),
			$this->getLineStyle($relationship)
		);
	}

	private function quoteIdentifier($identifier) {
		// Graphviz does not display the backslash character correctly.
		// Not good for namespaced classes, but what are you gonna do?
		$identifier = str_replace('\\', '_', $identifier);

		return '"' . str_replace('"', '\\"', $identifier) . '"';
	}
}