<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Configuration\ConfigurationReader;
use Helmich\Graphizer\Configuration\ImportConfiguration;
use Helmich\Graphizer\Configuration\PackageConfiguration;
use Helmich\Graphizer\Persistence\BackendInterface;
use Helmich\Graphizer\Persistence\Engine\JsonBulkOperation;
use Helmich\Graphizer\Persistence\Op\MergeNode;
use PhpParser\Error;
use PhpParser\Parser;

class FileWriter {

	/** @var NodeWriterInterface */
	private $nodeWriter;

	/** @var Parser */
	private $parser;

	/** @var BackendInterface */
	private $backend;

	/** @var ImportConfiguration */
	private $configuration;

	/** @var ConfigurationReader */
	private $configurationReader;

	/** @var FileWriterDispatchingListener */
	private $listener;

	public function __construct(
		BackendInterface $backend,
		NodeWriterInterface $nodeWriter,
		Parser $parser,
		ImportConfiguration $configuration,
		ConfigurationReader $configurationReader
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
		$directory = realpath($directory);

		$this->readDirectoryRecursive($directory, $this->configuration, $directory);
		$this->listener->onFinish($directory);
	}

	private function readDirectoryRecursive($directory,
		ImportConfiguration $configuration,
		$baseDirectory,
		PackageConfiguration $package = NULL
	) {
		if ($configuration->hasConfigurationForSubDirectory($directory)) {
			$configuration = $configuration->merge($configuration->getConfigurationForSubDirectory($directory));
		}

		$configurationFileName = $directory . '/.graphizer.json';
		if (file_exists($configurationFileName)) {
			$this->listener->onConfigApplied($configurationFileName);

			$subConfiguration = $this->configurationReader->readConfigurationFromFile($configurationFileName);
			$configuration    = $configuration->merge($subConfiguration);
		}

		if ($configuration->hasExplicitPackageConfigured()) {
			$package = $configuration->getPackage();
		} else {
			$discoveredPackage = $this->autoDiscoverPackage($directory);
			if ($discoveredPackage !== NULL) {
				$package = $discoveredPackage;
			}
		}

		$entries = scandir($directory);
		foreach ($entries as $entry) {
			if ($entry[0] == '.') {
				continue;
			}

			$entryPath = $directory . '/' . $entry;
			if (is_dir($entryPath)) {
				if ($configuration->getInterpreter()->isDirectoryEntryMatching($entry)) {
					$this->readDirectoryRecursive($entryPath, $configuration, $baseDirectory, $package);
				}
			} else {
				$this->readFileRecursive($entryPath, $configuration, $baseDirectory, $package);
			}
		}
	}

	public function readFile($filename, $baseDirectory = NULL) {
		$result = $this->readFileRecursive($filename, $this->configuration, $baseDirectory);
		$this->listener->onFinish($filename);
		return $result;
	}

	private function readFileRecursive(
		$filename,
		ImportConfiguration $configuration,
		$baseDirectory,
		PackageConfiguration $package = NULL
	) {
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

		$bulk = $this->backend->createBulkOperation();

		$collectionNode = $this->nodeWriter->writeNodeCollection($ast, $bulk);
		$collectionNode->filename($relativeFilename);
//		$collectionNode->save();

		if ($package) {
			/** @var MergeNode $mergePackage */
			$mergePackage = (new MergeNode('Package'))
				->name($package->getName())
				->description($package->getDescription());

			$bulk->push($mergePackage);
			$bulk->push($mergePackage->relate('CONTAINS_FILE', $collectionNode));
//			$cypher = 'MATCH (c:Collection) WHERE id(c)={root}
//			           MERGE (p:Package {name: {pkg}.name, description: {pkg}.description})
//			           MERGE (p)-[:CONTAINS]->(c)';
//			$this->backend->createQuery($cypher)->execute(['root' => $collectionNode, 'pkg' => ['name' => $package->getName(), 'description' => $package->getDescription()]]);
		}

//		$this->backend->labelNode($collectionNode, 'File');
		$collectionNode->addLabel('File');
		$bulk->evaluate();

		$this->listener->onFileRead($filename, (microtime(TRUE) - $time) * 1000);

		return $collectionNode;
	}

	private function autoDiscoverPackage($directory) {
		if (file_exists($directory . '/composer.json')) {
			$data = json_decode($directory . '/composer.json');
			return new PackageConfiguration(
				$data->name,
				isset($data->description) ? $data->description : ''
			);
		} else if (file_exists($directory . '/ext_emconf.php')) {
			$_EXTKEY = dirname($directory);
			$EM_CONF = [];

			require($directory . '/ext_emconf.php');

			return new PackageConfiguration(
				$_EXTKEY,
				$EM_CONF[$_EXTKEY]['title']
			);
		}
		return NULL;
	}
}