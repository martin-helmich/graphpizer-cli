<?php
namespace Helmich\Graphizer\Console\Command;

use Helmich\Graphizer\Persistence\BackendBuilder;
use Symfony\Component\Console\Command\Command;

class CommandBuilder {

	public function __construct(){
	}

	/**
	 * @param string $cls
	 * @param string $name
	 * @return Command
	 */
	public function build($cls, $name = NULL) {
		$backendBuilder = new BackendBuilder();

		$cmd = new $cls($name, $backendBuilder);
		return $cmd;
	}
}