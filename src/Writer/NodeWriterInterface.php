<?php
namespace Helmich\Graphizer\Writer;

use Everyman\Neo4j\Node as NeoNode;
use PhpParser\Node as PhpNode;

interface NodeWriterInterface {

	/**
	 * @param PhpNode[] $nodes
	 * @return NeoNode
	 */
	public function writeNodeCollection(array $nodes);

	/**
	 * @param PhpNode $node
	 * @return NeoNode
	 */
	public function writeNode(PhpNode $node);
}