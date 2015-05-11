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

use Helmich\Graphizer\Console\Command\AbstractCommand;
use Helmich\Graphizer\Exporter\Graph\ExportConfiguration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Abstract base class for commands that export the model as graph.
 *
 * @package Helmich\Graphizer
 * @subpackage Console\Command\Export
 */
abstract class AbstractExportCommand extends AbstractCommand {

	protected function configure() {
		$this
			->addOption('with-usages', NULL, InputOption::VALUE_NONE, "Add use relations into the graph")
			->addOption('with-properties', NULL, InputOption::VALUE_NONE, "Add class properties into the graph")
			->addOption('with-methods', NULL, InputOption::VALUE_NONE, "Add class methods to the graph");
	}

	protected function buildExportConfiguration(InputInterface $input) {
		return (new ExportConfiguration())
			->setWithMethods($input->getOption('with-methods'))
			->setWithUsages($input->getOption('with-usages'))
			->setWithProperties($input->getOption('with-properties'));
	}
}