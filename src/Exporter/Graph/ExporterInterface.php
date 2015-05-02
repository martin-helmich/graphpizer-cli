<?php
namespace Helmich\Graphizer\Exporter\Graph;

interface ExporterInterface {

	public function export($withMethods = FALSE, $withProperties = FALSE, $pretty = FALSE);
}