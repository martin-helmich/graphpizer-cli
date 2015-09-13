<?php
namespace Helmich\Graphizer\Console\Command;

use Helmich\Graphizer\Console\ConsoleBackendDebugger;
use Helmich\Graphizer\Persistence\Engine\BackendBuilder as EngineBackendBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command {

	/**
	 * @var EngineBackendBuilder
	 */
	private $backendBuilder;

	public function __construct($name = NULL, EngineBackendBuilder $backendBuilder) {
		parent::__construct($name);
		$this->backendBuilder = $backendBuilder;
	}

	protected function connect(InputInterface $input, OutputInterface $output) {
		$this->backendBuilder
			->setHost($input->getOption('graph-host'))
			->setPort($input->getOption('graph-port'));

		if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
			$this->backendBuilder->setHttpDebug(TRUE);
		}

		if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
			$this->backendBuilder->setDebugger(new ConsoleBackendDebugger($output));
		}

		return $this->backendBuilder->build();
	}
}