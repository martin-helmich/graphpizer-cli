<?php

/*
 * GraPHPizer - Store PHP syntax trees in a Neo4j database
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

namespace Helmich\Graphizer\Console\Command\Export;

use Helmich\Graphizer\Exporter\Graph\PlantUmlExporter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportPlantUmlCommand extends AbstractExportCommand {

	protected function configure() {
		parent::configure();
		$this
			->setName('export:plantuml')
			->setDescription('Export into PlantUML format (http://plantuml.sourceforge.net)')
			->addOption('export', 'e', InputOption::VALUE_REQUIRED, 'Export as PNG to this file');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend  = $this->connect($input, $output);
		$exporter = new PlantUmlExporter($backend);

		$dot = $exporter->export($this->buildExportConfiguration($input));
		$output->write($dot);
	}
}