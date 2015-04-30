<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Utility\ObservableTrait;
use PhpParser\Parser;

class FileWriter {

	use ObservableTrait;

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

	public function __construct(Backend $backend, NodeWriterInterface $nodeWriter, Parser $parser) {
		$this->nodeWriter    = $nodeWriter;
		$this->parser        = $parser;
		$this->backend       = $backend;
	}

	public function addFileReadListener(callable $debugListener) {
		$this->addListener('fileRead', $debugListener);
	}

	public function readDirectory($directory) {
		$dirIterator = new \RecursiveDirectoryIterator($directory);
		$iteratorIterator = new \RecursiveIteratorIterator($dirIterator);
		$regexIterator = new \RegexIterator($iteratorIterator, '/^(.*)\.php[345]?$/', \RecursiveRegexIterator::GET_MATCH);

		foreach($regexIterator as $fileInfo) {
			$this->readFile($fileInfo[0], $directory);
		}
	}

	public function readFile($filename, $baseDirectory) {
		$this->notify('fileRead', $filename);

		$code = file_get_contents($filename);
		$ast  = $this->parser->parse($code);

		$relativeFilename = ltrim(substr($filename, strlen($baseDirectory)), '/\\');

		$collectionNode = $this->nodeWriter->writeNodeCollection($ast);
		$collectionNode->setProperty('filename', $relativeFilename);
		$collectionNode->save();

		$this->backend->labelNode($collectionNode, 'File');

		return $collectionNode;
	}
}