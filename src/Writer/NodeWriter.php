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
use Helmich\Graphizer\Persistence\Op\CreateEdge;
use Helmich\Graphizer\Persistence\Op\CreateNode;
use Helmich\Graphizer\Persistence\Op\NodeMatcher;
use Helmich\Graphizer\Persistence\Op\ReturnObject;
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
//		$colId = uniqid('node');
		$collectionOp = new CreateNode('Collection', ['fileRoot' => TRUE]);

		$bulk = new Bulk($this->backend);
		$bulk->push($collectionOp);

		$i = 0;
		foreach ($nodes as $node) {
			$nodeOp = $this->writeNodeInner($node, $bulk);
			$bulk->push($collectionOp->relate('HAS', $nodeOp, ['ordering' => $i]));
//			$bulk->push(new CreateEdge($collectionOp, $nodeOp, 'HAS', ['ordering' => $i]));
//			$bulk->push("CREATE ({$colId})-[:HAS{ordering: {$i}}]->({$nodeId})");

			$i++;
		}

		$bulk->push(new ReturnObject($collectionOp));
//		$bulk->push("RETURN ${colId}");

		return $bulk->evaluate()[0]->node($collectionOp->getId());
	}

	public function writeNode(Node $node) {
		$bulk = new Bulk($this->backend);

		$nodeOp = $this->writeNodeInner($node, $bulk);

		$bulk->push(new ReturnObject($nodeOp));
//		$bulk->push("RETURN ${nodeId}");
		return $bulk->evaluate()[0]->node($nodeOp->getId());
	}

	/**
	 * @param Node $node
	 * @param Bulk $bulk
	 * @return NodeMatcher
	 */
	public function writeNodeInner(Node $node, Bulk $bulk) {
		$commentText = $node->getDocComment() ? $node->getDocComment()->getText() : NULL;

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

//		$nodeId = uniqid('node');
//		$args   = ["prop_{$nodeId}" => $properties];
//		$cypher = "CREATE ({$nodeId}:{$node->getType()}{prop_{$nodeId}}) ";

		$createOp = new CreateNode($node->getType(), $properties);
		$bulk->push($createOp);

//		$bulk->push($cypher, $args);

		$this->storeNodeComments($node, $createOp, $bulk);

		foreach ($node->getSubNodeNames() as $subNodeName) {
			$subNode = $node->{$subNodeName};
			if (is_array($subNode)) {
				if ($this->isScalarArray($subNode)) {
					if (!empty($subNode)) {
						$createOp->setProperty($subNodeName, $subNode);
//						$bulk->mergeArgument("prop_{$nodeId}", [$subNodeName => $subNode]);
					} else {
						$createOp->setProperty($subNodeName, '~~EMPTY_ARRAY~~');
//						$bulk->mergeArgument("prop_{$nodeId}", [$subNodeName => '~~EMPTY_ARRAY~~']);
					}
				} else {
					$collection   = NULL;
//					$collectionId = NULL;
					$collectionOp = NULL;

					foreach ($subNode as $i => $realSubNode) {
						if ($collectionOp === NULL) {
//							$collectionId = uniqid('node');
//							$cypher       = "CREATE ({$collectionId}:Collection)";
							$collectionOp = new CreateNode('Collection');
							$bulk->push($collectionOp);
//							$bulk->push($cypher);
						}

						if (is_scalar($realSubNode)) {
//							$subNodeId = uniqid('node');
							$subNodeOp = new CreateNode('Literal', ['value' => $realSubNode]);
							$bulk->push($subNodeOp);
//							$bulk->push(
//								"CREATE (${subNodeId}:Literal{prop_{$subNodeId}})",
//								["prop_{$subNodeId}" => ['value' => $realSubNode]]
//							);
						} else if ($realSubNode === NULL) {
							continue;
						} else {
							$subNodeOp = $this->writeNodeInner($realSubNode, $bulk);
						}

						$bulk->push($collectionOp->relate('HAS', $subNodeOp, ['ordering' => $i]));
//						$bulk->push(new CreateEdge($collectionOp, $subNodeOp, 'HAS', ['ordering' => $i]));
//						$bulk->push("CREATE ({$collectionId})-[:HAS{ordering: $i}]->({$subNodeId})");
					}

					if ($collectionOp !== NULL) {
						$bulk->push($createOp->relate('SUB', $collectionOp, ['type' => $subNodeName]));
//						$bulk->push(new CreateEdge($createOp, $collectionOp, 'SUB'));
//						$bulk->push("CREATE ({$nodeId})-[:SUB{type: \"{$subNodeName}\"}]->({$collectionId})");
					}
				}
			} elseif ($subNode instanceof Node) {
				$subNodeOp = $this->writeNodeInner($subNode, $bulk);
				$bulk->push($createOp->relate('SUB', $subNodeOp, ['type' => $subNodeName]));
//				$bulk->push(new CreateEdge($createOp, $subNodeOp, 'SUB', ['type' => $subNodeName]));
//				$bulk->push("CREATE ({$nodeId})-[:SUB{type: \"{$subNodeName}\"}]->({$subNodeId})");
			} else {
				$createOp->setProperty($subNodeName, $subNode);
//				$bulk->mergeArgument("prop_{$nodeId}", [$subNodeName => $subNode]);
			}
		}

		return $createOp;
//		return $nodeId;
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

	private function storeNodeComments(Node $phpNode, NodeMatcher $nodeOp, Bulk $bulk) {
		if ($phpNode->hasAttribute('comments')) {
			foreach ($phpNode->getAttribute('comments') as $comment) {
				$commentOp = $this->commentWriter->writeComment($comment, $bulk);
				$bulk->push($nodeOp->relate('HAS_COMMENT', $commentOp));
//				$bulk->push(new CreateEdge($nodeOp, $commentOp, 'HAS_COMMENT'));
//				$bulk->push("CREATE ({$nodeId})-[:HAS_COMMENT]->({$id})");
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