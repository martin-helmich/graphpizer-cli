<?php
namespace Helmich\Graphizer\Modeler;

use Helmich\Graphizer\Persistence\Backend;

class NamespaceResolver {

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function run() {
		$cypher     =
			'MATCH          (ns:Stmt_Namespace)
			 OPTIONAL MATCH (ns)-[:SUB_STMTS]->(s)-->(:Stmt_Use)-->()-->(u:Stmt_UseUse)
			 RETURN ns, collect(u) AS imports';
		$namespaces = $this->backend->createQuery($cypher)->execute();

		$this->backend->execute('MATCH (name:Name_FullyQualified) SET name.fullName = name.allParts');
		$this->backend->execute('MATCH (ns:Stmt_Namespace)-[:SUB_NAME]->(name) SET name.fullName = name.allParts');

		$nameCypher = 'MATCH (ns)-[:SUB_STMTS]->()-[*..]->(name:Name) WHERE id(ns)={node} AND name.fullName IS NULL RETURN name';
		$nameQuery  = $this->backend->createQuery($nameCypher, 'name');

		foreach ($namespaces as $row) {
			$namespace    = $row->node('ns');
			$knownAliases = [];

			foreach ($row['imports'] as $import) {
				$knownAliases[$import->getProperty('alias')] = $import->getProperty('name');
			}

			foreach ($nameQuery->execute(['node' => $namespace]) as $name) {
				$nameString = $name->getProperty('allParts');
				if (array_key_exists($nameString, $knownAliases)) {
					$name->setProperty('fullName', $knownAliases[$nameString]);
					$name->save();
				} else {
					if ($namespace->getProperty('name')) {
						$name->setProperty('fullName', $namespace->getProperty('name') . '\\' . $nameString);
						$name->save();
					}
				}
			}
		}

		$this->treatUnnamespacedNodes();
	}

	private function treatUnnamespacedNodes() {
		$cypher =
			'MATCH (c:Collection)-[:HAS]->(ns)-[*..]->(n:Name)
			 WHERE c.fileRoot = true AND NOT ns:Stmt_Namespace
			 SET n.fullName = n.allParts';
		$this->backend->execute($cypher);
	}
}