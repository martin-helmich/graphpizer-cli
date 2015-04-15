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

	public function __construct(Backend $backend, NodeWriterInterface $nodeWriter, Parser $parser) {
		$this->nodeWriter = $nodeWriter;
		$this->parser     = $parser;
		$this->backend    = $backend;
	}

	public function readFile($filename) {
		$code = file_get_contents($filename);
		$ast  = $this->parser->parse($code);

		$collectionNode = $this->nodeWriter->writeNodeCollection($ast);
		$collectionNode->setProperty('filename', $filename);
		$collectionNode->save();

		$this->backend->labelNode($collectionNode, 'File');

		return $collectionNode;
	}
}