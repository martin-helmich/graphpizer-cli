<?php
namespace Helmich\Graphizer\Console\Command;

use Helmich\Graphizer\Console\ConsoleBackendDebugger;
use Helmich\Graphizer\Persistence\BackendBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command {

	/**
	 * @var BackendBuilder
	 */
	private $backendBuilder;

	public function __construct($name = NULL, BackendBuilder $backendBuilder) {
		parent::__construct($name);
		$this->backendBuilder = $backendBuilder;
	}

	protected function connect(InputInterface $input, OutputInterface $output) {
		$this->backendBuilder
			->setHost($input->getOption('neo-host'))
			->setPort($input->getOption('neo-port'))
			->setUser($input->getOption('neo-user'))
			->setPassword($input->getOption('neo-password'));

		if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
			$this->backendBuilder->setDebugger(new ConsoleBackendDebugger($output));
		}

		return $this->backendBuilder->build();
	}
}