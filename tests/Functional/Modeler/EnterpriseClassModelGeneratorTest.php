<?php
namespace Helmich\Graphizer\Tests\Functional\Modeler;

use Helmich\Graphizer\Modeler\ClassModelGenerator;
use Helmich\Graphizer\Modeler\NamespaceResolver;
use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Tests\Functional\AbstractFunctionalTestCase;
use Helmich\Graphizer\Writer\FileWriter;
use Helmich\Graphizer\Writer\FileWriterBuilder;

class EnterpriseClassModelGeneratorTest extends AbstractFunctionalTestCase {

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
	public function classesAreLoaded() {
		$this->assertCypherQueryReturnsCount(
			10,
			'MATCH (c1:Class)
			 RETURN c1'
		);
	}

}