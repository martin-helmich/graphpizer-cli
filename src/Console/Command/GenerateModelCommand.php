<?php
namespace Helmich\Graphizer\Console\Command;

use Helmich\Graphizer\Service\ModelGenerationService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateModelCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('generate:model')
			->setDescription('Generates a meta model from stored syntax trees')
			->addOption('with-usages', NULL, InputOption::VALUE_NONE, 'Infer class usages')
			->addOption('with-dependencies', NULL, InputOption::VALUE_NONE, 'Analyze package dependencies');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend = $this->connect($input, $output);

		$service = new ModelGenerationService($backend);
		$service->generateModel($input->getOption('with-usage'), $input->getOption('with-dependencies'));
	}
}