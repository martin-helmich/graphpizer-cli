<?php
namespace Helmich\Graphizer\Tests\Functional\Modeler;

use Helmich\Graphizer\Modeler\ClassModelGenerator;
use Helmich\Graphizer\Modeler\NamespaceResolver;
use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Tests\Functional\AbstractFunctionalTestCase;
use Helmich\Graphizer\Writer\FileWriter;
use Helmich\Graphizer\Writer\FileWriterBuilder;

class ClassModelGeneratorTest extends AbstractFunctionalTestCase {

	/**
	 * @var FileWriter
	 */
	private $writer;

	/**
	 * @var ClassModelGenerator
	 */
	private $modelBuilder;

	public function setUp() {
		parent::setUp();
		$backend = new Backend(static::$client);

		$this->writer       = (new FileWriterBuilder($backend))->build();
		$this->modelBuilder = new ClassModelGenerator($backend, new NamespaceResolver($backend));

		$this->writer->readFile(__DIR__ . '/../Fixtures/advanced_hello_world.php');
		$this->modelBuilder->run();
	}

	/**
	 * @test
	 * @medium
	 */
	public function shouldLoadClasses() {
		$this->assertCypherQueryReturnsCount(
			1,
			'MATCH (c1:Class) WHERE c1.fqcn="SpokenGreeter"
			 MATCH (c2:Class) WHERE c2.fqcn="DefaultSayer"
			 MATCH (c3:Class) WHERE c3.fqcn="GermanGreeter"
			 RETURN c1, c2, c3'
		);
	}

	/**
	 * @test
	 * @medium
	 */
	public function shouldLoadProperties() {
		$this->assertCypherQueryReturnsCount(
			2,
			'MATCH (spoken:Class)-[:HAS_PROPERTY]->(p:Property) WHERE spoken.fqcn="SpokenGreeter"
			 RETURN p'
		);
	}

	/**
	 * @test
	 * @medium
	 */
	public function shouldLoadMethods() {
		$this->assertCypherQueryReturnsCount(
			2,
			'MATCH (spoken:Class)-[:HAS_METHOD]->(m:Method) WHERE spoken.fqcn="SpokenGreeter"
			 RETURN m'
		);
	}

	/**
	 * @test
	 * @medium
	 */
	public function shouldLoadInterfaces() {
		$this->assertCypherQueryReturnsCount(
			1,
			'MATCH (sayer:Interface) WHERE sayer.fqcn="Sayer"
			 MATCH (greeter:Interface) WHERE greeter.fqcn="Greeter"
			 RETURN sayer, greeter'
		);
	}

	/**
	 * @test
	 * @medium
	 */
	public function shouldLoadTraits() {
		$this->assertCypherQueryReturnsCount(1, 'MATCH (a:Trait) WHERE a.fqcn="PrintTrait" RETURN a');
	}

	/**
	 * @test
	 * @medium
	 */
	public function shouldLoadInterfaceImplementations() {
		$this->assertCypherQueryReturnsCount(
			1,
			'MATCH (gg:Class)-[:IMPLEMENTS]->(g:Interface) WHERE gg.fqcn="GermanGreeter" AND g.fqcn="Greeter" RETURN gg'
		);
	}

	/**
	 * @test
	 * @medium
	 */
	public function shouldLoadTraitUsages() {
		$this->assertCypherQueryReturnsCount(1, 'MATCH (c:Class)-[:USES_TRAIT]->(t:Trait) WHERE c.fqcn="DefaultSayer" AND t.fqcn="PrintTrait" RETURN c, t');
	}

	/**
	 * @test
	 * @medium
	 */
	public function shouldBeIdempotent() {
		$this->modelBuilder->run();

		$this->assertCypherQueryReturnsCount(
			1,
			'MATCH (c1:Class) WHERE c1.fqcn="SpokenGreeter"
			 MATCH (c2:Class) WHERE c2.fqcn="DefaultSayer"
			 MATCH (c3:Class) WHERE c3.fqcn="GermanGreeter"
			 RETURN c1, c2, c3'
		);
		$this->assertCypherQueryReturnsCount(
			2,
			'MATCH (spoken:Class)-[:HAS_METHOD]->(m:Method) WHERE spoken.fqcn="SpokenGreeter"
			 RETURN m'
		);
		$this->assertCypherQueryReturnsCount(
			2,
			'MATCH (spoken:Class)-[:HAS_PROPERTY]->(p:Property) WHERE spoken.fqcn="SpokenGreeter"
			 RETURN p'
		);
	}
}