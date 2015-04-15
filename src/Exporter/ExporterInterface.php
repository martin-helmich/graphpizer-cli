<?php
namespace Helmich\Graphizer\Exporter;

interface ExporterInterface {

	public function export($withMethods = FALSE, $withProperties = FALSE, $pretty = FALSE);
}