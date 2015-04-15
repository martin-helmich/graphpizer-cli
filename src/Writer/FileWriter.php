<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Persistence\Backend;
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
	 * @var callable
	 */
	private $debugListener;

	public function __construct(Backend $backend, NodeWriterInterface $nodeWriter, Parser $parser) {
		$this->nodeWriter    = $nodeWriter;
		$this->parser        = $parser;
		$this->backend       = $backend;
		$this->debugListener = function ($file) {
		};
	}

	public function setDebugListener(callable $debugListener) {
		$this->debugListener = $debugListener;
	}

	public function readDirectory($directory) {
		$dirIterator = new \RecursiveDirectoryIterator($directory);
		$iteratorIterator = new \RecursiveIteratorIterator($dirIterator);
		$regexIterator = new \RegexIterator($iteratorIterator, '/\.php[345]?$/', \RecursiveRegexIterator::GET_MATCH);

		foreach($regexIterator as $fileInfo) {
			$this->readFile($fileInfo[0]);
		}
	}

	public function readFile($filename) {
		call_user_func($this->debugListener, $filename);

		$code = file_get_contents($filename);
		$ast  = $this->parser->parse($code);

		$collectionNode = $this->nodeWriter->writeNodeCollection($ast);
		$collectionNode->setProperty('filename', $filename);
		$collectionNode->save();

		$this->backend->labelNode($collectionNode, 'File');

		return $collectionNode;
	}
}