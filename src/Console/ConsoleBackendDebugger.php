<?php
namespace Helmich\Graphizer\Console;

use Helmich\Graphizer\Persistence\DebuggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleBackendDebugger implements DebuggerInterface {

	/**
	 * @var OutputInterface
	 */
	private $output;

	private $timer;

	public function __construct(OutputInterface $output) {
		$this->output = $output;
	}

	public function nodeCreated($id, array $labels) {
		$this->output->writeln(
			sprintf('Created node <comment>#%s</comment> with labels <comment>%s</comment>', $id, json_encode($labels))
		);
	}

	public function queryExecuted($cypher, array $args) {
		$time = microtime(TRUE) - $this->timer;
		$this->output->writeln(sprintf("Took <info>%fms</info>", $time * 1000));
//		$this->output->writeln(
//			sprintf(
//				'Executed Cypher query <comment>%s</comment> with args: <comment>%s</comment>',
//				$cypher,
//				json_encode($args)
//			)
//		);
	}

	public function queryExecuting($cypher, array $args) {
		$this->output->write(
			sprintf(
				'Executing Cypher query <comment>%s</comment> with args <comment>%s</comment>... ',
				$this->normalizeCypher($cypher),
				json_encode($args)
			)
		);
		$this->timer = microtime(TRUE);
	}

	private function normalizeCypher($cypher) {
		$cypher = str_replace("\n", "", $cypher);
		$cypher = preg_replace(',\s+,', ' ', $cypher);
		return $cypher;
	}
}