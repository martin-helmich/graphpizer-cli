<?php
namespace Helmich\Graphizer\Console\Command;

use Helmich\Graphizer\Service\ImportService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('import')
			->setDescription('Import a set of files as AST')
			->addOption('prune', NULL, InputOption::VALUE_NONE, 'Prune the database before execution')
			->addArgument('dir', InputArgument::IS_ARRAY | InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend = $this->connect($input, $output);

		$debugCallback = NULL;
		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
			$debugCallback = function ($file) use ($output) {
				$output->writeln('Processing file <comment>' . $file . '</comment>');
			};
		}

		$importService = new ImportService($backend);
		$importService->importSourceFiles(
			$input->getArgument('dir'),
			$input->getOption('prune'),
			$debugCallback
		);
	}
}