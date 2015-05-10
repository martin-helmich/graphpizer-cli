<?php

/*
 * GraPHPizer - Store PHP syntax trees in a Neo4j database
 * Copyright (C) 2015  Martin Helmich <kontakt@martin-helmich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Helmich\Graphizer\Modeler;

use Helmich\Graphizer\Modeler\TypeInference\SymbolTable;
use Helmich\Graphizer\Modeler\TypeInference\TypeInferencePass;
use Helmich\Graphizer\Persistence\Backend;

/**
 * Graph-based type inference engine.
 *
 * This class contains a type inference engine that tries to infers possible
 * types (curse you, PHP) for methods, properties and variables. This is done
 * iteratively in multiple passes.
 *
 * @package Helmich\Graphizer
 * @subpackage Modeler
 */
class TypeResolver {

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function run() {
		$this->backend->execute(
			'MATCH (c:Scalar_LNumber) MERGE (t:Type {name: "integer", primitive: true})
			 MERGE (c)-[:POSSIBLE_TYPE {confidence: 1}]->(t)'
		);
		$this->backend->execute(
			'MATCH (c:Scalar_DNumber) MERGE (t:Type {name: "double", primitive: true})
			 MERGE (c)-[:POSSIBLE_TYPE {confidence: 1}]->(t)'
		);
		$this->backend->execute(
			'MATCH (c) WHERE
			     c:Scalar_String OR
			     c:Scalar_Encapsed OR
			     c:Scalar_MagicConst_Dir OR
			     c:Scalar_MagicConst_Class OR
			     c:Scalar_MagicConst_Function OR
			     c:Scalar_MagicConst_Namespace OR
			     c:Scalar_MagicConst_Trait
			 MERGE (t:Type {name: "string", primitive: true})
			 MERGE (c)-[:POSSIBLE_TYPE {confidence: 1}]->(t)'
		);
		$this->backend->execute(
			'MATCH (a:Expr_Array) MERGE (t:Type {name: "array", primitive: true})
			 MERGE (a)-[:POSSIBLE_TYPE]->(t)'
		);
		$this->backend->execute(
			'MATCH (c:Expr_New)-[:SUB{type: "class"}]->(n)
			     WHERE (n:Name OR n:Name_FullyQualified) AND n.fullName IS NOT NULL
			 MERGE (t:Type {name: n.fullName, primitive: false})
			 MERGE (c)-[:POSSIBLE_TYPE {confidence: 1}]->(t)'
		);

		$this->backend->execute(
			'MATCH (var:Expr_Variable{name: "this"})<-[:SUB|HAS*]-(:Stmt_Class)<-[:DEFINED_IN]-(class:Class)<-[:IS]-(type:Type)
			 MERGE (var)-[:POSSIBLE_TYPE {confidence: 1}]->(type)'
		);

		$symbolTable = new SymbolTable();
		$pass = new TypeInferencePass($symbolTable, $this->backend);

		$loopCounter = 0;
		do {
			$pass->pass();
			$loopCounter ++;
			echo "Affected {$pass->affectedInLastPass()} in last pass.\n";
		}
		while (!$pass->isDone());

		echo "Type propagation finished after $loopCounter passes!\n";

		$symbolTable->dump();
	}

}