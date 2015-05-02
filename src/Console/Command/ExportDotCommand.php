<?php
namespace Helmich\Graphizer\Console\Command;

use Helmich\Graphizer\Exporter\Graph\DotExporter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportDotCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('export:dot')
			->setDescription('Export into DOT format (http://www.graphviz.org/content/dot-language)');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend = $this->connect($input, $output);
		$exporter = new DotExporter($backend);

		$output->write($exporter->export(FALSE, FALSE, TRUE));
	}
}