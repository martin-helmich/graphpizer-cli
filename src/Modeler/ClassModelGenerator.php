<?php
namespace Helmich\Graphizer\Modeler;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Helmich\Graphizer\Persistence\Backend;
use phpDocumentor\Reflection\DocBlock;
use PhpParser\Node\Stmt\Class_;

class ClassModelGenerator {

	/**
	 * @var Client
	 */
	private $client;

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Client $client, Backend $backend) {
		$this->client  = $client;
		$this->backend = $backend;
	}

	public function run() {
		$query = $this->backend
			->createQuery('MATCH (cls:Stmt_Class) OPTIONAL MATCH (ns:Stmt_Namespace)-[*..]->(cls) RETURN cls, ns');
		$label = $this->client->makeLabel('Class');

		foreach ($query->execute() as $row) {
			$cls = $row->node('cls');
			$ns  = $row->node('ns');

			$class = $this->client->makeNode();
			$class->setProperty('name', $cls->getProperty('name'));

			if ($ns) {
				$class->setProperty('namespace', $ns->getProperty('name'));
				$class->setProperty('fqcn', $ns->getProperty('name') . '\\' . $cls->getProperty('name'));
			} else {
				$class->setProperty('namespace', NULL);
				$class->setProperty('fqcn', $cls->getProperty('name'));
			}

			$class->setProperty('abstract', ($cls->getProperty('type') & Class_::MODIFIER_ABSTRACT) > 0);
			$class->setProperty('final', ($cls->getProperty('type') & Class_::MODIFIER_FINAL) > 0);

			$class->save();
			$class->addLabels([$label]);

			$class
				->relateTo($cls, 'DEFINED_IN')
				->save();

			$this->extractPropertiesForClass($class, $cls);
			$this->extractMethodsForClass($class, $cls);
		}

		$this->consolidateTypesWithClasses();
	}

	private function consolidateTypesWithClasses() {
		$this->backend->execute('MATCH (t:Type), (n:Class) WHERE t.name = n.name CREATE (t)-[:IS]->(c)');
	}

	private function extractPropertiesForClass(Node $classNode, Node $classStmtNode) {
		$cypher =
			'START cls=node({cls}) MATCH (cls)-[*..]->(outer:Stmt_Property)-->()-->(inner:Stmt_PropertyProperty) RETURN outer, inner';
		$query  = $this->backend->createQuery($cypher);
		$label  = $this->client->makeLabel('Property');

		foreach ($query->execute(['cls' => $classStmtNode->getId()]) as $row) {
			$outerProperty = $row->node('outer');
			$innerProperty = $row->node('inner');

			$property = $this->client->makeNode();
			$property->setProperty('public', ($outerProperty->getProperty('type') & Class_::MODIFIER_PUBLIC) > 0);
			$property->setProperty('private', ($outerProperty->getProperty('type') & Class_::MODIFIER_PRIVATE) > 0);
			$property->setProperty('protected', ($outerProperty->getProperty('type') & Class_::MODIFIER_PROTECTED) > 0);
			$property->setProperty('static', ($outerProperty->getProperty('type') & Class_::MODIFIER_STATIC) > 0);
			$property->setProperty('name', $innerProperty->getProperty('name'));
			$property->setProperty('docComment', $outerProperty->getProperty('docComment'));
			$property->save();

			$property->addLabels([$label]);

			$property
				->relateTo($outerProperty, 'DEFINED_IN')
				->save();

			$classNode
				->relateTo($property, 'HAS_PROPERTY')
				->save();

			$defaultValueRelation = $innerProperty->getFirstRelationship('SUB_DEFAULT');
			if ($defaultValueRelation !== NULL) {
				$defaultValueNode = $defaultValueRelation->getEndNode();

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
		$label  = $this->client->makeLabel('Method');

		foreach ($query->execute(['cls' => $classStmtNode->getId()]) as $methodStmt) {
			/** @var Node $methodStmt */
			$method = $this->client->makeNode(
				[
					'public'    => ($methodStmt->getProperty('type') & Class_::MODIFIER_PUBLIC) > 0,
					'private'   => ($methodStmt->getProperty('type') & Class_::MODIFIER_PRIVATE) > 0,
					'protected' => ($methodStmt->getProperty('type') & Class_::MODIFIER_PROTECTED) > 0,
					'static'    => ($methodStmt->getProperty('type') & Class_::MODIFIER_STATIC) > 0,
					'abstract'  => ($methodStmt->getProperty('type') & Class_::MODIFIER_ABSTRACT) > 0,
					'name'      => $methodStmt->getProperty('name')
				]
			);
			$method->save();
			$method->addLabels([$label]);

			$method->relateTo($methodStmt, 'DEFINED_IN')->save();
			$classNode->relateTo($method, 'HAS_METHOD')->save();

			$this->enrichNodeFromDocBlock($method, $methodStmt->getProperty('docComment'));
		}
	}

	private function enrichNodeFromDocBlock(Node $node, $docblock) {
		$phpdoc = new DocBlock($docblock);

		if (empty($phpdoc->getShortDescription()) === FALSE) {
			$node->setProperty('shortDescription', $phpdoc->getShortDescription());
		}

		if (empty($phpdoc->getLongDescription()) === FALSE) {
			$node->setProperty('longDescription', $phpdoc->getLongDescription()->getContents());
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
				$dataTypeNode = $this->getTypeNode($dataType, $imports, $namespace->getProperty('name'));
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
		$buildOrGetPrimitiveNode = function ($type, $primitive=TRUE) {
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
			case 'mixed':
				return NULL;
			default:
				if ($type{0} == '\\') {
					$name = ltrim($type, '\\');
				} else {
					if (array_key_exists($type, $importScope)) {
						$name = $importScope[$type];
					} else {
						$name = $currentNamespace . '\\' . $type;
					}
				}

				/** @var Node $node */
				return $buildOrGetPrimitiveNode($name, FALSE);
		}
	}
}