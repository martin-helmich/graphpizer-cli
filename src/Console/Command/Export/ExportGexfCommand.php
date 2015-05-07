<?php
namespace Helmich\Graphizer\Console\Command\Export;

use Helmich\Graphizer\Exporter\Graph\GexfExporter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportGexfCommand extends AbstractExportCommand {

	protected function configure() {
		parent::configure();
		$this
			->setName('export:gexf')
			->setDescription('Export into GEXF format (http://gexf.net/format)')
			->addOption('pretty', NULL, InputOption::VALUE_NONE, 'Pretty-print the generated XML');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend  = $this->connect($input, $output);
		$exporter = new GexfExporter($backend);

		$output->write($exporter->export($this->buildExportConfiguration($input)));
	}
}