<?php
namespace Helmich\Graphizer\Service;

use Helmich\Graphizer\Modeler\ClassModelGenerator;
use Helmich\Graphizer\Modeler\NamespaceResolver;
use Helmich\Graphizer\Modeler\UsageAnalyzer;
use Helmich\Graphizer\Persistence\Backend;

class ModelGenerationService {

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function generateModel($withUsage = FALSE) {
		$namespaceResolver = new NamespaceResolver($this->backend);

		$modelGenerator = new ClassModelGenerator($this->backend, $namespaceResolver);
		$modelGenerator->run();

		if ($withUsage) {
			$usageAnalyzer = new UsageAnalyzer($this->backend);
			$usageAnalyzer->run();
		}
	}
}