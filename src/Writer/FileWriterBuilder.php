<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Configuration\ImportConfiguration;
use Helmich\Graphizer\Configuration\ConfigurationReader;
use Helmich\Graphizer\Persistence\Backend;
use PhpParser\Lexer;
use PhpParser\Parser;

class FileWriterBuilder {

	/**
	 * @var Backend
	 */
	private $backend;

	/**
	 * @var ImportConfiguration
	 */
	private $configuration;

	/** @var ConfigurationReader */
	private $configurationReader;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	/**
	 * @param ImportConfiguration $configuration
	 * @return self
	 */
	public function setConfiguration(ImportConfiguration $configuration) {
		$this->configuration = $configuration;
		return $this;
	}

	/**
	 * @param ConfigurationReader $configurationReader
	 * @return $this
	 */
	public function setConfigurationReader(ConfigurationReader $configurationReader) {
		$this->configurationReader = $configurationReader;
		return $this;
	}

	public function build() {
		if (NULL === $this->configuration) {
			$this->configuration = new ImportConfiguration();
		}

		if (NULL == $this->configurationReader) {
			$this->configurationReader = new ConfigurationReader();
		}

		return new FileWriter(
			$this->backend,
			(new NodeWriterBuilder($this->backend))->build(),
			new Parser(new Lexer()),
			$this->configuration,
			$this->configurationReader
		);
	}
}