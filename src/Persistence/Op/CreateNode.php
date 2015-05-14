<?php
namespace Helmich\Graphizer\Persistence\Op;


class CreateNode extends AbstractOperation implements NodeMatcher {

	use EdgeBuilder;
	use PropertyFilter;

	private $id;

	private $labels;

	/**
	 * @var array
	 */
	private $properties;

	public function __construct($label, array $properties = [], $id = NULL) {
		if ($id === NULL) {
			$id = uniqid('node');
		}

		$this->id         = $id;
		$this->labels     = [$label];
		$this->properties = $this->filterProperties($properties);
	}

	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getLabel() {
		return $this->labels[0];
	}

	/**
	 * @param string $label
	 * @return void
	 */
	public function addLabel($label) {
		$this->labels[] = $label;
	}

	/**
	 * @return array
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * @param array $newProperties
	 */
	public function mergeProperties(array $newProperties) {
		$this->properties = array_merge($this->properties, $this->filterProperties($newProperties));
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @return void
	 */
	public function setProperty($key, $value) {
		if ($value !== NULL) {
			$this->properties[$key] = $value;
		}
	}

	/**
	 * @return string
	 */
	public function toCypher() {
		return sprintf(
			'CREATE (%s:%s {prop_%s})',
			$this->id,
			implode(':', $this->labels),
			$this->id
		);
	}

	/**
	 * @return string
	 */
	public function getArguments() {
		$properties              = $this->properties;
		$properties['__node_id'] = $this->id;

		return ['prop_' . $this->id => $properties];
	}

	/**
	 * @return NodeMatcher
	 */
	public function getMatcher() {
		return new MatchNodeByIdProperty($this->id);
	}
}