<?php
namespace Helmich\Graphizer\Console\Command;

use Helmich\Graphizer\Configuration\ImportConfiguration;
use Helmich\Graphizer\Configuration\ImportConfigurationReader;
use Helmich\Graphizer\Service\ImportService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('import:ast')
			->setDescription('Import a set of files as AST')
			->addOption('prune', NULL, InputOption::VALUE_NONE, 'Prune the database before execution')
			->addOption(
				'exclude',
				'e',
				InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
				'Exclude directories'
			)
			->addArgument('dir', InputArgument::IS_ARRAY | InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend = $this->connect($input, $output);

		$count         = 0;
		$debugCallback = function ($file) use (&$count) {
			$count++;
		};

		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
			$debugCallback = function ($file) use ($output, &$count) {
				$output->writeln('Processing file <comment>' . $file . '</comment>');
				$count++;
			};
		}

		$configurationReader  = new ImportConfigurationReader();
		$defaultConfiguration =
			$configurationReader->readConfigurationFromFile(__DIR__ . '/../../../res/DefaultImportConfiguration.json');

		$userConfiguration = new ImportConfiguration(
			[],
			$input->getOption('exclude')
		);

		$importService = new ImportService($backend, $configurationReader);
		$importService->importSourceFiles(
			$input->getArgument('dir'),
			$input->getOption('prune'),
			$defaultConfiguration->merge($userConfiguration),
			$debugCallback
		);

		$output->writeln('Processed <comment>' . $count . '</comment> files.');
	}
}