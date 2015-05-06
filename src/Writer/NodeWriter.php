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
		$colId = uniqid('node');

		$bulk = new Bulk($this->backend);
//		$bulk->push('USING PERIODIC COMMIT 100');
		$bulk->push("CREATE ({$colId}:Collection{fileRoot: true})");

		$i = 0;
		foreach ($nodes as $node) {
			$nodeId = $this->writeNodeInner($node, $bulk);
			$bulk->push("CREATE ({$colId})-[:HAS{ordering: {$i}}]->({$nodeId})");

			$i++;
		}

		$bulk->push("RETURN ${colId}");

		return $bulk->evaluate()[0]->node($colId);
//		echo($cypher);

//		$nodes = array_map(
//			function (Node $node) {
//				return $this->writeNode($node);
//			},
//			$nodes
//		);
//
//		$collection = $this->backend->createNode(['fileRoot' => TRUE], NodeCollection::NODE_NAME);
//
//		foreach ($nodes as $i => $node) {
//			$collection
//				->relateTo($node, 'HAS')
//				->setProperty('ordering', $i)
//				->save();
//		}
//
//		return $collection;
	}

	public function writeNode(Node $node) {
		$bulk = new Bulk($this->backend);

		$nodeId = $this->writeNodeInner($node, $bulk);

		$bulk->push("RETURN ${nodeId}");
		return $bulk->evaluate()[0]->node($nodeId);
	}

	/**
	 * @param Node $node
	 * @param Bulk $bulk
	 * @return NeoNode
	 */
	public function writeNodeInner(Node $node, Bulk $bulk) {
		$commentText = $node->getDocComment() ? $node->getDocComment()->getText() : NULL;

//		$neoNode = $this->backend->createNode($this->getNodeProperties($node), $node->getType());
//		$neoNode->setProperty('docComment', $commentText);
//		$this->enrichNode($node, $neoNode);
//		$neoNode->save();

		$properties = $this->getNodeProperties($node);
		$properties = $this->enrichNodeProperties($node, $properties);

		if (NULL !== $commentText) {
			$properties['docComment'] = $commentText;
		}

		// We need to add a dummy value when the property array is empty, otherwise
		// Neo4j will not create the node
		if (empty($properties)) {
			$properties['__dummy'] = 1;
		}

		$nodeId = uniqid('node');
		$args   = ["prop_{$nodeId}" => $properties];
		$cypher = "CREATE ({$nodeId}:{$node->getType()}{prop_{$nodeId}}) ";

		$bulk->push($cypher, $args);

		$this->storeNodeComments($node, $nodeId, $bulk);

		foreach ($node->getSubNodeNames() as $subNodeName) {
			$subNode = $node->{$subNodeName};
			if (is_array($subNode)) {
				if ($this->isScalarArray($subNode)) {
					if (!empty($subNode)) {
						$bulk->mergeArgument("prop_{$nodeId}", [$subNodeName => $subNode]);
						#$neoNode->setProperty($subNodeName, $subNode);
						#$neoNode->save();
					} else {
						$bulk->mergeArgument("prop_{$nodeId}", [$subNodeName => '~~EMPTY_ARRAY~~']);
//						$neoNode->setProperty($subNodeName, '~~EMPTY_ARRAY~~');
//						$neoNode->save();
					}
				} else {
					$collection   = NULL;
					$collectionId = NULL;

					foreach ($subNode as $i => $realSubNode) {
						if ($collectionId === NULL) {
							$collectionId = uniqid('node');
							$cypher       = "CREATE ({$collectionId}:Collection)";
							$bulk->push($cypher);
//							$collection = $this->backend->createNode([], NodeCollection::NODE_NAME);
						}

						if (is_scalar($realSubNode)) {
							$subNodeId = uniqid('node');
							$bulk->push(
								"CREATE (${subNodeId}:Literal{prop_{$subNodeId}})",
								["prop_{$subNodeId}" => ['value' => $realSubNode]]
							);
//							$neoSubNode = $this->backend->createNode(['value' => $realSubNode], 'Literal');
						} else if ($realSubNode === NULL) {
							continue;
						} else {
							$subNodeId = $this->writeNodeInner($realSubNode, $bulk);
						}

						$bulk->push("CREATE ({$collectionId})-[:HAS{ordering: $i}]->({$subNodeId})");
//						$collection
//							->relateTo($neoSubNode, 'HAS')
//							->setProperty('ordering', $i)
//							->save();
					}

					if ($collectionId !== NULL) {
						$bulk->push("CREATE ({$nodeId})-[:SUB{type: \"{$subNodeName}\"}]->({$collectionId})");
//						$neoNode
//							->relateTo($collection, 'SUB_' . strtoupper($subNodeName))
//							->save();
					}
				}
			} elseif ($subNode instanceof Node) {
				$subNodeId = $this->writeNodeInner($subNode, $bulk);
				$bulk->push("CREATE ({$nodeId})-[:SUB{type: \"{$subNodeName}\"}]->({$subNodeId})");

//				$neoNode
//					->relateTo($neoSubNode, 'SUB_' . strtoupper($subNodeName))
//					->setProperty('ordering', 0)
//					->save();
			} else {
				$bulk->mergeArgument("prop_{$nodeId}", [$subNodeName => $subNode]);
//				$neoNode->setProperty($subNodeName, $subNode);
//				$neoNode->save();
			}
		}

//		return $neoNode;
		return $nodeId;
	}

	private function enrichNodeProperties(Node $node, array $properties) {
		foreach ($node->getSubNodeNames() as $subNodeName) {
			$subNode = $node->{$subNodeName};
			if ($subNode instanceof Node\Name) {
				$properties[$subNodeName] = $subNode->toString();
			}
		}

		if ($node instanceof Node\Name) {
			$properties['allParts'] = $node->toString();
		}

		return $properties;
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

	private function storeNodeComments(Node $phpNode, $nodeId, Bulk $bulk) {
		if ($phpNode->hasAttribute('comments')) {
			foreach ($phpNode->getAttribute('comments') as $comment) {
				$id = $this->commentWriter->writeComment($comment, $bulk);
				$bulk->push("CREATE ({$nodeId})-[:HAS_COMMENT]->({$id})");
//
//				$commentNode = $this->commentWriter->writeComment($comment);
//				$neoNode
//					->relateTo($commentNode, 'HAS_COMMENT')
//					->save();
			}
		}
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