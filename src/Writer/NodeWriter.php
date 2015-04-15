<?php
namespace Helmich\Graphizer\Writer;

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

use Everyman\Neo4j\Node as NeoNode;
use Helmich\Graphizer\Data\NodeCollection;
use Helmich\Graphizer\Persistence\Backend;
use PhpParser\Node;

class NodeWriter implements NodeWriterInterface {

	/**
	 * @var Backend
	 */
	private $backend;

	/**
	 * @var CommentWriter
	 */
	private $commentWriter;

	public function __construct(Backend $backend, CommentWriter $writer) {
		$this->backend       = $backend;
		$this->commentWriter = $writer;
	}

	public function writeNodeCollection(array $nodes) {
		$nodes = array_map(
			function (Node $node) {
				return $this->writeNode($node);
			},
			$nodes
		);

		$collection = $this->backend->createNode(['fileRoot' => TRUE], NodeCollection::NODE_NAME);

		foreach ($nodes as $i => $node) {
			$collection
				->relateTo($node, 'HAS')
				->setProperty('ordering', $i)
				->save();
		}

		return $collection;
	}

	private function enrichNode(Node $node, NeoNode $neoNode) {
		foreach ($node->getSubNodeNames() as $subNodeName) {
			$subNode = $node->{$subNodeName};
			if ($subNode instanceof Node\Name) {
				$neoNode->setProperty($subNodeName, $subNode->toString());
			}
		}

		if ($node instanceof Node\Name) {
			$neoNode->setProperty('allParts', $node->toString());
		}
	}

	private function isScalarArray($array) {
		foreach ($array as $a) {
			if (is_scalar($a) === FALSE) {
				if (is_array($a)) {
					return $this->isScalarArray($a);
				} else {
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	private function storeNodeComments(Node $phpNode, NeoNode $neoNode) {
		if ($phpNode->hasAttribute('comments')) {
			foreach ($phpNode->getAttribute('comments') as $comment) {
				$commentNode = $this->commentWriter->writeComment($comment);
				$neoNode
					->relateTo($commentNode, 'HAS_COMMENT')
					->save();
			}
		}
	}

	/**
	 * @param Node $node
	 * @return NeoNode
	 * @throws \Everyman\Neo4j\Exception
	 */
	public function writeNode(Node $node) {
		$commentText = $node->getDocComment() ? $node->getDocComment()->getText() : NULL;

		$neoNode = $this->backend->createNode($this->getNodeProperties($node), $node->getType());
		$neoNode->setProperty('docComment', $commentText);
		$this->enrichNode($node, $neoNode);
		$neoNode->save();

		$this->storeNodeComments($node, $neoNode);

		foreach ($node->getSubNodeNames() as $subNodeName) {
			$subNode = $node->{$subNodeName};
			if (is_array($subNode)) {
				if ($this->isScalarArray($subNode)) {
					if (!empty($subNode)) {
						$neoNode->setProperty($subNodeName, $subNode);
						$neoNode->save();
					} else {
						$neoNode->setProperty($subNodeName, '~~EMPTY_ARRAY~~');
						$neoNode->save();
					}
				} else {
					$collection = NULL;

					foreach ($subNode as $i => $realSubNode) {
						if ($collection === NULL) {
							$collection = $this->backend->createNode([], NodeCollection::NODE_NAME);
						}

						if (is_scalar($realSubNode)) {
							$neoSubNode = $this->backend->createNode(['value' => $realSubNode], 'Literal');
						} else {
							$neoSubNode = $this->writeNode($realSubNode);
						}
						$collection
							->relateTo($neoSubNode, 'HAS')
							->setProperty('ordering', $i)
							->save();
					}

					if ($collection !== NULL) {
						$neoNode
							->relateTo($collection, 'SUB_' . strtoupper($subNodeName))
							->save();
					}
				}
			} elseif ($subNode instanceof Node) {
				$neoSubNode = $this->writeNode($subNode);

				$neoNode
					->relateTo($neoSubNode, 'SUB_' . strtoupper($subNodeName))
					->setProperty('ordering', 0)
					->save();
			} else {
				$neoNode->setProperty($subNodeName, $subNode);
				$neoNode->save();
			}
		}

		return $neoNode;
	}

	/**
	 * @param Node $node
	 * @return array
	 */
	protected function getNodeProperties(Node $node) {
		$properties = $node->getAttributes();

		// Comments are treated separately; they are not scalar values and
		// cannot be stored as a node attribute. Indead, we store each comment
		// as a separate node and simply store the relation.
		unset($properties['comments']);
		return $properties;
	}
}