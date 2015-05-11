<?php
namespace Helmich\Graphizer\Console\Command\Export;

use Helmich\Graphizer\Exporter\Graph\JsonExporter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportJsonCommand extends AbstractExportCommand {

	protected function configure() {
		parent::configure();
		$this
			->setName('export:json')
			->setDescription('Export into JSON format')
			->addOption('pretty', NULL, InputOption::VALUE_NONE, 'Pretty-print the generated JSON');
	}

	protected function buildExportConfiguration(InputInterface $input) {
		$config = parent::buildExportConfiguration($input);
		$config->setPretty($input->getOption('pretty'));
		return $config;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend  = $this->connect($input, $output);
		$exporter = new JsonExporter($backend);

		$output->write($exporter->export($this->buildExportConfiguration($input)));
	}
}