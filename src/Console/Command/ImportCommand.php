<?php
namespace Helmich\Graphizer\Console\Command;

use Helmich\Graphizer\Writer\FileWriterBuilder;
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

		if ($input->getOption('prune')) {
			$output->writeln('Pruning database.');
			$backend->wipe();
		}

		$paths      = $input->getArgument('dir');
		$fileWriter = (new FileWriterBuilder($backend))->build();

		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
			$fileWriter->setDebugListener(function ($file) use ($output) {
				$output->writeln('Processing file <comment>' . $file . '</comment>');
			});
		}

		foreach ($paths as $path) {
			if (is_file($path)) {
				$fileWriter->readFile($path);
			} else {
				$fileWriter->readDirectory($path);
			}
		}
	}
}