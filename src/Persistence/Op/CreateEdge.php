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
 * Creates a new edge between two nodes.
 *
 * @package Helmich\Graphizer
 * @subpackage Persistence\Op
 */
class CreateEdge implements Operation {

	use PropertyTrait;

	/** @var string */
	private $id;

	/** @var NodeMatcher */
	private $start;

	/** @var NodeMatcher */
	private $end;

	/** @var string */
	private $type;

	/**
	 * @param NodeMatcher $start
	 * @param NodeMatcher $end
	 * @param string      $type
	 * @param array       $properties
	 */
	public function __construct(NodeMatcher $start, NodeMatcher $end, $type, array $properties = []) {
		$this->id         = uniqid('rel');
		$this->start      = $start;
		$this->end        = $end;
		$this->type       = $type;
		$this->properties = $this->filterProperties($properties);
	}

	/**
	 * @return NodeMatcher
	 */
	public function getStart() {
		return $this->start;
	}

	/**
	 * @return NodeMatcher
	 */
	public function getEnd() {
		return $this->end;
	}

	/**
	 * @return NodeMatcher[]
	 */
	public function getRequiredNodes() {
		return [$this->start, $this->end];
	}


	/**
	 * @return string
	 */
	public function toCypher() {
		if (count($this->properties) > 0) {
			return sprintf(
				'CREATE (%s)-[%s:%s {prop_%s}]->(%s)',
				$this->start->getId(),
				$this->id,
				$this->type,
				$this->id,
				$this->end->getId()
			);
		} else {
			return sprintf(
				'CREATE (%s)-[%s:%s]->(%s)',
				$this->start->getId(),
				$this->id,
				$this->type,
				$this->end->getId()
			);
		}
	}

	/**
	 * @return string
	 */
	public function getArguments() {
		if (count($this->properties) > 0) {
			return [
				'prop_' . $this->id => $this->properties
			];
		} else {
			return [];
		}
	}
}