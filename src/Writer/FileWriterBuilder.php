<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Configuration\ImportConfiguration;
use Helmich\Graphizer\Configuration\ImportConfigurationReader;
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

	/** @var ImportConfigurationReader */
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
	 * @param ImportConfigurationReader $configurationReader
	 * @return $this
	 */
	public function setConfigurationReader(ImportConfigurationReader $configurationReader) {
		$this->configurationReader = $configurationReader;
		return $this;
	}

	public function build() {
		if (NULL === $this->configuration) {
			$this->configuration = new ImportConfiguration();
		}

		if (NULL == $this->configurationReader) {
			$this->configurationReader = new ImportConfigurationReader();
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