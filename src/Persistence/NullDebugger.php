<?php
namespace Helmich\Graphizer\Persistence;

class NullDebugger implements DebuggerInterface {

	public function queryExecuted($cypher, array $args) {
	}

	public function nodeCreated($id, array $labels) {
	}

	public function queryExecuting($cypher, array $args) {
	}
}