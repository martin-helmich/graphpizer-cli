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

namespace Helmich\Graphizer\Console\Command;

use Helmich\Graphizer\Analyzer\Metric\Metric;
use Helmich\Graphizer\Analyzer\MetricGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateMetricCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('generate:metric')
			->setDescription('Generates code metrics from the imported source code');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend = $this->connect($input, $output);

		$listener = function(Metric $metric) use ($output) {
			$output->writeln('Evaluating metric <comment>' . $metric->getName() . '</comment>.');
		};

		$generator = MetricGenerator::create($backend);
		$generator->addMetricEvaluatedListener($listener);
		$generator->run();
	}
}