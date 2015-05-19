<?php
namespace Helmich\Graphizer\Service;

use Helmich\Graphizer\Configuration\ImportConfiguration;
use Helmich\Graphizer\Configuration\ConfigurationReader;
use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Writer\FileWriterBuilder;
use Helmich\Graphizer\Writer\FileWriterListener;

class ImportService {

	/**
	 * @var Backend
	 */
	private $backend;

	/**
	 * @var ConfigurationReader
	 */
	private $configurationReader;

	public function __construct(Backend $backend, ConfigurationReader $configurationReader) {
		$this->backend             = $backend;
		$this->configurationReader = $configurationReader;
	}

	public function importSourceFiles(
		array $sourceFiles,
		$pruneFirst = FALSE,
		ImportConfiguration $configuration = NULL,
		FileWriterListener $listener = NULL
	) {
		if ($pruneFirst) {
			echo "Wiping... ";
			$this->backend->wipe();
			echo "Done\n";
		}

		if (NULL === $configuration) {
			$configuration = new ImportConfiguration();
		}

		$fileWriter = (new FileWriterBuilder($this->backend))
			->setConfiguration($configuration)
			->setConfigurationReader($this->configurationReader)
			->build();

		if (NULL !== $listener) {
			$fileWriter->addListener($listener);
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