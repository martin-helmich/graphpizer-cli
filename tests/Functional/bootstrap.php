<?php
namespace Helmich\Graphizer\Tests\Functional;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php');

function cypherQuery($query) {
	return new CypherQueryWrapper(AbstractFunctionalTestCase::$client, $query);
}

function hasResultCount($n) {
	return new CypherQueryCountConstraint($n);
}

function matchesSyntaxTree($ast) {
	return new ASTMachingConstraint($ast);
}

class CypherQueryWrapper {

	/**
	 * @var Client
	 */
	private $client;

	private $query;

	public function __construct(Client $client, $query) {
		$this->client = $client;
		$this->query  = $query;
	}

	public function buildQuery() {
		return new Query($this->client, $this->query);
	}
}

class ASTMachingConstraint extends \PHPUnit_Framework_Constraint {

	/**
	 * @var array
	 */
	private $ast;

	/**
	 * @var PrettyPrinterAbstract
	 */
	private $printer;

	public function __construct($ast) {
		parent::__construct();
		$this->ast     = $ast;
		$this->printer = new Standard();
	}

	/**
	 * Returns a string representation of the object.
	 *
	 * @return string
	 */
	public function toString() {
		return 'is identical to given AST';
	}

	protected function matches($other) {
		$expectedOutput = $this->printer->prettyPrint($this->ast);
		$output         = $this->printer->prettyPrint($other);

		return $expectedOutput == $output;
	}
}

class CypherQueryCountConstraint extends \PHPUnit_Framework_Constraint {

	/**
	 * @var
	 */
	private $n;

	public function __construct($n) {
		parent::__construct();
		$this->n = $n;
	}

	/**
	 * Returns a string representation of the object.
	 *
	 * @return string
	 */
	public function toString() {
		return 'contains ' . $this->n . ' result nodes';
	}

	protected function matches($other) {
		if ($other instanceof CypherQueryWrapper) {
			$query = $other->buildQuery();
			$count = $query->getResultSet()->count();

			if ($count !== $this->n) {
				return FALSE;
			}
			return TRUE;
		}
		return FALSE;
	}
}