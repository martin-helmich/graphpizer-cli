<?php
namespace Helmich\Graphizer\Persistence\Op;


use Everyman\Neo4j\Node;

class MatchNodeByNode extends AbstractOperation implements NodeMatcher {

	use EdgeBuilder;

	/** @var string */
	private $id;

	/** @var Node */
	private $node;

	public function __construct(Node $node) {
		$this->node = $node;
		$this->id   = $node->getProperty('__node_id');
	}

	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function toCypher() {
		return sprintf(
			'MATCH (%s) WHERE id(%s)={id_%s}',
			$this->id,
			$this->id,
			$this->id
		);
	}

	/**
	 * @return string
	 */
	public function getArguments() {
		return ['id_' . $this->id => $this->node->getId()];
	}

	/**
	 * @return NodeMatcher
	 */
	public function getMatcher() {
		return $this;
	}
}