<?php
namespace Helmich\Graphizer\Console\Command;

use Helmich\Graphizer\Configuration\ProjectConfiguration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PruneCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('import:prune')
			->setDescription('Clear the database of everything. Really, everything!')
			->addArgument('project', InputArgument::OPTIONAL, 'The project to wipe', 'default');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$project = new ProjectConfiguration($input->getArgument('project'), $input->getArgument('project'));

		$backend = $this->connect($input, $output);
		$backend->wipe($project);

		$output->writeln("Deleted <comment>everything</comment>. You happy now?");
	}
}