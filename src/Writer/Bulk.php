<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Persistence\Backend;

class Bulk {

	protected $cypherQueries = [];

	protected $arguments = [];

	/**
	 * @var Backend
	 */
	protected $backend;

	public function __construct(Backend $backend, array $queries = [], array $arguments = []) {
		$this->backend = $backend;
		$this->cypherQueries = $queries;
		$this->arguments = $arguments;
	}

	public function merge(Bulk $other) {
		$mergedQueries = array_merge($this->cypherQueries, $other->cypherQueries);
		$mergedArguments = array_merge($this->arguments, $other->arguments);

		return new Bulk($this->backend, $mergedQueries, $mergedArguments);
	}

	public function push($cypher, array $arguments = []) {
		$cypher = trim($cypher);
		$this->cypherQueries[] = $cypher;

		$arguments = $this->filterNullValues($arguments);

		$this->arguments = array_merge($this->arguments, $arguments);
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
		if (0 === count($this->cypherQueries)) {
			return NULL;
		}

		$cypher = $this->renderCypher();
		$query = $this->backend->createQuery($cypher);

		try {
			return $query->execute($this->arguments);
		} catch (\Exception $e) {
			echo $cypher;
			var_dump($this->arguments);
			throw $e;
		}
	}
}