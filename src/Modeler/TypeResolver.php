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

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Relationship;
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
			'MATCH (c:Expr_New)-[:SUB{type: "class"}]->(n)
			     WHERE (n:Name OR n:Name_FullyQualified) AND n.fullName IS NOT NULL
			 MERGE (t:Type {name: n.fullName, primitive: false})
			 MERGE (c)-[:POSSIBLE_TYPE {confidence: 1}]->(t)'
		);

		$this->backend->execute(
			'MATCH (var:Expr_Variable{name: "this"})<-[:SUB|HAS*]-(:Stmt_Class)<-[:DEFINED_IN]-(class:Class)<-[:IS]-(type:Type)
			 MERGE (var)-[:POSSIBLE_TYPE {confidence: 1}]->(type)'
		);

		$varQuery = $this->backend->createQuery(
			'MATCH (c)-[:SUB|HAS*]->(var:Expr_Variable)
			 WHERE id(c)={node}
			 RETURN var'
		);

		$classMethodsQuery = $this->backend->createQuery(
			'MATCH (method:Stmt_ClassMethod)<-[:SUB|HAS*]-(classStmt:Stmt_Class),
			       (classStmt)<-[:DEFINED_IN]-(class)<-[:IS]-(type),
			       (method)<-[:DEFINED_IN]-()-[:HAS_PARAMETER]->(param)
			 RETURN method, classStmt, class, type, collect(param) AS parameters'
		);

		$query = function ($cypher) {
			$q = $this->backend->createQuery($cypher, NULL, TRUE);
			$r = $q->execute();

			$stats = $r->getStatistics();
			return
				$stats->getRelationshipsCreated() +
				$stats->getNodesCreated();
		};

		$scopes = [];

		$loopCounter = 0;
		do {
			$affected = 0;
			$loopCounter++;

			if ($loopCounter >= 100) {
				throw new \Exception('Something\'s wrong!');
			}

			$affected += $query(
				'MATCH (c:Expr_Assign)-[:SUB{type: "var"}]->(var),
				       (c)-[:SUB{type: "expr"}]->(expr)-[:POSSIBLE_TYPE]->(type)
				 MERGE (var)-[:POSSIBLE_TYPE]->(type)'
			);

			$affected += $query(
				'MATCH (call:Expr_MethodCall)-[:SUB{type:"var"}]->(var)-[:POSSIBLE_TYPE]->(calleeType{primitive:false})
				 MATCH (calleeType)-[:IS]->(:Class)-[:HAS_METHOD|EXTENDS*]->(callee:Method {name: call.name})-[:POSSIBLE_TYPE]->(calleeReturnType)
				 MERGE (call)-[:POSSIBLE_TYPE]->(calleeReturnType)'
			);

			$affected += $query(
				'MATCH (propFetch:Expr_PropertyFetch)-[:SUB{type: "var"}]->(var)-[:POSSIBLE_TYPE]->(parentType),
				       (parentType)-[:IS]->(:Class)-[:HAS_PROPERTY|EXTENDS*]->(property:Property {name: propFetch.name})-[:POSSIBLE_TYPE]->(propertyType)
				 MERGE (propFetch)-[:POSSIBLE_TYPE]->(propertyType)'
			);

			$affected += $query(
				'MATCH (return:Stmt_Return)-[:SUB{type:"expr"}]->(expr)-[:POSSIBLE_TYPE]->(type:Type)
				 MATCH (return)<-[:SUB|HAS*]-(methodStmt)<-[:DEFINED_IN]-(method:Method)
				 MERGE (method)-[:POSSIBLE_TYPE]->(type)'
			);

			echo "$affected nodes/relationships affected!\n";

			foreach ($classMethodsQuery->execute() as $row) {
				$method          = $row->node('method');
				$class = $row->node('class');
				$classType       = $row->node('type');
				$parameters      = $row['parameters'];

				$identifier = $class->getProperty('fqcn') . '::' . $method->getProperty('name');

				if (!array_key_exists($identifier, $scopes)) {
					$scopes[$identifier] = [];
				}

				$symbols =& $scopes[$identifier];

				foreach ($varQuery->execute(['node' => $method]) as $varRow) {
					$var  = $varRow->node('var');
					$name = $var->getProperty('name');

					$symbols[$name] = [];
				}

				if (array_key_exists('this', $symbols)) {
					$symbols['this'] = [$classType->getProperty('name') => $classType];
				}

				/** @var Node $parameter */
				foreach ($parameters as $parameter) {
					$paramName = $parameter->getProperty('name');
					if (array_key_exists($paramName, $symbols)) {
						/** @var Relationship $rel */
						foreach ($parameter->getRelationships('POSSIBLE_TYPE', Relationship::DirectionOut) as $rel) {
							$symbols[$paramName][$rel->getEndNode()->getProperty('name')] = $rel->getEndNode();
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
					foreach ($assignmentRow['types'] as $type) {
						$symbols[$name][$type->getProperty('name')] = $type;
					}
				}
			}
		}
		while ($affected > 0);

		echo "Type propagation finished after $loopCounter passes!\n";

		foreach ($scopes as $method => $symbols) {
			echo "Treating method {$method}\n";
			foreach ($symbols as $name => $possibleTypes) {
				echo "  Found variable {$name}. Possible types: " . $this->typeListToString($possibleTypes) . "\n";
			}
		}
	}

	private function typeListToString(array $types) {
		$names = [];
		foreach ($types as $type) {
			$names[] = $type->getProperty('name');
		}
		return implode(', ', $names);
	}
}