<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Persistence\Op\NodeMatcher;
use Helmich\Graphizer\Persistence\Op\Operation;

class Bulk {

	protected $cypherQueries = [];

	protected $arguments = [];

	/**
	 * @var Operation[]
	 */
	protected $operations = [];

	/**
	 * @var Backend
	 */
	protected $backend;
	/**
	 * @var int
	 */
	private $chunkSize;

	public function __construct(Backend $backend, array $queries = [], array $arguments = [], $chunkSize = 1000) {
		$this->backend       = $backend;
		$this->cypherQueries = $queries;
		$this->arguments     = $arguments;
		$this->chunkSize     = $chunkSize;
	}

	public function merge(Bulk $other) {
		$mergedQueries   = array_merge($this->cypherQueries, $other->cypherQueries);
		$mergedArguments = array_merge($this->arguments, $other->arguments);

		return new Bulk($this->backend, $mergedQueries, $mergedArguments);
	}

	public function push(Operation $operation) {
		$this->operations[] = $operation;
//		$cypher = trim($cypher);
//		$this->cypherQueries[] = $cypher;
//
//		$arguments = $this->filterNullValues($arguments);
//
//		$this->arguments = array_merge($this->arguments, $arguments);
	}

//	private function filterNullValues(array $arguments) {
//		$filtered = [];
//
//		foreach ($arguments as $key => $value) {
//			if (NULL !== $value) {
//				$filtered[$key] = $value;
//			}
//		}
//
//		return $filtered;
//	}

//	public function mergeArgument($argumentName, array $values) {
//		$values = $this->filterNullValues($values);
//		if (array_key_exists($argumentName, $this->arguments)) {
//			$this->arguments[$argumentName] = array_merge($this->arguments[$argumentName], $values);
//		} else {
//			$this->arguments[$argumentName] = $values;
//		}
//	}

//	public function renderCypher() {
//		return implode("\n", $this->cypherQueries);
//	}

	/**
	 * @return \Helmich\Graphizer\Persistence\TypedResultRowAdapter[]
	 */
	public function evaluate() {
		if (0 === count($this->operations)) {
			return NULL;
		}

		/** @var Operation[][] $chunks */
		$chunks     = array_chunk($this->operations, $this->chunkSize);
		$lastResult = NULL;

		foreach ($chunks as $chunk) {
			$cypher     = '';
			$arguments  = [];
			$knownNodes = [];

			foreach ($chunk as $operation) {
				if ($operation instanceof NodeMatcher) {
					$knownNodes[$operation->getId()] = $operation->getId();
				}

				foreach ($operation->getRequiredNodes() as $requiredNode) {
					if (!isset($knownNodes[$requiredNode->getId()])) {
						$knownNodes[$requiredNode->getId()] = $requiredNode->getId();

						$matcher   = $requiredNode->getMatcher();
						$cypher    = $matcher->toCypher() . "\n" . $cypher;
						$arguments = array_merge($arguments, $matcher->getArguments());
					}
				}

				$arguments = array_merge($arguments, $operation->getArguments());
				$cypher .= $operation->toCypher() . "\n";
			}

			$query      = $this->backend->createQuery($cypher);
			$lastResult = $query->execute($arguments);
		}

		return $lastResult;

//		if (0 === count($this->cypherQueries)) {
//			return NULL;
//		}
//
//		$cypher = $this->renderCypher();
//		$query = $this->backend->createQuery($cypher);
//
//		try {
//			return $query->execute($this->arguments);
//		} catch (\Exception $e) {
//			echo $cypher;
//			var_dump($this->arguments);
//			throw $e;
//		}
	}
}