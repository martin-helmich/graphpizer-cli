<?php
namespace Helmich\Graphizer\Console\Command;

use Helmich\Graphizer\Exporter\GexfExporter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportGexfCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('export:gexf')
			->setDescription('Export into GEXF format (http://gexf.net/format)')
			->addOption('pretty', NULL, InputOption::VALUE_NONE, 'Pretty-print the generated XML');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend = $this->connect($input, $output);
		$exporter = new GexfExporter($backend);

		$output->write($exporter->export(FALSE, FALSE, TRUE));
	}
}