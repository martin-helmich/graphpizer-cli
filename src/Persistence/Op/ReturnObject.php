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

namespace Helmich\Graphizer\Persistence\Op;

/**
 * Operation that returns an existing object from the scope
 *
 * @package    Helmich\Graphizer
 * @subpackage Persistence\Op
 */
class ReturnObject implements Operation {

	/**
	 * @var NodeMatcher
	 */
	private $node;

	/**
	 * @param NodeMatcher $node
	 */
	public function __construct(NodeMatcher $node) {
		$this->node = $node;
	}

	/**
	 * @return string
	 */
	public function toCypher() {
		return sprintf('RETURN %s', $this->node->getId());
	}

	/**
	 * @return array
	 */
	public function toJson() {
		return [
			'return' => $this->node->getId()
		];
	}


	/**
	 * @return string
	 */
	public function getArguments() {
		return [];
	}

	/**
	 * @return NodeMatcher[]
	 */
	public function getRequiredNodes() {
		return [$this->node];
	}

}