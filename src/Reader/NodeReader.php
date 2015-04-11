<?php
namespace Helmich\Graphizer\Reader;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node as NeoNode;
use Everyman\Neo4j\Relationship;
use Helmich\Graphizer\Data\NodeCollection;
use Helmich\Graphizer\Persistence\Backend;
use PhpParser\Node as PhpNode;

class NodeReader implements NodeReaderInterface {

	/**
	 * @var Backend
	 */
	private $backend;

	/**
	 * @var CommentReader
	 */
	private $commentReader;

	public function __construct(Backend $backend, CommentReader $commentReader) {
		$this->backend       = $backend;
		$this->commentReader = $commentReader;
	}

	public function readNode(NeoNode $nn) {
		if ($this->isCollection($nn)) {
			$nodes = [];
			foreach ((new NodeCollection($this->backend, $nn))->getChildNodes() as $childNode) {
				$nodes[] = $this->readNode($childNode);
			}
			return $nodes;
		} else if (($class = $this->getClassForNode($nn)) !== NULL) {
			$class = new \ReflectionClass($class);

			/** @var PhpNode $instance */
			$instance = $class->newInstanceWithoutConstructor();
			$rprop    = $class->getProperty('attributes');
			$rprop->setAccessible(TRUE);
			$rprop->setValue($instance, []);

			$this->populateSubNodes($nn, $instance);
			$this->populateComments($nn, $instance);

			return $instance;
		} else if ($this->doesNodeHaveLabel($nn, 'Literal')) {
			return $nn->getProperty('value');
		} else {
			throw new \Exception('Do not know what to do with node #' . $nn->getId());
		}
	}

	private function doesNodeHaveLabel(NeoNode $nn, $labelName) {
		foreach ($nn->getLabels() as $label) {
			if ($label instanceof Label && $label->getName() === $labelName) {
				return TRUE;
			}
		}
		return FALSE;
	}

	private function isCollection(NeoNode $nn) {
		return $this->doesNodeHaveLabel($nn, NodeCollection::NODE_NAME);
	}

	private function getClassForNode(NeoNode $nn) {
		foreach ($nn->getLabels() as $label) {
			/** @var Label $label */
			$baseClassName       = 'PhpParser\\Node\\' . str_replace('_', '\\', $label->getName());
			$potentialClassNames = [
				$baseClassName,
				$baseClassName . '_'
			];

			foreach ($potentialClassNames as $potentialClassName) {
				if (class_exists($potentialClassName)) {
					return $potentialClassName;
				}
			}
		}
		return NULL;
	}

	/**
	 * @param NeoNode $nn
	 * @param         $instance
	 * @throws \Exception
	 */
	private function populateSubNodes(NeoNode $nn, PhpNode $instance) {
		$properties = $nn->getProperties();
		foreach ($instance->getSubNodeNames() as $subNodeName) {
			if (($rel = $nn->getFirstRelationship('SUB_' . strtoupper($subNodeName), Relationship::DirectionOut)) !==
				NULL
			) {
				$value                    = $this->readNode($rel->getEndNode());
				$instance->{$subNodeName} = $value;
			} else if (array_key_exists($subNodeName, $properties)) {
				$value = $properties[$subNodeName];
				if ($value === '~~EMPTY_ARRAY~~') {
					$value = [];
				}
				$instance->{$subNodeName} = $value;
			}
		}
	}

	private function populateComments(NeoNode $nn, PhpNode $instance) {
		$cypher = 'MATCH (n)-[:HAS_COMMENT]->(c:Comment) WHERE id(n)={node} RETURN c';
		$query = $this->backend->createQuery($cypher, 'c');

		$comments = [];
		foreach($query->execute(['node' => $nn]) as $commentNode) {
			$comments[] = $this->commentReader->readComment($commentNode);
		}

		$instance->setAttribute('comments', $comments);
	}
}