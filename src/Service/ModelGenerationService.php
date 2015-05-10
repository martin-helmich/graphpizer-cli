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

namespace Helmich\Graphizer\Service;

use Helmich\Graphizer\Modeler\ClassModelGenerator;
use Helmich\Graphizer\Modeler\NamespaceResolver;
use Helmich\Graphizer\Modeler\TypeResolver;
use Helmich\Graphizer\Modeler\UsageAnalyzer;
use Helmich\Graphizer\Persistence\Backend;

class ModelGenerationService {

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function generateModel($withUsage = FALSE) {
		$namespaceResolver = new NamespaceResolver($this->backend);
		$namespaceResolver->run();

		$modelGenerator = new ClassModelGenerator($this->backend, $namespaceResolver);
		$modelGenerator->run();

		$typeResolver = new TypeResolver($this->backend);
		$typeResolver->run();

		if ($withUsage) {
			$usageAnalyzer = new UsageAnalyzer($this->backend);
			$usageAnalyzer->run();
		}
	}
}