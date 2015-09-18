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

namespace Helmich\Graphizer\Persistence\Engine;

use Helmich\Graphizer\Configuration\ProjectConfiguration;
use Helmich\Graphizer\Persistence\BulkOperation;

class JsonBulkOperation extends BulkOperation {

	/** @var Backend */
	private $backend;

	/** @var ProjectConfiguration */
	private $project;

	public function __construct(ProjectConfiguration $project, Backend $backend) {
		$this->backend = $backend;
		$this->project = $project;
	}

	/**
	 * @return \Traversable
	 */
	public function evaluate() {
		$result = [
			'nodes' => [],
			'relationships' => []
		];

		foreach ($this->operations as $operation) {
			$enc = $operation->toJson();

			foreach ($enc as $key => $value) {
				if (!array_key_exists($key, $result)) {
					$result[$key] = $value;
				} else {
					$result[$key] = array_merge($result[$key], $value);
				}
			}
		}

		$this->backend->import($this->project, $result);
	}

}