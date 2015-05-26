<?php
namespace Helmich\Graphizer\Modeler;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Helmich\Graphizer\Persistence\Neo4j\Backend;
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
		$this->backend->execute('CREATE INDEX ON :Type(name)');
		$this->backend->execute('CREATE INDEX ON :Class(fqcn)');
		$this->backend->execute('CREATE INDEX ON :Interface(fqcn)');
		$this->backend->execute('CREATE INDEX ON :Trait(fqcn)');

		$this->namespaceResolver->run();

		$query = $this->backend->createQuery(
			'MATCH (cls:Stmt_Class)       OPTIONAL MATCH (ns:Stmt_Namespace)-[:SUB|HAS*]->(cls)   RETURN cls   AS cls, ns, "class"     AS type UNION
			 MATCH (iface:Stmt_Interface) OPTIONAL MATCH (ns:Stmt_Namespace)-[:SUB|HAS*]->(iface) RETURN iface AS cls, ns, "interface" AS type UNION
			 MATCH (trt:Stmt_Trait)       OPTIONAL MATCH (ns:Stmt_Namespace)-[:SUB|HAS*]->(trt)   RETURN trt   AS cls, ns, "trait"     AS type'
		);

		foreach ($query->execute() as $row) {
			$this->processClassLike($row);
		}

		$this->findClassExtensions();
		$this->findInterfaceImplementations();
		$this->findTraitUsages();
		$this->findMethodImplementations();
	}

	private function findTraitUsages() {
		$this->backend->execute(
			'MATCH (c:Class)-[:DEFINED_IN]->(:Stmt_Class)-[:SUB {type: "stmts"}]->()-[:HAS]->(:Stmt_TraitUse)-[:SUB {type: "traits"}]->()-[:HAS]->(tname)
			 MATCH (t:Trait) WHERE t.fqcn = tname.fullName
			 MERGE (c)-[:USES_TRAIT]->(t)'
		);
	}

	private function findInterfaceImplementations() {
		$this->backend->execute(
			'MATCH (c:Class)-[:DEFINED_IN]->(:Stmt_Class)-[:SUB {type: "implements"}]->()-[:HAS]->(iname)
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
			'MATCH (sub:Class)-[:DEFINED_IN]->(:Stmt_Class)-[:SUB {type: "extends"}]->(ename)
			 MATCH (super:Class) WHERE super.fqcn=ename.fullName
			 MERGE (sub)-[:EXTENDS]->(super)
		'
		);
	}

	private function extractPropertiesForClass(Node $classNode, Node $classStmtNode) {
		$cypher =
			'START cls=node({def})
			 MATCH (cls)-[:SUB|HAS*]->(outer:Stmt_Property)-->()-->(inner:Stmt_PropertyProperty)
			 OPTIONAL MATCH (inner)-[:SUB {type: "default"}]->(default)
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
				'public'    => ($outerProperty->getProperty('type') & Class_::MODIFIER_PUBLIC) > 0,
				'private'   => ($outerProperty->getProperty('type') & Class_::MODIFIER_PRIVATE) > 0,
				'protected' => ($outerProperty->getProperty('type') & Class_::MODIFIER_PROTECTED) > 0,
				'static'    => ($outerProperty->getProperty('type') & Class_::MODIFIER_STATIC) > 0,
				'name'      => $innerProperty->getProperty('name'),
			];

			if ($outerProperty->getProperty('docComment')) {
				$properties['docComment'] = $outerProperty->getProperty('docComment');
			}

			if (!$row['existing']) {
				$result   =
					$create->execute(['prop' => $properties, 'definition' => $classStmtNode, 'class' => $classNode]);
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
		$cypher = 'MATCH (cls)-[:SUB {type: "stmts"}]->()-->(m:Stmt_ClassMethod) WHERE id(cls)={cls} RETURN m';
		$query  = $this->backend->createQuery($cypher, 'm');

		$cypher = 'MATCH (d) WHERE id(d)={definition}
		           MATCH (c) WHERE id(c)={class}
		           MERGE (m:Method {
		               public: {method}.public,
		               private: {method}.private,
		               protected: {method}.protected,
		               static: {method}.static,
		               abstract: {method}.abstract,
		               name: {method}.name,
		               fullName: c.fqcn + "::" + {method}.name
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

			$result = $create->execute(['method' => $properties, 'definition' => $methodStmt, 'class' => $classNode]);
			$method = $result[0]->node('m');

			$this->enrichNodeFromDocBlock($method, $methodStmt->getProperty('docComment'), $classNode);

			$paramsQuery = $this->backend->createQuery(
				'MATCH (m)-[:SUB {type: "params"}]->()-[:HAS]->(paramDefinition) WHERE id(m)={node}
				 OPTIONAL MATCH (paramDefinition)-[:SUB {type: "default"}]->(default)
				 OPTIONAL MATCH (m)<-[:DEFINED_IN]-()-[:HAS_PARAMETER]->(param {name: paramDefinition.name})
				 RETURN paramDefinition, default, param'
			);

			foreach ($paramsQuery->execute(['node' => $methodStmt]) as $order => $row) {
				$parameter     = $row->node('paramDefinition');
				$default       = $row->node('default');
				$parameterNode = $row->node('param');

				$properties = [
					'name'            => $parameter->getProperty('name'),
					'variadic'        => $parameter->getProperty('variadic'),
					'byRef'           => $parameter->getProperty('byRef'),
					'hasDefaultValue' => NULL !== $default
				];

				if (NULL === $parameterNode) {
					$parameterNode = $this->backend->createNode(
						$properties,
						'Parameter'
					);
					$parameterNode
						->relateTo($parameter, 'DEFINED_IN')
						->save();
					$method
						->relateTo($parameterNode, 'HAS_PARAMETER')
						->setProperty('ordering', $order)
						->save();
				} else {
					$parameterNode->setProperties($properties);
				}

				$parameterNode->save();

				$c = 'MATCH (p)-[:DEFINED_IN]->()-[:SUB {type: "type"}]->(tname) WHERE id(p)={parameter}
					  MERGE (t:Type {name: tname.fullName, primitive: false})
					  MERGE (p)-[:POSSIBLE_TYPE]->(t)';
				$this->backend->createQuery($c)->execute(['parameter' => $parameterNode]);
				$c = 'MATCH (p)-[:DEFINED_IN]->(pd) WHERE id(p)={parameter} AND pd.type IS NOT NULL AND NOT (pd)-[:SUB {type: "type"}]->()
					  MERGE (t:Type {name: pd.type, primitive: true})
					  MERGE (p)-[:POSSIBLE_TYPE]->(t)';
				$this->backend->createQuery($c)->execute(['parameter' => $parameterNode]);
			}
		}
	}

	private function enrichNodeFromDocBlock(Node $node, $docblock, Node $context = NULL) {
		$phpdoc = new DocBlock($docblock);
		$dirty  = FALSE;

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
				if ($dt === 'self') {
					$dt = $context->getProperty('fqcn');
				}
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
					$cypher = 'MATCH (type) WHERE id(type)={type}
					           MATCH (n) WHERE id(n)={node}
					           MERGE (n)-[:POSSIBLE_TYPE]->(type)';
					$this->backend->createQuery($cypher)->execute(['node' => $node, 'type' => $dataTypeNode]);
				}
			}
		}
	}

	/**
	 * @param Node $node
	 * @return Node
	 */
	private function getNamespaceForContext(Node $node) {
		$cypher = 'MATCH (n)-[:DEFINED_IN]->()<-[:SUB|HAS*]-(ns:Stmt_Namespace) WHERE id(n)={node} RETURN ns';
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
			 MATCH (n)-[:DEFINED_IN]->()<-[:SUB|HAS*]-(ns:Stmt_Namespace)
			 MATCH (ns)-[:SUB {type: "stmts"}]->()-->(:Stmt_Use)-->()-->(use:Stmt_UseUse)
			 RETURN use';
		$query   = $this->backend->createQuery($cypher, 'use');
		$imports = [];

		foreach ($query->execute(['node' => $node->getId()]) as $useStmt) {
			$imports[$useStmt->getProperty('alias')] = $useStmt->getProperty('name');
		}

		return $imports;
	}

	/**
	 * @param string $type
	 * @param array $importScope
	 * @param string $currentNamespace
	 * @return Node
	 * @throws \Exception
	 */
	private function getTypeNode($type, $importScope, $currentNamespace) {
		$buildOrGetPrimitiveNode = function ($type, $primitive = TRUE, $collection = FALSE) {
			$cypher = 'MERGE (n:Type{name: {name}, primitive: {primitive}, collection: {collection}}) RETURN n';
			$query  = $this->backend->createQuery($cypher, 'n');

			return $query->execute(['name' => $type, 'primitive' => $primitive])[0];
		};

		if (preg_match(',^(?P<inner>.+)\[\],', $type, $matches)) {
			$type = 'array<' . $matches['inner'] . '>';
		}

		if (preg_match(',^(?P<outer>.+)<(?P<inner>.+)>$,', $type, $matches)) {
			$inner = $this->getTypeNode($matches['inner'], $importScope, $currentNamespace);
			if ($inner !== NULL) {
				$cypher = 'MATCH (inner:Type) WHERE id(inner)={inner}
				           MERGE (t:Type {name: {name}, primitive: false, collection: true})
				           MERGE (t)-[:IS_COLLECTION_OF]->(inner)
				           RETURN t';
				$query  = $this->backend->createQuery($cypher, 't');

				return $query->execute(['name' => $type, 'inner' => $inner])[0];
			}
		}

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
				return $buildOrGetPrimitiveNode('array', TRUE, TRUE);
			case 'callable':
				return $buildOrGetPrimitiveNode('callable');
			case 'object':
				return $buildOrGetPrimitiveNode('object');
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
			 MERGE (t:Type {name: {fqcn}, primitive: false})
			 MERGE (t)-[:IS]->(c)
			 RETURN c"
		);

		$result = $q->execute($arguments);
		$class  = $result[0]->node('c');

		if ($row['type'] !== 'interface') {
			$this->extractPropertiesForClass($class, $cls);
		}

		$this->extractMethodsForClass($class, $cls);
	}
}
