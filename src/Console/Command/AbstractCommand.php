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