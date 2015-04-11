<?php
namespace Helmich\Graphizer\Writer;

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
//							if (is_string($realSubNode)) {
//								$realSubNode = new Node\Scalar\String_($realSubNode);
//							} elseif (is_int($realSubNode) || is_long($realSubNode)) {
//								$realSubNode = new Node\Scalar\LNumber($realSubNode);
//							} elseif (is_double($realSubNode) || is_float($realSubNode)) {
//								$realSubNode = new Node\Scalar\DNumber($realSubNode);
//							} else {
//								throw new \Exception('Unconsidered scalar type "' . gettype($realSubNode) . '"!');
//							}

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

		unset($properties['comments']);

//		$newComments = [];
//		foreach((array)$properties['comments'] as $comment) {
//			$newComments[] = [
//				'line' => $comment->getLine(),
//				'text' => $comment->getText(),
//				'isDoc' => $comment instanceof Doc
//			];
//		}
//
//		$properties['comments'] = $newComments;

		return $properties;
	}
}