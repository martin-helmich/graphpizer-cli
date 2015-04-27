<?php
namespace Helmich\Graphizer\Modeler;

use Everyman\Neo4j\Cypher\Query;
use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Writer\Bulk;

class NamespaceResolver {

	/**
	 * @var Backend
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
			'MATCH (c:Collection)-[:HAS]->(ns)-[*..]->(n:Name)
			 WHERE c.fileRoot = true AND NOT ns:Stmt_Namespace
			 SET n.fullName = n.allParts'
		);
	}

	private function treatNamespacedNodes() {
		$client     = $this->backend->getClient();
		$cypher     =
			'MATCH          (ns:Stmt_Namespace)
			 OPTIONAL MATCH (ns)-[:SUB_STMTS]->(s)-->(:Stmt_Use)-->()-->(u:Stmt_UseUse)
			 RETURN ns, collect(u) AS imports';
		$namespaces = $this->backend->createQuery($cypher)->execute();

		$this->backend->execute('MATCH (name:Name_FullyQualified) SET name.fullName = name.allParts');
		$this->backend->execute('MATCH (ns:Stmt_Namespace)-[:SUB_NAME]->(name) SET name.fullName = name.allParts');

		$nameCypher =
			'MATCH (ns)-[:SUB_STMTS]->()-[*..]->(name:Name) WHERE id(ns)={node} AND name.fullName IS NULL RETURN name';
		$nameQuery  = $this->backend->createQuery($nameCypher, 'name');

//		$tr = $client->beginTransaction();

//		$bulk = new Bulk($this->backend);
		$readBulk = new Bulk($this->backend);
		$writeBulk = new Bulk($this->backend);

		foreach ($namespaces as $row) {
			$namespace    = $row->node('ns');
			$knownAliases = [];

			foreach ($row['imports'] as $import) {
				$knownAliases[$import->getProperty('alias')] = $import->getProperty('name');
			}

			foreach ($nameQuery->execute(['node' => $namespace]) as $name) {
				$nameString = $name->getProperty('allParts');
				$id = uniqid('node');
				if (array_key_exists($nameString, $knownAliases)) {
					$readBulk->push("MATCH ({$id}) WHERE id({$id})={node{$id}}", ["node{$id}" => $name->getId()]);
					$writeBulk->push("SET {$id}.fullName={fullname}", ['fullname' => $knownAliases[$nameString]]);
//					$bulk->push(
//						"MATCH ({$id}) WHERE id({$id})={node} SET {$id}.fullName={fullname}",
//						['fullname' => $knownAliases[$nameString], 'node' => $name->getId()]
//					);
//					$tr->addStatements(
//						new Query(
//							$client,
//							'MATCH (n) WHERE id(n)={node} SET n.fullName={fullname}',
//							['fullname' => $knownAliases[$nameString], 'node' => $name->getId()]
//						)
//					);
					$name->setProperty('fullName', $knownAliases[$nameString]);
					$name->save();
				} else {
					if ($namespace->getProperty('name')) {
						$readBulk->push("MATCH ({$id}) WHERE id({$id})={node{$id}}", ["node{$id}" => $name->getId()]);
						$writeBulk->push("SET {$id}.fullName={fullname}", ['fullname' => $namespace->getProperty('name'). '\\' . $nameString]);
//						$bulk->push(
//							"MATCH ({$id}) WHERE id({$id})={node} SET {$id}.fullName={fullname}",
//							['fullname' => $namespace->getProperty('name'). '\\' . $nameString, 'node' => $name->getId()]
//						);
//						$tr->addStatements(
//							new Query(
//								$client,
//								'MATCH (n) WHERE id(n)={node} SET n.fullName={fullname}',
//								['fullname' => $namespace->getProperty('name'). '\\' . $nameString, 'node' => $name->getId()]
//							)
//						);
//						$name->setProperty('fullName', $namespace->getProperty('name') . '\\' . $nameString);
//						$name->save();
					}
				}
			}
		}

		$readBulk->merge($writeBulk)->evaluate();

//		$bulk->evaluate();
//		$tr->commit();
	}
}