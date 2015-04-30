<?php
namespace Helmich\Graphizer\Exporter\Code;

use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Reader\NodeReaderInterface;
use PhpParser\PrettyPrinterAbstract;

class PhpExporter {

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

	/**
	 * @var callable[]
	 */
	private $listeners = [];

	public function __construct(NodeReaderInterface $nodeReader, Backend $backend, PrettyPrinterAbstract $printer) {
		$this->nodeReader = $nodeReader;
		$this->backend    = $backend;
		$this->printer    = $printer;
	}

	public function addExportFileListener(callable $listener) {
		$this->listeners[] = $listener;
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

			$this->notify($filename);
		}
	}

	private function notify($filename) {
		foreach ($this->listeners as $listener) {
			call_user_func($listener, $filename);
		}
	}
}