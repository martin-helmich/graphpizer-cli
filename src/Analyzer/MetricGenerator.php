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

namespace Helmich\Graphizer\Analyzer;

use Helmich\Graphizer\Analyzer\Metric\CyclomaticComplexity;
use Helmich\Graphizer\Analyzer\Metric\Metric;
use Helmich\Graphizer\Analyzer\Metric\NodeCount;
use Helmich\Graphizer\Persistence\Neo4j\Backend;
use Helmich\Graphizer\Utility\ObservableTrait;

class MetricGenerator {

	use ObservableTrait;

	/** @var Metric[] */
	private $metrics = [];

	static public function create(Backend $backend) {
		$instance = new MetricGenerator();
		$instance->addMetric(new NodeCount($backend));
		$instance->addMetric(new CyclomaticComplexity($backend));

		return $instance;
	}

	public function __construct() {
	}

	public function addMetricEvaluatedListener(callable $listener) {
		$this->addListener('metricEvaluated', $listener);
	}

	public function addMetric(Metric $metric) {
		$this->metrics[] = $metric;
	}

	public function run() {
		foreach ($this->metrics as $metric) {
			$this->notify('metricEvaluated', $metric);
			$metric->evaluate();
		}
	}
}