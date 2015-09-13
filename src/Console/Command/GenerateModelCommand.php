<?php
namespace Helmich\Graphizer\Console\Command;

use Helmich\Graphizer\Configuration\Configuration;
use Helmich\Graphizer\Configuration\ConfigurationReader;
use Helmich\Graphizer\Configuration\ProjectConfiguration;
use Helmich\Graphizer\Service\ModelGenerationService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateModelCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('generate:model')
			->setDescription('Generates a meta model from stored syntax trees')
			->addArgument('project', InputArgument::OPTIONAL, 'The project to import', 'default');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$configurationFileName = getcwd() . '/.graphpizer.json';
		if (file_exists($configurationFileName)) {
			$configurationReader = new ConfigurationReader();
			$configuration = $configurationReader->readConfigurationFromFile($configurationFileName);
		} else {
			$project = new ProjectConfiguration($input->getArgument('project'), 'Project');
			$configuration = new Configuration([], [], NULL, $project);
		}

		$backend = $this->connect($input, $output);

		$service = new ModelGenerationService($backend);
		$service->generateModel($configuration->getProject());
	}
}