<?php
namespace Helmich\Graphizer\Modeler;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Persistence\TypedResultRowAdapter;
use phpDocumentor\Reflection\DocBlock;
use PhpParser\Node\Stmt\Class_;

class ClassModelGenerator {

	/**
	 * @var Backend
	 */
	private $backend;

	/**
	 * @var NamespaceResolver
	 */
	private $namespaceResolver;

	public function __construct(Backend $backend, NamespaceResolver $namespaceResolver) {
		$this->backend           = $backend;
		$this->namespaceResolver = $namespaceResolver;
	}

	public function run() {
		$this->namespaceResolver->run();

		$query = $this->backend->createQuery(
			'MATCH (cls:Stmt_Class)       OPTIONAL MATCH (ns:Stmt_Namespace)-[*..]->(cls)   RETURN cls   AS cls, ns, "class"     AS type UNION
			 MATCH (iface:Stmt_Interface) OPTIONAL MATCH (ns:Stmt_Namespace)-[*..]->(iface) RETURN iface AS cls, ns, "interface" AS type UNION
			 MATCH (trt:Stmt_Trait)       OPTIONAL MATCH (ns:Stmt_Namespace)-[*..]->(trt)   RETURN trt   AS cls, ns, "trait"     AS type'
		);

		foreach ($query->execute() as $row) {
			$this->processClassLike($row);
		}

		$this->consolidateTypesWithClasses();
		$this->findClassExtensions();
		$this->findInterfaceImplementations();
		$this->findTraitUsages();
		$this->findMethodImplementations();
	}

	private function consolidateTypesWithClasses() {
		$this->backend->execute(
			'MATCH (t:Type), (c) WHERE t.name = c.fqcn AND (c:Class OR c:Interface) MERGE (t)-[:IS]->(c)'
		);
	}

	private function findTraitUsages() {
		$this->backend->execute(
			'MATCH (c:Class)-[:DEFINED_IN]->(:Stmt_Class)-[:SUB_STMTS]->()-[:HAS]->(:Stmt_TraitUse)-[:SUB_TRAITS]->()-[:HAS]->(tname)
			 MATCH (t:Trait) WHERE t.fqcn = tname.fullName
			 MERGE (c)-[:USES_TRAIT]->(t)'
		);
	}

	private function findInterfaceImplementations() {
		$this->backend->execute(
			'MATCH (c:Class)-[:DEFINED_IN]->(:Stmt_Class)-[:SUB_IMPLEMENTS]->()-[:HAS]->(iname)
			 MATCH (i:Interface) WHERE i.fqcn=iname.fullName
			 MERGE (c)-[:IMPLEMENTS]->(i)'
		);
	}

	private function findMethodImplementations() {
		$this->backend->execute(
			'MATCH (m:Method)<-[:HAS_METHOD]-()-[:IMPLEMENTS]->()-[:HAS_METHOD]->(s) WHERE m.name=s.name
			 MERGE (m)-[:IMPLEMENTS_METHOD]->(s)'
		);
		$this->backend->execute(
			'MATCH (m:Method)<-[:HAS_METHOD]-()-[:EXTENDS]->()-[:HAS_METHOD]->(s) WHERE m.name=s.name AND m.abstract=true
			 MERGE (m)-[:IMPLEMENTS_METHOD]->(s)'
		);
		$this->backend->execute(
			'MATCH (m:Method)<-[:HAS_METHOD]-()-[:EXTENDS]->()-[:HAS_METHOD]->(s) WHERE m.name=s.name AND m.abstract=false
			 MERGE (m)-[:OVERRIDES_METHOD]->(s)'
		);
	}

	private function findClassExtensions() {
		$this->backend->execute(
			'MATCH (sub:Class)-[:DEFINED_IN]->(:Stmt_Class)-[:SUB_EXTENDS]->(ename)
			 MATCH (super:Class) WHERE super.fqcn=ename.fullName
			 MERGE (sub)-[:EXTENDS]->(super)
		'
		);
	}

