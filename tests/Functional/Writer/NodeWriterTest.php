<?php
namespace Helmich\Graphizer\Tests\Functional\Writer;

use Helmich\Graphizer\Persistence\Neo4j\Backend;
use Helmich\Graphizer\Tests\Functional\AbstractFunctionalTestCase;
use Helmich\Graphizer\Writer\NodeWriter;
use Helmich\Graphizer\Writer\NodeWriterBuilder;
use PhpParser\Comment\Doc;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Function_;
use function assertThat;
use function Helmich\Graphizer\Tests\Functional\cypherQuery;
use function Helmich\Graphizer\Tests\Functional\hasResultCount;

class NodeWriterTest extends AbstractFunctionalTestCase {

	/**
	 * @var NodeWriter
	 */
	private $writer;

	public function setUp() {
		parent::setUp();
		$backend = new Backend(static::$client);
		$this->writer = (new NodeWriterBuilder($backend))->build();
	}

	/**
	 * @test
	 * @medium
	 */
	public function scalarNodeIsCorrectlyStored() {
		$phpNode = new String_('foo');
		$this->writer->writeNode($phpNode);

		assertThat(cypherQuery('MATCH (c:Scalar_String) WHERE c.value="foo" RETURN c'), hasResultCount(1));
	}

	/**
	 * @test
	 * @medium
	 */
	public function commentsAreStored() {
		$comment = new Doc("/**\n * @var Foobar\n */");
		$phpNode = new Function_('foobar', [], ['comments' => [$comment]]);

		$this->writer->writeNode($phpNode);

		assertThat(cypherQuery('MATCH (c:Stmt_Function)-[:HAS_COMMENT]->(d:DocComment) WHERE c.name="foobar" RETURN c'), hasResultCount(1));
	}

	/**
	 * @test
	 * @medium
	 */
	public function subNodeRelationsAreStored() {
		$node = new Assign(new Variable('foo'), new LNumber(42));
		$this->writer->writeNode($node);

		assertThat(cypherQuery('MATCH (a:Expr_Assign)-[:SUB{type: "var"}]->(b:Expr_Variable {name: "foo"}) RETURN a,b'), hasResultCount(1));
	}
}