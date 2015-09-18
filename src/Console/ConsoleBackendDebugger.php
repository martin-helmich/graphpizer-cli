<?php
/*
 * GraPHPizer source code analytics engine (cli component)
 * Copyright (C) 2015  Martin Helmich <kontakt@martin-helmich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Helmich\Graphizer\Console;

use Helmich\Graphizer\Configuration\ProjectConfiguration;
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

	public function projectUpserted(ProjectConfiguration $project) {
		$this->output->writeln(
			sprintf('Creating/updating project <comment>%s</comment>', $project->getSlug())
		);
	}

	public function nodeCreated($id, array $labels) {
		$this->output->writeln(
			sprintf('Created node <comment>#%s</comment> with labels <comment>%s</comment>', $id, json_encode($labels))
		);
	}

	public function queryExecuted($cypher, array $args) {
		$time = microtime(TRUE) - $this->timer;
		$this->output->writeln(sprintf("Took <info>%fms</info>", $time * 1000));
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