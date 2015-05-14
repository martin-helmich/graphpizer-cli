<?php
namespace Helmich\Graphizer\Persistence\Op;


class ReturnObject implements Operation {

	/**
	 * @var NodeMatcher
	 */
	private $node;

	public function __construct(NodeMatcher $node) {
		$this->node = $node;
	}

	/**
	 * @return string
	 */
	public function toCypher() {
		return sprintf('RETURN %s', $this->node->getId());
	}

	/**
	 * @return string
	 */
	public function getArguments() {
		return [];
	}

	/**
	 * @return NodeMatcher[]
	 */
	public function getRequiredNodes() {
		return [$this->node];
	}

}