<?php
namespace Helmich\Graphizer\Console\Command\Export;

use Helmich\Graphizer\Exporter\Graph\D3Exporter;
use Helmich\Graphizer\Exporter\Graph\JsonExporter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportD3jsCommand extends AbstractExportCommand {

	protected function configure() {
		parent::configure();
		$this
			->setName('export:d3')
			->setDescription('Export into a D3.js document');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend      = $this->connect($input, $output);
		$jsonExporter = new JsonExporter($backend);
		$d3Exporter = new D3Exporter($jsonExporter);

		$output->write($d3Exporter->export($this->buildExportConfiguration($input)));
	}
}