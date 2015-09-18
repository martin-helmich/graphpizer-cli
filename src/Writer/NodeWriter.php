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

namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Persistence\BackendInterface;
use Helmich\Graphizer\Persistence\BulkOperation;
use Helmich\Graphizer\Persistence\Neo4j\Backend;
use Helmich\Graphizer\Persistence\Op\CreateNode;
use Helmich\Graphizer\Persistence\Op\NodeMatcher;
use Helmich\Graphizer\Persistence\Op\ReturnObject;
use PhpParser\Node;

class NodeWriter implements NodeWriterInterface {

	/**
	 * @var CommentWriter
	 */
	private $commentWriter;

	public function __construct(CommentWriter $writer) {
		$this->commentWriter = $writer;
	}

	public function writeNodeCollection(array $nodes, BulkOperation $bulk) {
		$collectionOp = new CreateNode('Collection', ['fileRoot' => TRUE]);

		$bulk->push($collectionOp);

		$i = 0;
		foreach ($nodes as $node) {
			$bulk->push($collectionOp->relate('HAS', $this->writeNodeInner($node, $bulk))->ordering($i));
			$i++;
		}

		$bulk->push(new ReturnObject($collectionOp));

		return $collectionOp;
	}

	public function writeNode(Node $node, BulkOperation $bulk) {
		$bulk->push(new ReturnObject($nodeOp = $this->writeNodeInner($node, $bulk)));
		return $nodeOp;
	}

	/**
	 * @param Node          $node
	 * @param BulkOperation $bulk
	 * @return NodeMatcher
	 */
	public function writeNodeInner(Node $node, BulkOperation $bulk) {
		$commentText = $node->getDocComment() ? $node->getDocComment()->getText() : NULL;

		$properties = $this->getNodeProperties($node);
		$properties = $this->enrichNodeProperties($node, $properties);

		if (NULL !== $commentText) {
			$properties['docComment'] = $commentText;
		}

		$bulk->push($createOp = new CreateNode($node->getType(), $properties));
		$this->storeNodeComments($node, $createOp, $bulk);

		foreach ($node->getSubNodeNames() as $subNodeName) {
			$subNode = $node->{$subNodeName};
			if (is_array($subNode)) {
				if ($this->isScalarArray($subNode)) {
					if (!empty($subNode)) {
						$createOp->setProperty($subNodeName, $subNode);
					} else {
						$createOp->setProperty($subNodeName, '~~EMPTY_ARRAY~~');
					}
				} else {
					$collectionOp = NULL;

					foreach ($subNode as $i => $realSubNode) {
						if ($collectionOp === NULL) {
							$collectionOp = new CreateNode('Collection');
							$bulk->push($collectionOp);
						}

						if (is_scalar($realSubNode)) {
							$subNodeOp = (new CreateNode('Literal'))->value($realSubNode);
							$bulk->push($subNodeOp);
						} else if ($realSubNode === NULL) {
							continue;
						} else {
							$subNodeOp = $this->writeNodeInner($realSubNode, $bulk);
						}

						$bulk->push($collectionOp->relate('HAS', $subNodeOp)->ordering($i));
					}

					if ($collectionOp !== NULL) {
						$bulk->push($createOp->relate('SUB', $collectionOp)->type($subNodeName));
					}
				}
			} elseif ($subNode instanceof Node) {
				$subNodeOp = $this->writeNodeInner($subNode, $bulk);
				$bulk->push($createOp->relate('SUB', $subNodeOp)->type($subNodeName));
			} else {
				$createOp->setProperty($subNodeName, $subNode);
			}
		}

		return $createOp;
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

	private function storeNodeComments(Node $phpNode, NodeMatcher $nodeOp, BulkOperation $bulk) {
		if ($phpNode->hasAttribute('comments')) {
			foreach ($phpNode->getAttribute('comments') as $comment) {
				$commentOp = $this->commentWriter->writeComment($comment, $bulk);
				$bulk->push($nodeOp->relate('HAS_COMMENT', $commentOp));
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