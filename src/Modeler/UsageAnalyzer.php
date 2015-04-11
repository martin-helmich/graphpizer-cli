<?php
namespace Helmich\Graphizer\Modeler;

use Helmich\Graphizer\Persistence\Backend;

class UsageAnalyzer {

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend){
		$this->backend = $backend;
	}

	public function run() {
		// Register usages from constructor calls.
		$this->backend->execute(
			'MATCH (new:Expr_New)<-[*..]-(:Stmt_Class)<-[:DEFINED_IN]-(c:Class)
			 MERGE (type:Type{name:new.class, primitive: false})<-[:INSTANTIATES]-(new)
			 MERGE (c)-[r:USES]->(type) ON MATCH SET r.count = r.count + 1 ON CREATE SET r.count = 1'
		);

		// Register usages from property types
		$this->backend->execute(
			'MATCH (p:Property)-[:POSSIBLE_TYPE]->(t) WHERE t.primitive=false
			 MATCH (p)<-[:HAS_PROPERTY]-(c:Class)
			 MERGE (c)-[r:USES]->(t) ON MATCH SET r.count = r.count + 1 ON CREATE SET r.count = 1'
		);

		// Register usages from method return types
		$this->backend->execute(
			'MATCH (m:Method)-[:POSSIBLE_TYPE]->(t) WHERE t.primitive=false
			 MATCH (m)<-[:HAS_METHOD]-(c:Class)
			 MERGE (c)-[r:USES]->(t) ON MATCH SET r.count = r.count+1 ON CREATE SET r.count=1'
		);
	}
}