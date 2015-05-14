<?php
namespace Helmich\Graphizer\Persistence\Op;


class CreateEdge implements Operation {

	use PropertyFilter;

	/** @var string */
	private $id;

	/** @var NodeMatcher */
	private $start;

	/** @var NodeMatcher */
	private $end;

	/** @var string */
	private $type;

	/** @var array */
	private $properties;

	public function __construct(NodeMatcher $start, NodeMatcher $end, $type, array $properties = []) {
		$this->id         = uniqid('rel');
		$this->start      = $start;
		$this->end        = $end;
		$this->type       = $type;
		$this->properties = $this->filterProperties($properties);
	}

	/**
	 * @return NodeMatcher
	 */
	public function getStart() {
		return $this->start;
	}

	/**
	 * @return NodeMatcher
	 */
	public function getEnd() {
		return $this->end;
	}

	/**
	 * @return NodeMatcher[]
	 */
	public function getRequiredNodes() {
		return [$this->start, $this->end];
	}


	/**
	 * @return string
	 */
	public function toCypher() {
		if (count($this->properties) > 0) {
			return sprintf(
				'CREATE (%s)-[%s:%s {prop_%s}]->(%s)',
				$this->start->getId(),
				$this->id,
				$this->type,
				$this->id,
				$this->end->getId()
			);
		} else {
			return sprintf(
				'CREATE (%s)-[%s:%s]->(%s)',
				$this->start->getId(),
				$this->id,
				$this->type,
				$this->end->getId()
			);
		}
	}

	/**
	 * @return string
	 */
	public function getArguments() {
		if (count($this->properties) > 0) {
			return [
				'prop_' . $this->id => $this->properties
			];
		} else {
			return [];
		}
	}
}