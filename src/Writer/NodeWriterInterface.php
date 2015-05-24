<?php
namespace Helmich\Graphizer\Writer;

use Everyman\Neo4j\Node as NeoNode;
use Helmich\Graphizer\Persistence\BulkOperation;
use Helmich\Graphizer\Persistence\Op\CreateNode;
use Helmich\Graphizer\Persistence\Op\NodeMatcher;
use PhpParser\Node as PhpNode;

interface NodeWriterInterface {

	/**
	 * @param PhpNode[]     $nodes
	 * @param BulkOperation $bulk
	 * @return CreateNode
	 */
	public function writeNodeCollection(array $nodes, BulkOperation $bulk);

	/**
	 * @param PhpNode       $node
	 * @param BulkOperation $bulk
	 * @return CreateNode
	 */
	public function writeNode(PhpNode $node, BulkOperation $bulk);
}