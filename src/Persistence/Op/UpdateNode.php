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
 * Operation that updates a given node
 *
 * @package    Helmich\Graphizer
 * @subpackage Persistence\Op
 */
class UpdateNode implements Operation {

	use PropertyTrait;

	/**
	 * @var NodeMatcher
	 */
	private $node;

	/**
	 * @param NodeMatcher $node
	 * @param array       $properties
	 */
	public function __construct(NodeMatcher $node, array $properties) {
		$this->node       = $node;
		$this->properties = $properties;
	}

	/**
	 * @return string
	 */
	public function toCypher() {
		$fieldNames = [];
		foreach ($this->properties as $key => $value) {
			$fieldNames[] = sprintf('%s.%s = {%s}', $this->node->getId(), $key, $this->node->getId() . '_' . $key);
		}
		return 'SET ' . implode(', ', $fieldNames);
	}

	/**
	 * @return array
	 */
	public function toJson() {
		throw new \BadMethodCallException('Not supported!');
	}

	/**
	 * @return string
	 */
	public function getArguments() {
		$args = [];

		foreach ($this->properties as $key => $value) {
			$args[$this->node->getId() . '_' . $key] = $value;
		}

		return $args;
	}

	/**
	 * @return NodeMatcher[]
	 */
	public function getRequiredNodes() {
		return [$this->node];
	}

}