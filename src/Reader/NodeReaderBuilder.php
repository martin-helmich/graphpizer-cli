<?php
namespace Helmich\Graphizer\Reader;

use Helmich\Graphizer\Persistence\Neo4j\Backend;

class NodeReaderBuilder {

	/**
	 * @var \Helmich\Graphizer\Persistence\Neo4j\Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function build() {
		$commentWriter = new CommentReader($this->backend);

		return new NodeReader($this->backend, $commentWriter);
	}
}