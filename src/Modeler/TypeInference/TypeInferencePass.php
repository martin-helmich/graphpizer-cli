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

namespace Helmich\Graphizer\Modeler\TypeInference;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Relationship;
use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Persistence\PreparedStatement;

/**
 * A single pass of the type inference process.
 *
 * @package Helmich\Graphizer
 * @subpackage Modeler\TypeInference
 */
class TypeInferencePass {

	/** @var Backend */
	private $backend;

	/** @var int */
	private $affected = -1;

	/** @var PreparedStatement */
	private $variableBelowNodeQuery;

	/** @var PreparedStatement */
	private $classMethodsQuery;

	/** @var SymbolTable */
	private $symbolTable;

	public function __construct(SymbolTable $symbolTable, Backend $backend) {
		$this->backend     = $backend;
		$this->symbolTable = $symbolTable;

		$this->variableBelowNodeQuery = $this->backend->createQuery(
			'MATCH (c)-[:SUB|HAS*]->(var:Expr_Variable)
			 WHERE id(c)={node}
			 RETURN var'
		);

		$this->classMethodsQuery = $this->backend->createQuery(
			'MATCH (method:Stmt_ClassMethod)<-[:SUB|HAS*]-(classStmt:Stmt_Class),
			       (classStmt)<-[:DEFINED_IN]-(class)<-[:IS]-(type),
			       (method)<-[:DEFINED_IN]-()-[:HAS_PARAMETER]->(param)
			 RETURN method, classStmt, class, type, collect(param) AS parameters'
		);
	}

	public function pass() {
		$this->affected = 0;

		$query = function ($cypher) {
			$q = $this->backend->createQuery($cypher, NULL, TRUE);
			$r = $q->execute();

			$stats = $r->getStatistics();
			return
				$stats->getRelationshipsCreated() +
				$stats->getNodesCreated();
		};

		$this->affected += $query(
			'MATCH (c:Expr_Assign)-[:SUB{type: "var"}]->(var),
				       (c)-[:SUB{type: "expr"}]->(expr)-[:POSSIBLE_TYPE]->(type)
				 MERGE (var)-[:POSSIBLE_TYPE]->(type)'
		);

		$this->affected += $query(
			'MATCH (call:Expr_MethodCall)-[:SUB{type:"var"}]->(var)-[:POSSIBLE_TYPE]->(calleeType{primitive:false})
				 MATCH (calleeType)-[:IS]->(:Class)-[:HAS_METHOD|EXTENDS*]->(callee:Method {name: call.name})-[:POSSIBLE_TYPE]->(calleeReturnType)
				 MERGE (call)-[:POSSIBLE_TYPE]->(calleeReturnType)'
		);

		$this->affected += $query(
			'MATCH (propFetch:Expr_PropertyFetch)-[:SUB{type: "var"}]->(var)-[:POSSIBLE_TYPE]->(parentType),
				       (parentType)-[:IS]->(:Class)-[:HAS_PROPERTY|EXTENDS*]->(property:Property {name: propFetch.name})-[:POSSIBLE_TYPE]->(propertyType)
				 MERGE (propFetch)-[:POSSIBLE_TYPE]->(propertyType)'
		);

		$this->affected += $query(
			'MATCH (return:Stmt_Return)-[:SUB{type:"expr"}]->(expr)-[:POSSIBLE_TYPE]->(type:Type)
				 MATCH (return)<-[:SUB|HAS*]-(methodStmt)<-[:DEFINED_IN]-(method:Method)
				 MERGE (method)-[:POSSIBLE_TYPE]->(type)'
		);

		foreach ($this->classMethodsQuery->execute() as $row) {
			$method          = $row->node('method');
			$class = $row->node('class');
			$classType       = $row->node('type');
			$parameters      = $row['parameters'];

			$symbolTable = $this->symbolTable
				->scope($class->getProperty('fqcn'))
				->scope($method->getProperty('name'));

			foreach ($this->variableBelowNodeQuery->execute(['node' => $method]) as $varRow) {
				$var  = $varRow->node('var');
				$name = $var->getProperty('name');

				$symbolTable->addSymbol($name);
			}

			$symbolTable->addTypeForSymbol('this', $classType);

			/** @var Node $parameter */
			foreach ($parameters as $parameter) {
				$paramName = $parameter->getProperty('name');
				if ($symbolTable->hasSymbol($paramName)) {
					/** @var Relationship $rel */
					foreach ($parameter->getRelationships('POSSIBLE_TYPE', Relationship::DirectionOut) as $rel) {
						$symbolTable->addTypeForSymbol($paramName, $rel->getEndNode());
					}
				}
			}

			$assignments = $this->backend->createQuery(
				'MATCH (m)-[:SUB|HAS*]->(assignment:Expr_Assign)-[:SUB{type: "var"}]->(var:Expr_Variable)-[:POSSIBLE_TYPE]->(type)
					 WHERE id(m)={node}
					 RETURN assignment, var, collect(type) AS types'
			);
			foreach ($assignments->execute(['node' => $method]) as $assignmentRow) {
				$name = $assignmentRow->node('var')->getProperty('name');
				/** @var Node $type */
				foreach ($assignmentRow['types'] as $type) {
					$symbolTable->addTypeForSymbol($name, $type);
				}
			}
		}
	}

	public function isDone() {
		if ($this->affected === -1) {
			throw new \BadMethodCallException('This type inference pass did not run yet!');
		}

		return $this->affected === 0;
	}

	public function affectedInLastPass() {
		return $this->affected;
	}
}