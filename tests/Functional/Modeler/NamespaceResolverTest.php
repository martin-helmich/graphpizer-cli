<?php
namespace Helmich\Graphizer\Tests\Functional\Modeler;

use Helmich\Graphizer\Modeler\ClassModelGenerator;
use Helmich\Graphizer\Modeler\NamespaceResolver;
use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Tests\Functional\AbstractFunctionalTestCase;
use Helmich\Graphizer\Writer\FileWriter;
use Helmich\Graphizer\Writer\FileWriterBuilder;

class NamespaceResolverTest extends AbstractFunctionalTestCase {

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

		$this->writer->readFile(__DIR__ . '/../Fixtures/enterprise_hello_world.php');
		$this->modelBuilder->run();
	}

	/**
	 * @test
	 * @medium
	 */
	public function namespacesAreResolved() {
		$cypher = 'MATCH (n:Stmt_Name) WHERE n.fullName = {name} RETURN n';
		$this->assertCypherQueryReturnsCount(1, $cypher, ['name' => 'Big\Enterprise\Output\ConsoleOutput']);
	}
}