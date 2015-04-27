<?php
namespace Helmich\Graphizer\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputOption;

class Application extends BaseApplication {

	public function getDefinition() {
		$parent = parent::getDefinition();

		$parent->addOption(new InputOption('neo-host', 'H', InputOption::VALUE_REQUIRED, 'Hostname of Neo4j server', 'localhost'));
		$parent->addOption(new InputOption('neo-port', 'P', InputOption::VALUE_REQUIRED, 'Port of Neo4j server', 7474));
		$parent->addOption(new InputOption('neo-user', 'u', InputOption::VALUE_REQUIRED, 'Username for Neo4j server', 'neo4j'));
		$parent->addOption(new InputOption('neo-password', 'p', InputOption::VALUE_REQUIRED, 'Password for Neo4j server', 'martin123'));

		return $parent;
	}
}