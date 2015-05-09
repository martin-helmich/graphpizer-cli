<?php
namespace Helmich\Graphizer\Service;

use Helmich\Graphizer\Configuration\ImportConfiguration;
use Helmich\Graphizer\Configuration\ImportConfigurationReader;
use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Writer\FileWriterBuilder;

class ImportService {

	/**
	 * @var Backend
	 */
	private $backend;

	/**
	 * @var ImportConfigurationReader
	 */
	private $configurationReader;

	public function __construct(Backend $backend, ImportConfigurationReader $configurationReader) {
		$this->backend             = $backend;
		$this->configurationReader = $configurationReader;
	}

	public function importSourceFiles(array $sourceFiles, $pruneFirst = FALSE, ImportConfiguration $configuration = NULL, callable $debugCallback = NULL) {
		if ($pruneFirst) {
			$this->backend->wipe();
		}

		if (NULL === $configuration) {
			$configuration = new ImportConfiguration();
		}

		$fileWriter = (new FileWriterBuilder($this->backend))
			->setConfiguration($configuration)
			->setConfigurationReader($this->configurationReader)
			->build();

		if (NULL !== $debugCallback) {
			$fileWriter->addFileReadListener($debugCallback);
		}

		foreach ($sourceFiles as $path) {
			if (is_file($path)) {
				$fileWriter->readFile($path, dirname($path));
			} else {
				$fileWriter->readDirectory($path);
			}
		}
	}
}