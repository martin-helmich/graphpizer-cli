<?php
namespace Helmich\Graphizer\Console;

use Helmich\Graphizer\Persistence\DebuggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleBackendDebugger implements DebuggerInterface {

	/**
	 * @var OutputInterface
	 */
	private $output;

	public function __construct(OutputInterface $output) {
		$this->output = $output;
	}

	public function nodeCreated($id, array $labels) {
		$this->output->writeln(
			sprintf('Created node <comment>#%s</comment> with labels <comment>%s</comment>', $id, json_encode($labels))
		);
	}

	public function queryExecuted($cypher, array $args) {
		$this->output->writeln(
			sprintf(
				'Executed Cypher query <comment>%s</comment> with args: <comment>%s</comment>',
				$cypher,
				json_encode($args)
			)
		);
	}
}