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
 * @package    Helmich\Graphizer
 * @subpackage Modeler\TypeInference
 */
class TypeInferencePass {

	/** @var Backend */
	private $backend;

	/** @var int */
	private $affected = -1;

	/** @var int */
	private $iterationCount = 0;

	/** @var int */
	private $maxIterationCount = PHP_INT_MAX;

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
			       (classStmt)<-[:DEFINED_IN]-(class)<-[:IS]-(type)
			 OPTIONAL MATCH (method)<-[:DEFINED_IN]-()-[:HAS_PARAMETER]->(param)
			 RETURN method, classStmt, class, type, collect(param) AS parameters'
		);
	}

	/**
	 * Sets the maximum allowed iteration count (default is INT_MAX).
	 *
	 * Usually, the process should terminate automatically once no new nodes or
	 * edges are added to the graph (it is assumed that this will happen at some
	 * point). This is just a safeguard against unproven assumptions.
	 *
	 * @param int $iterationCount The maximum iteration count.
	 */
	public function setMaxIterationCount($iterationCount) {
		$this->maxIterationCount = $iterationCount;
	}

	/**
	 * Executes the next iteration.
	 *
	 * @throws TypeInferenceLoopException
	 */
	public function pass() {
		$this->reset();
		$this->abortIfMaxIterationCountExceeded();

		$this->propagateExprTypeByAssignment();
		$this->propagateExprTypeByMethodCallResult();
		$this->propagateExprTypeByPropertyFetch();
		$this->propagateMethodTypeByReturnStatement();
		$this->propagatePropertyTypeByAssignment();

		foreach ($this->classMethodsQuery->execute() as $row) {
			$method     = $row->node('method');
			$class      = $row->node('class');
			$classType  = $row->node('type');
			$parameters = $row['parameters'];

			$this->populateSymbolTableForMethod($class, $method, $classType, $parameters);
		}

		$this->iterationCount++;
	}

	/**
	 * Determines if the process is complete.
	 *
	 * The process is defined to have completed when no nodes or edges were
	 * created in the last iteration.
	 *
	 * @return bool TRUE when the process is completed.
	 */
	public function isDone() {
		if ($this->affected === -1) {
			throw new \BadMethodCallException('This type inference pass did not run yet!');
		}

		return $this->affected === 0;
	}

	/**
	 * Returns the number of nodes and edges that were affected in the current
	 * iteration.
	 *
	 * @return int The number of nodes and edges that were affected in the
	 *             current iteration.
	 */
	public function affectedInLastPass() {
		return $this->affected;
	}

	/**
	 * Returns the current iteration count.
	 *
	 * @return int The current iteration count
	 */
	public function getIterationCount() {
		return $this->iterationCount;
	}

	/**
	 * Executes a Cypher query and updates the change counter for this iteration.
	 *
	 * @param string $cypher The Cypher query
	 * @return void
	 */
	private function query($cypher) {
		$q = $this->backend->createQuery($cypher, NULL, TRUE);
		$r = $q->execute();

		$stats = $r->getStatistics();

		$this->affected += $stats->getRelationshipsCreated();
		$this->affected += $stats->getNodesCreated();
	}

	/**
	 * Find all assignments for which a type is known for the right-hand
	 * expression. Apply the known type(s) to the left-hand expression.
	 *
	 * @return void
	 */
	private function propagateExprTypeByAssignment() {
		$this->query(
			'MATCH (c:Expr_Assign)-[:SUB{type: "var"}]->(var),
			       (c)-[:SUB{type: "expr"}]->(expr)-[:POSSIBLE_TYPE]->(type)
			 MERGE (var)-[:POSSIBLE_TYPE]->(type)'
		);
	}

	/**
	 * Find all method calls for which the object type and the method's return
	 * type is known. Apply the known type(s) to the call expression.
	 *
	 * @return void
	 */
	private function propagateExprTypeByMethodCallResult() {
		// Caution: Using "-[:HAS_METHOD]->" for finding methods in classes is
		// not sufficient, since the method can also be defined in one of the
		// parent classes. This is solved by using "-[:HAS_METHOD|EXTENDS*]->"
		// instead!
		$this->query(
			'MATCH (call:Expr_MethodCall)-[:SUB{type:"var"}]->(var)-[:POSSIBLE_TYPE]->(calleeType{primitive:false})
			 MATCH (calleeType)-[:IS]->(:Class)-[:HAS_METHOD|EXTENDS*]->(callee:Method {name: call.name})-[:POSSIBLE_TYPE]->(calleeReturnType)
			 MERGE (call)-[:POSSIBLE_TYPE]->(calleeReturnType)'
		);
	}

	/**
	 * Find all property fetches for which the object and it's property type are
	 * known. Apply the known type(s) to the fetch expression.
	 *
	 * @return void
	 */
	private function propagateExprTypeByPropertyFetch() {
		// Mind ":HAS_METHOD" vs. ":HAS_METHOD|EXTENDS*" (see above)
		$this->query(
			'MATCH (propFetch:Expr_PropertyFetch)-[:SUB{type: "var"}]->(var)-[:POSSIBLE_TYPE]->(parentType),
			       (parentType)-[:IS]->(:Class)-[:HAS_PROPERTY|EXTENDS*]->(property:Property {name: propFetch.name})-[:POSSIBLE_TYPE]->(propertyType)
			 MERGE (propFetch)-[:POSSIBLE_TYPE]->(propertyType)'
		);
	}

	/**
	 * Find all return statements that return a statement of known type. Apply
	 * the known type(s) to the method meta-model. The return types might be
	 * propagated further in following iterations.
	 *
	 * @return void
	 */
	private function propagateMethodTypeByReturnStatement() {
		$this->query(
			'MATCH (return:Stmt_Return)-[:SUB{type:"expr"}]->(expr)-[:POSSIBLE_TYPE]->(type:Type)
			 MATCH (return)<-[:SUB|HAS*]-(methodStmt)<-[:DEFINED_IN]-(method:Method)
			 MERGE (method)-[:POSSIBLE_TYPE]->(type)'
		);
	}

	/**
	 * Resets this pass for the next iteration.
	 *
	 * @return void
	 */
	private function reset() {
		$this->affected = 0;
	}

	/**
	 * Aborts the iteration if the maximum iteration count was exceeded.
	 *
	 * @throws TypeInferenceLoopException
	 */
	private function abortIfMaxIterationCountExceeded() {
		if ($this->iterationCount >= $this->maxIterationCount) {
			throw new TypeInferenceLoopException($this->iterationCount);
		}
	}

	/**
	 * Populates the global symbol table with all variables from a given class
	 * method.
	 *
	 * @param Node   $class      The class node (labeled ":Class")
	 * @param Node   $method     The method node (labeled ":Stmt_ClassMethod")
	 * @param Node   $classType  The class type node (labeled ":Type")
	 * @param Node[] $parameters Parameter definitions (labeled ":Parameter")
	 */
	private function populateSymbolTableForMethod(Node $class, Node $method, Node $classType, \Traversable $parameters) {
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

	/**
	 * Propagates property types by assignments to known types.
	 *
	 * @return void
	 */
	private function propagatePropertyTypeByAssignment() {
		$this->query(
			'MATCH (ass:Expr_Assign)
			           -[:SUB{type:"var"}]->(propFetch:Expr_PropertyFetch)
			           -[:SUB{type:"var"}]->(propVar)
			           -[:POSSIBLE_TYPE]->(propType)
			     WHERE (propFetch.name IS NOT NULL)
			 MATCH (ass)
			           -[:SUB{type: "expr"}]->(assignedExpr)
			           -[:POSSIBLE_TYPE]->(assignedType)
			 MATCH (propType)
			           -[:IS]->(propClass)
			           -[:HAS_PROPERTY|EXTENDS*]->(assignedProperty:Property{name: propFetch.name})
			 MERGE (assignedProperty)-[:POSSIBLE_TYPE]->(assignedType)'
		);
	}
}