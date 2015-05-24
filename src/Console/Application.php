<?php
namespace Helmich\Graphizer\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputOption;

class Application extends BaseApplication {

	public function getDefinition() {
		$parent = parent::getDefinition();

		$parent->addOption(new InputOption('graph-host', 'H', InputOption::VALUE_REQUIRED, 'Hostname of GraPHPizer server', 'localhost'));
		$parent->addOption(new InputOption('graph-port', 'P', InputOption::VALUE_REQUIRED, 'Port of GraPHPizer server', 9000));

		return $parent;
	}
}