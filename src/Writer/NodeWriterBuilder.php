<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Persistence\Backend;

class NodeWriterBuilder {

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function build() {
		$commentWriter = new CommentWriter($this->backend);
		return new NodeWriter($this->backend, $commentWriter);
	}
}