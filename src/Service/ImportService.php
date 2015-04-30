<?php
namespace Helmich\Graphizer\Service;

use Helmich\Graphizer\Modeler\NamespaceResolver;
use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Writer\FileWriterBuilder;

class ImportService {

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function importSourceFiles(array $sourceFiles, $pruneFirst = FALSE, callable $debugCallback = NULL) {
		if ($pruneFirst) {
			$this->backend->wipe();
		}

		$fileWriter = (new FileWriterBuilder($this->backend))->build();

		if (NULL !== $debugCallback) {
			$fileWriter->addFileReadListener($debugCallback);
		}

		foreach ($sourceFiles as $path) {
			if (is_file($path)) {
				$fileWriter->readFile($path);
			} else {
				$fileWriter->readDirectory($path);
			}
		}
	}
}