<?php
namespace Helmich\Graphizer\Reader;

use Everyman\Neo4j\Node as NeoNode;
use PhpParser\Node as PhpNode;

interface NodeReaderInterface {

	/**
	 * @param NeoNode $node
	 * @return PhpNode|PhpNode[]
	 */
	public function readNode(NeoNode $node);
}