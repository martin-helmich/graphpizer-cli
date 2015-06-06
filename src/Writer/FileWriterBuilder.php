<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Configuration\Configuration;
use Helmich\Graphizer\Configuration\ConfigurationReader;
use Helmich\Graphizer\Configuration\ImportConfiguration;
use Helmich\Graphizer\Parser\CachingDecorator;
use Helmich\Graphizer\Parser\FileParser;
use Helmich\Graphizer\Persistence\BackendInterface;
use PhpParser\Lexer;
use PhpParser\Parser;

class FileWriterBuilder {

	/** @var BackendInterface */
	private $backend;

	/**
	 * @var ImportConfiguration
	 */
	private $configuration;

	/** @var ConfigurationReader */
	private $configurationReader;

	public function __construct(BackendInterface $backend) {
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
			$this->configuration = new Configuration();
		}

		if (NULL == $this->configurationReader) {
			$this->configurationReader = new ConfigurationReader();
		}

		$parser = new Parser(new Lexer());
		$fileParser = new CachingDecorator(new FileParser($parser), getcwd() . '/.graphizer-cache');

		return new FileWriter(
			$this->backend,
			(new NodeWriterBuilder())->build(),
			$fileParser,
			$this->configuration,
			$this->configurationReader
		);
	}
}