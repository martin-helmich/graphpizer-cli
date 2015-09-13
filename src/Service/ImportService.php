<?php
namespace Helmich\Graphizer\Service;

use Helmich\Graphizer\Configuration\Configuration;
use Helmich\Graphizer\Configuration\ConfigurationReader;
use Helmich\Graphizer\Configuration\ImportConfiguration;
use Helmich\Graphizer\Persistence\BackendInterface;
use Helmich\Graphizer\Writer\FileWriterBuilder;
use Helmich\Graphizer\Writer\FileWriterListener;

class ImportService {

	/**
	 * @var BackendInterface
	 */
	private $backend;

	/**
	 * @var ConfigurationReader
	 */
	private $configurationReader;

	public function __construct(BackendInterface $backend, ConfigurationReader $configurationReader) {
		$this->backend             = $backend;
		$this->configurationReader = $configurationReader;
	}

	public function importSourceFiles(
		array $sourceFiles,
		$pruneFirst = FALSE,
		Configuration $configuration = NULL,
		FileWriterListener $listener = NULL
	) {
		if (NULL === $configuration) {
			$configuration = new Configuration();
		}

		$rootConfigurations = $this->getRootConfigurations($sourceFiles, $configuration);

		if ($pruneFirst) {
			foreach ($rootConfigurations as $rootConfiguration) {
				echo "Wiping project {$rootConfiguration->getProject()->getSlug()}...";
				$this->backend->wipe($rootConfiguration->getProject());
				echo "Done";
			}
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

	/**
	 * Builds a set of configurations for each specified import source
	 *
	 * @param array         $sourceFiles       The list of import sources (files and/or directories)
	 * @param Configuration $baseConfiguration The base configuration. Each new configuration will be merged with this object
	 * @return ImportConfiguration[]           The created configuration objects
	 */
	private function getRootConfigurations(array $sourceFiles, Configuration $baseConfiguration) {
		$configurations = [];

		foreach ($sourceFiles as $sourceFile) {
			$configurationFileName = $sourceFile . '/.graphpizer.json';
			if (!file_exists($configurationFileName)) {
				continue;
			}

			$configuration = $this->configurationReader->readConfigurationFromFile($configurationFileName);
			$configuration = $baseConfiguration->merge($configuration);

			$configurations[] = $configuration;
		}

		return $configurations;
	}
}