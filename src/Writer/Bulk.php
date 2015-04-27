<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Persistence\Backend;

class Bulk {

	private $cypherQueries = [];

	private $arguments     = [];

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function push($cypher, array $arguments = []) {
		$this->cypherQueries[] = $cypher;
		$this->arguments       = array_merge($this->arguments, $this->filterNullValues($arguments));
	}

	private function filterNullValues(array $arguments) {
		$filtered = [];

		foreach ($arguments as $key => $value) {
			if (NULL !== $value) {
				$filtered[$key] = $value;
			}
		}

		return $filtered;
	}

	public function mergeArgument($argumentName, array $values) {
		$values = $this->filterNullValues($values);
		if (array_key_exists($argumentName, $this->arguments)) {
			$this->arguments[$argumentName] = array_merge($this->arguments[$argumentName], $values);
		} else {
			$this->arguments[$argumentName] = $values;
		}
	}

	public function renderCypher() {
		return implode("\n", $this->cypherQueries);
	}

	/**
	 * @return \Helmich\Graphizer\Persistence\TypedResultRowAdapter[]
	 */
	public function evaluate() {
		$cypher = $this->renderCypher();
		$query  = $this->backend->createQuery($cypher);
		return $query->execute($this->arguments);
	}
}