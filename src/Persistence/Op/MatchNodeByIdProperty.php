<?php
namespace Helmich\Graphizer\Persistence\Op;


class MatchNodeByIdProperty extends AbstractOperation implements NodeMatcher {

	use EdgeBuilder;

	/** @var string */
	private $id;

	public function __construct($id) {
		$this->id = $id;
	}

	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function toCypher() {
		return sprintf(
			'MATCH (%s {__node_id: {id%s}})',
			$this->id,
			$this->id
		);
	}

	/**
	 * @return string
	 */
	public function getArguments() {
		return ['id' . $this->id => $this->id];
	}

	/**
	 * @return NodeMatcher
	 */
	public function getMatcher() {
		return $this;
	}
}