	private function extractPropertiesForClass(Node $classNode, Node $classStmtNode) {
		$cypher =
			'START cls=node({def})
			 MATCH (cls)-[*..]->(outer:Stmt_Property)-->()-->(inner:Stmt_PropertyProperty)
			 OPTIONAL MATCH (inner)-[:SUB_DEFAULT]->(default)
			 OPTIONAL MATCH (class)-[:HAS_PROPERTY]->(existing) WHERE id(class)={cls} AND existing.name=inner.name
			 RETURN outer, inner, default, existing';
		$query  = $this->backend->createQuery($cypher);

		$cypher = 'MATCH (d) WHERE id(d)={definition}
		           MATCH (c) WHERE id(c)={class}
		           CREATE (p:Property {prop})
		           MERGE (p)-[:DEFINED_IN]->(d)
		           MERGE (c)-[:HAS_PROPERTY]->(p)
		           RETURN p';
		$create = $this->backend->createQuery($cypher);

		foreach ($query->execute(['def' => $classStmtNode, 'cls' => $classNode]) as $row) {
			$outerProperty = $row->node('outer');
			$innerProperty = $row->node('inner');

			$properties = [
				'public'     => ($outerProperty->getProperty('type') & Class_::MODIFIER_PUBLIC) > 0,
				'private'    => ($outerProperty->getProperty('type') & Class_::MODIFIER_PRIVATE) > 0,
				'protected'  => ($outerProperty->getProperty('type') & Class_::MODIFIER_PROTECTED) > 0,
				'static'     => ($outerProperty->getProperty('type') & Class_::MODIFIER_STATIC) > 0,
				'name'       => $innerProperty->getProperty('name'),
			];

			if ($outerProperty->getProperty('docComment')) {
				$properties['docComment'] = $outerProperty->getProperty('docComment');
			}

			if (!$row['existing']) {
				$result = $create->execute(['prop' => $properties, 'definition' => $classStmtNode, 'class' => $classNode]);
				$property = $result[0]->node('p');
			} else {
				$property = $row->node('existing');
				$property->setProperties($properties);
				$property->save();
			}

			if ($row['default']) {
				$defaultValueNode = $row->node('default');

				/** @var Label[] $labels */
				$labels = $defaultValueNode->getLabels();
				foreach ($labels as $valueLabel) {
					switch ($valueLabel->getName()) {
						case 'Scalar_String':
							$this->setNodeDataType($property, 'string');
							break;
						case 'Scalar_LNumber':
							$this->setNodeDataType($property, 'int');
							break;
						case 'Scalar_DNumber':
							$this->setNodeDataType($property, 'double');
							break;
						case 'Expr_Array':
							$this->setNodeDataType($property, 'array');
							break;
					}
				}
			}

			$this->enrichNodeFromDocBlock($property, $outerProperty->getProperty('docComment'));
		}
	}

	private function extractMethodsForClass(Node $classNode, Node $classStmtNode) {
		$cypher = 'MATCH (cls)-[:SUB_STMTS]->()-->(m:Stmt_ClassMethod) WHERE id(cls)={cls} RETURN m';
		$query  = $this->backend->createQuery($cypher, 'm');

		$cypher = 'MATCH (d) WHERE id(d)={definition}
		           MATCH (c) WHERE id(c)={class}
		           MERGE (m:Method {
		               public: {method}.public,
		               private: {method}.private,
		               protected: {method}.protected,
		               static: {method}.static,
		               abstract: {method}.abstract,
		               name: {method}.name
		           })
		           MERGE (m)-[:DEFINED_IN]->(d)
		           MERGE (c)-[:HAS_METHOD]->(m)
		           RETURN m';
		$create = $this->backend->createQuery($cypher);

		foreach ($query->execute(['cls' => $classStmtNode->getId()]) as $methodStmt) {
			/** @var Node $methodStmt */
			$properties = [
				'public'    => ($methodStmt->getProperty('type') & Class_::MODIFIER_PUBLIC) > 0,
				'private'   => ($methodStmt->getProperty('type') & Class_::MODIFIER_PRIVATE) > 0,
				'protected' => ($methodStmt->getProperty('type') & Class_::MODIFIER_PROTECTED) > 0,
				'static'    => ($methodStmt->getProperty('type') & Class_::MODIFIER_STATIC) > 0,
				'abstract'  => ($methodStmt->getProperty('type') & Class_::MODIFIER_ABSTRACT) > 0,
				'name'      => $methodStmt->getProperty('name')
			];
//			$method     = $this->backend->createNode($properties, 'Method');
//			$method->relateTo($methodStmt, 'DEFINED_IN')->save();
//			$classNode->relateTo($method, 'HAS_METHOD')->save();

			$result = $create->execute(['method' => $properties, 'definition' => $classStmtNode, 'class' => $classNode]);
			$method = $result[0]->node('m');

			$this->enrichNodeFromDocBlock($method, $methodStmt->getProperty('docComment'));
		}
	}

	private function enrichNodeFromDocBlock(Node $node, $docblock) {
		$phpdoc = new DocBlock($docblock);
		$dirty = FALSE;

		if (empty($phpdoc->getShortDescription()) === FALSE) {
			$node->setProperty('shortDescription', $phpdoc->getShortDescription());
			$dirty = TRUE;
		}

		if (empty($phpdoc->getLongDescription()) === FALSE) {
			$node->setProperty('longDescription', $phpdoc->getLongDescription()->getContents());
			$dirty = TRUE;
		}

		if ($dirty) {
			$node->save();
		}

		if ($phpdoc->hasTag('var')) {
			foreach ($phpdoc->getTagsByName('var') as $tag) {
				$dt = explode(' ', $tag->getContent())[0];
				$this->setNodeDataType($node, $dt);
			}
		}

		if ($phpdoc->hasTag('return')) {
			foreach ($phpdoc->getTagsByName('return') as $tag) {
				$dt = explode(' ', $tag->getContent())[0];
				$this->setNodeDataType($node, $dt);
			}
		}
	}

