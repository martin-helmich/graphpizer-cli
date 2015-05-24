<?php
namespace Helmich\Graphizer\Persistence;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\ResultSet;

class PreparedStatement {

	private $cypher;

	/**
	 * @var null
	 */
	private $resultVar;

	/**
	 * @var DebuggerInterface
	 */
	private $debugger;

	/**
	 * @var callable
	 */
	private $queryFactory;

	public function __construct(Client $client, $cypher, $resultVar = NULL, DebuggerInterface $debugger = NULL) {
		$this->cypher    = $cypher;
		$this->resultVar = $resultVar;
		$this->debugger  = $debugger ? $debugger : new NullDebugger();

		$this->queryFactory = function (array $parameters) use ($client, $cypher) {
			return new Query($client, $cypher, $parameters);
		};
	}

	public function setQueryFactory(callable $factory) {
		$this->queryFactory = $factory;
	}

	/**
	 * Repairs bad characters in input arguments. Neo4j
	 * @param array $arguments
	 * @return array
	 */
	private function fixArgumentCharset(array $arguments) {
		$regex = <<<'END'
/
  (
    (?: [\x00-\x7F]               # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]    # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2} # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3} # quadruple-byte sequence 11110xxx 10xxxxxx * 3
    ){1,100}                      # ...one or more times
  )
| ( [\x80-\xBF] )                 # invalid byte in range 10000000 - 10111111
| ( [\xC0-\xFF] )                 # invalid byte in range 11000000 - 11111111
/x
END;

		foreach ($arguments as $i => $argument) {
			if (is_array($argument)) {
				$argument = $this->fixArgumentCharset($argument);
			} elseif (is_string($argument)) {
				$argument = preg_replace_callback($regex, function ($captures) {
					if ($captures[1] != "") {
						// Valid byte sequence. Return unmodified.
						return $captures[1];
					} elseif ($captures[2] != "") {
						// Invalid byte of the form 10xxxxxx.
						// Encode as 11000010 10xxxxxx.
						return chr(0xC2) . $captures[2];
					} else {
						// Invalid byte of the form 11xxxxxx.
						// Encode as 11000011 10xxxxxx.
						return chr(0xC3) . chr(ord($captures[3]) - 64);
					}
				}, $argument);
			}

			$arguments[$i] = $argument;
		}

		return $arguments;
	}

	private function filterArguments(array $arguments) {
		foreach ($arguments as $key => $value) {
			if ($value instanceof Node) {
				$arguments[$key] = $value->getId();
			}
		}

		return $this->fixArgumentCharset($arguments);
	}

	/**
	 * @param array $parameters
	 * @return ResultSet|TypedResultRowAdapter[]|\Everyman\Neo4j\Node[]
	 */
	public function execute(array $parameters = []) {
		$parameters = $this->filterArguments($parameters);

		$this->debugger->queryExecuting($this->cypher, $parameters);

		$query  = call_user_func($this->queryFactory, $parameters);

		try {
			$result = new TypedResultSetProxy($query->getResultSet());
		} catch (\Exception $e) {
			echo $this->cypher;
			var_dump($parameters);
			throw $e;
		}

		$this->debugger->queryExecuted($this->cypher, $parameters);

		if ($this->resultVar !== NULL) {
			return new SinglePropertyFilter($result, $this->resultVar);
		} else {
			return $result;
		}
	}
}