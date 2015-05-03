<?php
namespace Helmich\Graphizer\Persistence;

interface DebuggerInterface {

	public function nodeCreated($id, array $labels);
	public function queryExecuting($cypher, array $args);
	public function queryExecuted($cypher, array $args);
}