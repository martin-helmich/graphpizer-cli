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

namespace Helmich\Graphizer\Reader;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node as NeoNode;
use Everyman\Neo4j\Relationship;
use Helmich\Graphizer\Data\NodeCollection;
use Helmich\Graphizer\Persistence\BackendInterface;
use PhpParser\Node as PhpNode;

class NodeReader implements NodeReaderInterface {

	/**
	 * @var BackendInterface
	 */
	private $backend;

	/**
	 * @var CommentReader
	 */
	private $commentReader;

	public function __construct(BackendInterface $backend, CommentReader $commentReader) {
		$this->backend       = $backend;
		$this->commentReader = $commentReader;
	}

	public function readNode(NeoNode $nn) {
		if ($this->isCollection($nn)) {
			$nodes = [];
			foreach ((new NodeCollection($this->backend, $nn))->getChildNodes() as $childNode) {
				$nodes[] = $this->readNode($childNode);
			}
			return $nodes;
		} else if (($class = $this->getClassForNode($nn)) !== NULL) {
			$class = new \ReflectionClass($class);

			/** @var PhpNode $instance */
			$instance = $class->newInstanceWithoutConstructor();
			$rprop    = $class->getProperty('attributes');
			$rprop->setAccessible(TRUE);
			$rprop->setValue($instance, []);

			$this->populateSubNodes($nn, $instance);
			$this->populateComments($nn, $instance);

			return $instance;
		} else if ($this->doesNodeHaveLabel($nn, 'Literal')) {
			return $nn->getProperty('value');
		} else {
			throw new \Exception('Do not know what to do with node #' . $nn->getId());
		}
	}

	private function doesNodeHaveLabel(NeoNode $nn, $labelName) {
		foreach ($nn->getLabels() as $label) {
			if ($label instanceof Label && $label->getName() === $labelName) {
				return TRUE;
			}
		}
		return FALSE;
	}

	private function isCollection(NeoNode $nn) {
		return $this->doesNodeHaveLabel($nn, NodeCollection::NODE_NAME);
	}

	private function getClassForNode(NeoNode $nn) {
		foreach ($nn->getLabels() as $label) {
			/** @var Label $label */
			$baseClassName       = 'PhpParser\\Node\\' . str_replace('_', '\\', $label->getName());
			$potentialClassNames = [
				$baseClassName,
				$baseClassName . '_'
			];

			foreach ($potentialClassNames as $potentialClassName) {
				if (class_exists($potentialClassName)) {
					return $potentialClassName;
				}
			}
		}
		return NULL;
	}

	/**
	 * @param NeoNode $nn
	 * @param         $instance
	 * @throws \Exception
	 */
	private function populateSubNodes(NeoNode $nn, PhpNode $instance) {
		$properties = $nn->getProperties();
		$subNodeNames = $instance->getSubNodeNames();

		foreach ($properties as $key => $value) {
			if (in_array($key, $subNodeNames)) {
				if ($value === '~~EMPTY_ARRAY~~') {
					$value = [];
				}
				$instance->{$key} = $value;
			}
		}

		/** @var Relationship $subNodeRel */
		foreach ($nn->getRelationships('SUB', Relationship::DirectionOut) as $subNodeRel) {
			$endNode = $subNodeRel->getEndNode();
			$subNodeName = $subNodeRel->getProperty('type');

			$instance->{$subNodeName} = $this->readNode($endNode);
		}
	}

	private function populateComments(NeoNode $nn, PhpNode $instance) {
		$cypher = 'MATCH (n)-[:HAS_COMMENT]->(c:Comment) WHERE id(n)={node} RETURN c';
		$query = $this->backend->createQuery($cypher, 'c');

		$comments = [];
		foreach($query->execute(['node' => $nn]) as $commentNode) {
			$comments[] = $this->commentReader->readComment($commentNode);
		}

		$instance->setAttribute('comments', $comments);
	}
}