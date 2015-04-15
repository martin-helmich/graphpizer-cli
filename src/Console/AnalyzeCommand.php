<?php
namespace Helmich\Graphizer\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class AnalyzeCommand extends Command {

	protected function configure() {
		$this
			->setName('analyze')
			->setDescription('Analyze a set of files')
			->addOption('neoHost', 'h', InputArgument::OPTIONAL, 'Hostname of Neo4j server', 'localhost')
			->addOption('neoPort', 'P', InputArgument::OPTIONAL, 'Port of Neo4j server', 7474)
			->addOption('neoUser', 'u', InputArgument::OPTIONAL, 'Username for Neo4j server', '')
			->addOption('neoPassword', 'p', InputArgument::OPTIONAL, 'Password for Neo4j server', '')
			->addArgument('dir', InputArgument::IS_ARRAY | InputArgument::REQUIRED);
	}
}