<?php
namespace Helmich\Graphizer\Modeler;

use Helmich\Graphizer\Persistence\BulkOperation;
use Helmich\Graphizer\Persistence\Neo4j\Backend;
use Helmich\Graphizer\Persistence\Op\MatchNodeByNode;

class NamespaceResolver {

	/**
	 * @var \Helmich\Graphizer\Persistence\Neo4j\Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function run() {
		$transaction = $this->backend->getClient()->beginTransaction();

		$this->treatNamespacedNodes($transaction);
		$this->treatUnnamespacedNodes($transaction);
	}

	private function treatUnnamespacedNodes() {
		$this->backend->execute(
			'MATCH (c:Collection)-[:HAS]->(ns)-[:SUB|HAS*]->(n:Name)
			 WHERE c.fileRoot = true AND NOT ns:Stmt_Namespace
			 SET n.fullName = n.allParts'
		);
	}

	private function treatNamespacedNodes() {
		$cypher     =
			'MATCH          (ns:Stmt_Namespace)
			 OPTIONAL MATCH (ns)-[:SUB {type: "stmts"}]->(s)-->(:Stmt_Use)-->()-->(u:Stmt_UseUse)
			 RETURN ns, collect(u) AS imports';
		$namespaces = $this->backend->createQuery($cypher)->execute();

		$this->backend->execute('MATCH (name:Name_FullyQualified) SET name.fullName = name.allParts');
		$this->backend->execute('MATCH (ns:Stmt_Namespace)-[:SUB {type: "name"}]->(name) SET name.fullName = name.allParts');

		$nameCypher =
			'MATCH (ns)-[:SUB {type: "stmts"}]->()-[:SUB|HAS*]->(name:Name) WHERE id(ns)={node} AND name.fullName IS NULL RETURN name';
		$nameQuery  = $this->backend->createQuery($nameCypher, 'name');

		$bulk = $this->backend->createBulkOperation();

		foreach ($namespaces as $row) {
			$namespace    = $row->node('ns');
			$knownAliases = [];

			foreach ($row['imports'] as $import) {
				$knownAliases[$import->getProperty('alias')] = $import->getProperty('name');
			}

			foreach ($nameQuery->execute(['node' => $namespace]) as $name) {
				$nameString = $name->getProperty('allParts');
				$matcher = new MatchNodeByNode($name);
				if (array_key_exists($nameString, $knownAliases)) {
					$bulk->push($matcher->update()->fullName($knownAliases[$nameString]));
//					$bulk->push(new UpdateNode(new MatchNodeByNode($name), ['fullName' => $knownAliases[$nameString]]));
				} elseif ($namespace->getProperty('name')) {
					$bulk->push($matcher->update()->fullName($namespace->getProperty('name'). '\\' . $nameString));
//					$bulk->push(new UpdateNode(new MatchNodeByNode($name), ['fullName' => $namespace->getProperty('name'). '\\' . $nameString]));
				}
			}
		}

		$bulk->evaluate();
	}
}