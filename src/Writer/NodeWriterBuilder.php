<?php
namespace Helmich\Graphizer\Writer;

class NodeWriterBuilder {

	public function build() {
		$commentWriter = new CommentWriter();
		return new NodeWriter($commentWriter);
	}

}