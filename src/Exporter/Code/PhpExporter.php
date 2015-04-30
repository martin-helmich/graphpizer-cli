<?php
namespace Helmich\Graphizer\Exporter\Code;

use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Reader\NodeReaderInterface;
use Helmich\Graphizer\Utility\ObservableTrait;
use PhpParser\PrettyPrinterAbstract;

class PhpExporter {

	use ObservableTrait;

	/**
	 * @var NodeReaderInterface
	 */
	private $nodeReader;

	/**
	 * @var Backend
	 */
	private $backend;

	/**
	 * @var PrettyPrinterAbstract
	 */
	private $printer;

	public function __construct(NodeReaderInterface $nodeReader, Backend $backend, PrettyPrinterAbstract $printer) {
		$this->nodeReader = $nodeReader;
		$this->backend    = $backend;
		$this->printer    = $printer;
	}

	public function addFileWrittenListener(callable $listener) {
		$this->addListener('fileWritten', $listener);
	}

	public function export($targetDirectory) {
		$q = $this->backend->createQuery('MATCH (c:Collection) WHERE c.fileRoot = true RETURN c', 'c');
		foreach ($q->execute() as $fileCollection) {
			$ast = $this->nodeReader->readNode($fileCollection);
			$out = $this->printer->prettyPrintFile($ast);

			$filename = $targetDirectory . '/' . $fileCollection->getProperty('filename');
			$dirname  = dirname($filename);

			mkdir($dirname, 0777, TRUE);
			file_put_contents($filename, $out);

			$this->notify('fileWritten', $filename);
		}
	}
}