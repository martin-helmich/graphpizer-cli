<?php
namespace Helmich\Graphizer\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnalyzeCommand extends Command {

	protected function configure() {
		$this
			->setName('analyze')
			->setDescription('Analyze a set of files')
			->addOption('neoHost', 'H', InputArgument::OPTIONAL, 'Hostname of Neo4j server', 'localhost')
			->addOption('neoPort', 'P', InputArgument::OPTIONAL, 'Port of Neo4j server', 7474)
			->addOption('neoUser', 'u', InputArgument::OPTIONAL, 'Username for Neo4j server', '')
			->addOption('neoPassword', 'p', InputArgument::OPTIONAL, 'Password for Neo4j server', '')
			->addOption('prune', NULL, InputArgument::OPTIONAL, 'Prune the database before execution')
			->addArgument('dir', InputArgument::IS_ARRAY | InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$paths = $input->getArgument('dir');
	}
}