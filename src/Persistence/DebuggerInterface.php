<?php
namespace Helmich\Graphizer\Persistence;

use Helmich\Graphizer\Configuration\ProjectConfiguration;

interface DebuggerInterface {

	public function projectUpserted(ProjectConfiguration $project);
	public function nodeCreated($id, array $labels);
	public function queryExecuting($cypher, array $args);
	public function queryExecuted($cypher, array $args);
}