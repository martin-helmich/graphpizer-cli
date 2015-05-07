<?php
namespace Helmich\Graphizer\Console\Command\Export;

use Helmich\Graphizer\Console\Command\AbstractCommand;
use Helmich\Graphizer\Exporter\Code\PhpExporter;
use Helmich\Graphizer\Reader\NodeReaderBuilder;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCodeCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('export:php')
			->setDescription('Export back into PHP source files')
			->addArgument('target', InputArgument::REQUIRED, 'Target directory');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend   = $this->connect($input, $output);
		$targetDir = $input->getArgument('target');

		$nodeReader = (new NodeReaderBuilder($backend))->build();

		$count    = 0;
		$exporter = new PhpExporter($nodeReader, $backend, new Standard());
		$exporter->addFileWrittenListener(
			function () use (&$count) {
				$count++;
			}
		);

		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
			$exporter->addFileWrittenListener(
				function ($filename) use ($output) {
					$output->writeln('Writing file <comment>' . $filename . '</comment>');
				}
			);
		}

		$exporter->export($targetDir);

		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
			$output->writeln('Wrote <comment>' . $count . '</comment> files');
		}
	}
}