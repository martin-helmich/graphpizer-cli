<?php
namespace Helmich\Graphizer\Tests\Functional\Writer;

use Everyman\Neo4j\Cypher\Query;
use Helmich\Graphizer\Reader\NodeReaderBuilder;
use Helmich\Graphizer\Writer\NodeWriter;
use Helmich\Graphizer\Reader\NodeReader;
use Helmich\Graphizer\Writer\NodeWriterBuilder;
use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Tests\Functional\AbstractFunctionalTestCase;
use PhpParser\Lexer;
use PhpParser\Parser;
use function \assertThat;
use function \Helmich\Graphizer\Tests\Functional\matchesSyntaxTree;

class FullCycleTest extends AbstractFunctionalTestCase {

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var NodeWriter
	 */
	private $writer;

	/**
	 * @var NodeReader
	 */
	private $reader;

	public function setUp() {
		parent::setUp();
		$backend = new Backend(static::$client);

		$this->writer = (new NodeWriterBuilder($backend))->build();
		$this->reader = (new NodeReaderBuilder($backend))->build();
		$this->parser = new Parser(new Lexer());
	}

	public function getFixtureFiles() {
		$a = glob(__DIR__ . '/../Fixtures/*.php');
		return array_map(function($file) {
			return [basename($file)];
		}, $a);
//
//		return [
//			['simple_hello_world.php']
//		];
	}

	/**
	 * @param $fixtureFile
	 * @test
	 * @dataProvider getFixtureFiles
	 * @medium
	 */
	public function reconstitutedTreeIsIdenticalToOriginal($fixtureFile) {
		$ast = $this->parser->parse(file_get_contents(__DIR__ . '/../Fixtures/' . $fixtureFile));
		$this->writer->writeNodeCollection($ast);

		$query = new Query(static::$client, 'MATCH (c:Collection) WHERE c.fileRoot=true RETURN c');
		$collection = $query->getResultSet()[0]['c'];

		$reconstitutedAst = $this->reader->readNode($collection);
		$this->assertSyntaxTreesMatch($ast, $reconstitutedAst);
//		assertThat($reconstitutedAst, matchesSyntaxTree($ast));
	}
}