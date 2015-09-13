<?php
namespace Helmich\Graphizer\Persistence;

use Helmich\Graphizer\Configuration\ProjectConfiguration;

class NullDebugger implements DebuggerInterface {

	public function queryExecuted($cypher, array $args) {
	}

	public function nodeCreated($id, array $labels) {
	}

	public function queryExecuting($cypher, array $args) {
	}

	public function projectUpserted(ProjectConfiguration $project) {
	}
}