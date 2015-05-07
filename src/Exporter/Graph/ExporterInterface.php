<?php
namespace Helmich\Graphizer\Exporter\Graph;

interface ExporterInterface {

	public function export(ExportConfiguration $configuration);
}