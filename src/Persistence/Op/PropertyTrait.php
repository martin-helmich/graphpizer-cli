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

namespace Helmich\Graphizer\Persistence\Op;

/**
 * Trait containing helper methods for operations that process properties
 *
 * @package    Helmich\Graphizer
 * @subpackage Persistence\Op
 */
trait PropertyTrait {

	/**
	 * @var array
	 */
	protected $properties = [];

	/**
	 * Filters properties for being used in a Cypher query.
	 *
	 * Most importantly, property values *must not* be `null`. See [1] for more
	 * information.
	 *
	 * [1] http://stackoverflow.com/q/30238511/1995300
	 *
	 * @param array $properties
	 * @return array
	 */
	protected function filterProperties(array $properties) {
		$properties = array_filter($properties, function ($value) {
			return $value !== NULL;
		});
		return $properties;
	}

	/**
	 * @return array
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @return void
	 */
	public function setProperty($key, $value) {
		if ($value !== NULL) {
			$this->properties[$key] = $value;
		}
	}

	/**
	 * @param string $name
	 * @param array $values
	 * @return self
	 */
	public function __call($name, $values) {
		$this->properties[$name] = $values[0];
		return $this;
	}
} 