<?php
namespace Helmich\Graphizer\Console\Command;

use Helmich\Graphizer\Configuration\Configuration;
use Helmich\Graphizer\Configuration\ImportConfiguration;
use Helmich\Graphizer\Configuration\ConfigurationReader;
use Helmich\Graphizer\Console\Listener\NormalFileWriterListener;
use Helmich\Graphizer\Console\Listener\QuietFileWriterListener;
use Helmich\Graphizer\Console\Listener\VerboseFileWriterListener;
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
		$backend  = $this->connect($input, $output);
		$listener = $this->buildListenerByVerbosity($output);

		$configurationReader  = new ConfigurationReader();
		$defaultConfiguration =
			$configurationReader->readConfigurationFromFile(__DIR__ . '/../../../res/DefaultImportConfiguration.json');

		$userConfiguration = new Configuration([], $input->getOption('exclude'));

		$importService = new ImportService($backend, $configurationReader);
		$importService->importSourceFiles(
			$input->getArgument('dir'),
			$input->getOption('prune'),
			$defaultConfiguration->merge($userConfiguration),
			$listener
		);
	}

	/**
	 * @param OutputInterface $output
	 * @return NormalFileWriterListener|QuietFileWriterListener|VerboseFileWriterListener
	 */
	protected function buildListenerByVerbosity(OutputInterface $output) {
		switch ($output->getVerbosity()) {
			case OutputInterface::VERBOSITY_QUIET:
				$listener = new QuietFileWriterListener($output, $this->getHelper('formatter'));
				return $listener;
			case OutputInterface::VERBOSITY_NORMAL:
				$listener = new NormalFileWriterListener($output, $this->getHelper('formatter'));
				return $listener;
			default:
				$listener = new VerboseFileWriterListener($output, $this->getHelper('formatter'));
				return $listener;
		}
	}
}