<?php
namespace Helmich\Graphizer\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateModelCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('generate:model')
			->setDescription('Generates a meta model from stored syntax trees');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend = $this->connect($input, $output);

		if ($input->getOption('prune')) {
			$output->writeln('Pruning database.');
			$backend->wipe();
		}

	}
}