<?php
namespace Helmich\Graphizer\Configuration;

use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;

class ImportConfigurationReader {

	/** @var object */
	private $schema;

	/** @var Validator */
	private $validator;

	public function __construct() {
		$retriever = new UriRetriever();

		$this->schema    = $retriever->retrieve('file://' . realpath(__DIR__ . '/../../res/ConfigSchema.json'));
		$this->validator = new Validator();
	}

	public function readConfigurationFromFile($filename) {
		if (!file_exists($filename)) {
			throw new \InvalidArgumentException('File "' . $filename . '" does not exist!');
		}

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

		return new ImportConfiguration(
			$data->matchPatterns,
			$data->excludePatterns
		);
	}
}