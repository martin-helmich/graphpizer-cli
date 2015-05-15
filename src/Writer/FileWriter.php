<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Configuration\ImportConfiguration;
use Helmich\Graphizer\Configuration\ImportConfigurationReader;
use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Utility\ObservableTrait;
use PhpParser\Error;
use PhpParser\Parser;

class FileWriter {

	/**
	 * @var NodeWriterInterface
	 */
	private $nodeWriter;

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var Backend
	 */
	private $backend;

	/**
	 * @var ImportConfiguration
	 */
	private $configuration;

	/**
	 * @var ImportConfigurationReader
	 */
	private $configurationReader;

	/** @var FileWriterDispatchingListener */
	private $listener;

	public function __construct(Backend $backend,
		NodeWriterInterface $nodeWriter,
		Parser $parser,
		ImportConfiguration $configuration,
		ImportConfigurationReader $configurationReader
	) {
		$this->nodeWriter          = $nodeWriter;
		$this->parser              = $parser;
		$this->backend             = $backend;
		$this->configuration       = $configuration;
		$this->configurationReader = $configurationReader;
		$this->listener            = new FileWriterDispatchingListener();
	}

	public function addListener(FileWriterListener $listener) {
		$this->listener->register($listener);
	}

	public function readDirectory($directory) {
		$this->readDirectoryRecursive($directory, $this->configuration, $directory);
		$this->listener->onFinish($directory);
	}

	private function readDirectoryRecursive($directory, ImportConfiguration $configuration, $baseDirectory) {
		$configurationFileName = $directory . '/.graphizer.json';
		if (file_exists($configurationFileName)) {
			$this->listener->onConfigApplied($configurationFileName);

			$subConfiguration = $this->configurationReader->readConfigurationFromFile($configurationFileName);
			$configuration    = $configuration->merge($subConfiguration);
		}

		$entries = scandir($directory);
		foreach ($entries as $entry) {
			if ($entry == '..' || $entry == '.') {
				continue;
			}

			$entryPath = $directory . '/' . $entry;
			if (is_dir($entryPath)) {
				if ($configuration->getInterpreter()->isDirectoryEntryMatching($entry)) {
					$this->readDirectoryRecursive($entryPath, $configuration, $baseDirectory);
				}
			} else {
				$this->readFileRecursive($entryPath, $configuration, $baseDirectory);
			}
		}
	}

	public function readFile($filename, $baseDirectory = NULL) {
		$result = $this->readFileRecursive($filename, $this->configuration, $baseDirectory);
		$this->listener->onFinish($filename);
		return $result;
	}

	private function readFileRecursive($filename, ImportConfiguration $configuration, $baseDirectory) {
		if (!$configuration->getInterpreter()->isFileMatching($filename)) {
			$this->listener->onFileSkipped($filename);
			return NULL;
		}

		$time = microtime(TRUE);
		$this->listener->onFileReading($filename);

		$code = file_get_contents($filename);
		try {
			$ast = $this->parser->parse($code);
		} catch (Error $parseError) {
			$this->listener->onFileFailed($filename, $parseError);
			return NULL;
		}

		$relativeFilename =
			$baseDirectory ? ltrim(substr($filename, strlen($baseDirectory)), '/\\') : dirname($filename);

		$collectionNode = $this->nodeWriter->writeNodeCollection($ast);
		$collectionNode->setProperty('filename', $relativeFilename);
		$collectionNode->save();

		$this->backend->labelNode($collectionNode, 'File');
		$this->listener->onFileRead($filename, microtime(TRUE) - $time);

		return $collectionNode;
	}
}