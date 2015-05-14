<?php
namespace Helmich\Graphizer\Persistence\Op;

class UpdateNode implements Operation {

	/**
	 * @var NodeMatcher
	 */
	private $node;

	/**
	 * @var array
	 */
	private $properties;

	public function __construct(NodeMatcher $node, array $properties) {
		$this->node       = $node;
		$this->properties = $properties;
	}

	/**
	 * @return string
	 */
	public function toCypher() {
		$fieldNames = [];
		foreach ($this->properties as $key => $value) {
			$fieldNames[] = sprintf('%s.%s = {%s}', $this->node->getId(), $key, $this->node->getId() . '_' . $key);
		}
		return 'SET ' . implode(', ', $fieldNames);
	}

	/**
	 * @return string
	 */
	public function getArguments() {
		$args = [];

		foreach ($this->properties as $key => $value) {
			$args[$this->node->getId() . '_' . $key] = $value;
		}

		return $args;
	}

	/**
	 * @return NodeMatcher[]
	 */
	public function getRequiredNodes() {
		return [$this->node];
	}


}