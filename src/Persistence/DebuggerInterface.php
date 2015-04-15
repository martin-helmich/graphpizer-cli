<?php
namespace Helmich\Graphizer\Persistence;

interface DebuggerInterface {

	public function nodeCreated($id, array $labels);
	public function queryExecuted($cypher, array $args);
}