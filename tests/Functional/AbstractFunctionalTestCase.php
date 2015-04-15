<?php
namespace Helmich\Graphizer\Tests\Functional;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use PhpParser\PrettyPrinter\Standard;

abstract class AbstractFunctionalTestCase extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Client
	 */
	static public $client;

	public static function setUpBeforeClass() {
		static::$client = new Client();
		static::$client->getTransport()->setAuth('neo4j', 'martin123');
	}

	public function setUp(){
		$cypher = 'MATCH (n) OPTIONAL MATCH (n)-[r]-() DELETE n,r';
		static::$client->executeCypherQuery(new Query(static::$client, $cypher));
	}

	public function assertSyntaxTreesMatch($expected, $actual) {
		$printer = new Standard();

		$expectedOutput = $printer->prettyPrint($expected);
		$output         = $printer->prettyPrint($actual);

		$this->assertEquals($expectedOutput, $output);
	}

	public function assertCypherQueryReturnsCount($expectedCount, $cypher) {
		$query = new Query(static::$client, $cypher);
		$this->assertEquals($expectedCount, $query->getResultSet()->count());
	}

	public function tearDown() {
//		$cypher = 'MATCH (n) OPTIONAL MATCH (n)-[r]-() DELETE n,r';
//		static::$client->executeCypherQuery(new Query(static::$client, $cypher));
	}
}