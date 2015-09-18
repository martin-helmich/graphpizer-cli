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

use Helmich\Graphizer\Configuration\Configuration;
use Helmich\Graphizer\Configuration\ConfigurationReader;
use Helmich\Graphizer\Configuration\ProjectConfiguration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PruneCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('import:prune')
			->setDescription('Clear the database of everything. Really, everything!')
			->addArgument('project', InputArgument::OPTIONAL, 'The project to wipe', 'default');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$configurationFileName = getcwd() . '/.graphpizer.json';
		if (file_exists($configurationFileName)) {
			$configurationReader = new ConfigurationReader();
			$configuration = $configurationReader->readConfigurationFromFile($configurationFileName);
		} else {
			$project = new ProjectConfiguration($input->getArgument('project'), 'Project');
			$configuration = new Configuration([], [], NULL, $project);
		}

		$backend = $this->connect($input, $output);
		$backend->wipe($configuration->getProject());

		$output->writeln("Deleted <comment>everything</comment>. You happy now?");
	}
}