	private function setNodeDataType(Node $node, $dataTypes) {
		$imports   = $this->getImportsForContext($node);
		$namespace = $this->getNamespaceForContext($node);

		foreach (explode('|', $dataTypes) as $dataType) {
			$dataType = trim($dataType);
			if (empty($dataType) === FALSE) {
				$dataTypeNode =
					$this->getTypeNode($dataType, $imports, $namespace ? $namespace->getProperty('name') : NULL);
				if ($dataTypeNode !== NULL) {
					$node
						->relateTo($dataTypeNode, 'POSSIBLE_TYPE')
						->save();
				}
			}
		}
	}

	/**
	 * @param Node $node
	 * @return Node
	 */
	private function getNamespaceForContext(Node $node) {
		$cypher = 'MATCH (n)-[:DEFINED_IN]->()<-[*..]-(ns:Stmt_Namespace) WHERE id(n)={node} RETURN ns';
		$query  = $this->backend->createQuery($cypher);
		$result = $query->execute(['node' => $node]);

		if ($result->count() > 0) {
			return $result[0]['ns'];
		} else {
			return NULL;
		}
	}

	private function getImportsForContext(Node $node) {
		$cypher  =
			'START n=node({node})
			 MATCH (n)-[:DEFINED_IN]->()<-[*..]-(ns:Stmt_Namespace)
			 MATCH (ns)-[:SUB_STMTS]->()-->(:Stmt_Use)-->()-->(use:Stmt_UseUse)
			 RETURN use';
		$query   = $this->backend->createQuery($cypher, 'use');
		$imports = [];

		foreach ($query->execute(['node' => $node->getId()]) as $useStmt) {
			$imports[$useStmt->getProperty('alias')] = $useStmt->getProperty('name');
		}

		return $imports;
	}

	private function getTypeNode($type, $importScope, $currentNamespace) {
		$buildOrGetPrimitiveNode = function ($type, $primitive = TRUE) {
			$cypher = 'MERGE (n:Type{name: {name}, primitive: {primitive}}) RETURN n';
			$query  = $this->backend->createQuery($cypher, 'n');

			return $query->execute(['name' => $type, 'primitive' => $primitive])[0];
		};

		switch (strtolower($type)) {
			case 'int':
			case 'integer':
				return $buildOrGetPrimitiveNode('integer');
			case 'str':
			case 'string':
				return $buildOrGetPrimitiveNode('string');
			case 'bool':
			case 'boolean':
				return $buildOrGetPrimitiveNode('boolean');
			case 'float':
			case 'double':
			case 'decimal':
				return $buildOrGetPrimitiveNode('double');
			case 'void':
			case 'null':
			case 'nil':
				return $buildOrGetPrimitiveNode('null');
			case 'array':
				return $buildOrGetPrimitiveNode('array');
			case 'callable':
				return $buildOrGetPrimitiveNode('callable');
			case 'mixed':
				return NULL;
			default:
				if ($type{0} == '\\') {
					$name = ltrim($type, '\\');
				} else {
					if (array_key_exists($type, $importScope)) {
						$name = $importScope[$type];
					} elseif ($currentNamespace !== NULL) {
						$name = $currentNamespace . '\\' . $type;
					} else {
						$name = $type;
					}
				}

				/** @var Node $node */
				return $buildOrGetPrimitiveNode($name, FALSE);
		}
	}

	/**
	 * @param $row
	 */
	private function processClassLike(TypedResultRowAdapter $row) {
		$cls = $row->node('cls');
		$ns  = $row->node('ns');

		$arguments = [
			'definition' => $cls->getId(),
			'name'       => $cls->getProperty('name'),
			'namespace'  => NULL,
			'fqcn'       => $cls->getProperty('name'),
			'abstract'   => ($cls->getProperty('type') & Class_::MODIFIER_ABSTRACT) > 0,
			'final'      => ($cls->getProperty('type') & Class_::MODIFIER_FINAL) > 0
		];

		if ($ns) {
			$arguments['namespace'] = $ns->getProperty('name');
			$arguments['fqcn']      = $ns->getProperty('name') . '\\' . $cls->getProperty('name');
		}

		$label = ucfirst($row['type']);
		$q     = $this->backend->createQuery(
			"MATCH (d) WHERE id(d)={definition}
			 MERGE (c:{$label} {name: {name}, " . ($ns ? "namespace: {namespace}, " : "") . "fqcn: {fqcn}, abstract: {abstract}, final: {final}})
			 MERGE (c)-[:DEFINED_IN]->(d)
			 RETURN c"
		);

//		$class = $this->backend->createNode($properties, ucfirst($row['type']));
//		$class
//			->relateTo($cls, 'DEFINED_IN')
//			->save();

		$result = $q->execute($arguments);
		$class  = $result[0]->node('c');

		if ($row['type'] !== 'interface') {
			$this->extractPropertiesForClass($class, $cls);
		}

		$this->extractMethodsForClass($class, $cls);
	}
}