<?php
namespace Helmich\Graphizer\Configuration;

use JsonSchema\RefResolver;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;

class ConfigurationReader {

	/** @var object */
	private $schema;

	/** @var Validator */
	private $validator;

	public function __construct() {
		$retriever     = new UriRetriever();
		$baseDirectory = realpath(__DIR__ . '/../../res');

		$this->schema = $retriever->retrieve('file://' . $baseDirectory . '/ConfigSchema.json');

		$refResolver = new RefResolver($retriever);
		$refResolver->resolve($this->schema, 'file://' . $baseDirectory . '/ConfigSchema.json');

		$this->validator = new Validator();
	}

	public function readConfigurationFromFile($filename) {
		if (!file_exists($filename)) {
			throw new \InvalidArgumentException('File "' . $filename . '" does not exist!');
		}

		$baseDirectory = realpath(dirname($filename));

		$content = file_get_contents($filename);
		$data    = json_decode($content);

		$this->validator->check($data, $this->schema);
		if (!$this->validator->isValid()) {
			$errorText = '';
			foreach ($this->validator->getErrors() as $error) {
				$errorText .= sprintf("[%s] %s\n", $error['property'], $error['message']);
			}
			$errorText = trim($errorText);
			throw new \InvalidArgumentException("File {$filename} contains invalid configuration:\n{$errorText}");
		}

		$config = $this->buildConfigurationFromData($data->config);

		if (isset($data->subConfigs)) {
			foreach ($data->subConfigs as $path => $subConfigData) {
				$subPath = $baseDirectory . '/' . trim($path, '/');
				$config->addConfigurationForSubPath($subPath, $this->buildConfigurationFromData($subConfigData));
			}
		}

		return $config;
	}

	/**
	 * @param object $config
	 * @return Configuration
	 */
	protected function buildConfigurationFromData($config) {
		$package = NULL;
		if (isset($config->package)) {
			$packageData = $config->package;
			$package     = new PackageConfiguration($packageData->name, $packageData->description);
		}

		return new Configuration(
			isset($config->matchPatterns) ? $config->matchPatterns : [],
			isset($config->excludePatterns) ? $config->excludePatterns : [],
			$package
		);
	}
}