<?php
namespace Helmich\Graphizer\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PruneCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('import:prune')
			->setDescription('Clear the database of everything. Really, everything!');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend = $this->connect($input, $output);
		$backend->wipe();

		$output->writeln("Deleted <comment>everything</comment>. You happy now?");
	}
}