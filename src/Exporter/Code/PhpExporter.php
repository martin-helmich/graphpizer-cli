<?php

/*
 * GraPHPizer - Store PHP syntax trees in a Neo4j database
 * Copyright (C) 2015  Martin Helmich <kontakt@martin-helmich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Helmich\Graphizer\Exporter\Code;

use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Reader\NodeReaderInterface;
use Helmich\Graphizer\Utility\ObservableTrait;
use PhpParser\PrettyPrinterAbstract;

/**
 * Writes syntax trees from the node database back into files
 *
 * @package Helmich\Graphizer
 * @subpackage Exporter\Code
 */
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