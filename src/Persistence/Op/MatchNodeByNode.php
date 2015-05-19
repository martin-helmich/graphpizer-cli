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

use Everyman\Neo4j\Node;
use Helmich\Graphizer\Persistence\Op\Builder\EdgeBuilder;
use Helmich\Graphizer\Persistence\Op\Builder\UpdateBuilder;

/**
 * Matches a node by using an already existing node object
 *
 * @package    Helmich\Graphizer
 * @subpackage Persistence\Op
 */
class MatchNodeByNode extends AbstractOperation implements NodeMatcher {

	use EdgeBuilder;
	use UpdateBuilder;

	/** @var string */
	private $id;

	/** @var Node */
	private $node;

	/**
	 * @param Node $node
	 */
	public function __construct(Node $node) {
		$this->node = $node;
		$this->id   = $node->getProperty('__node_id');
	}

	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function toCypher() {
		return sprintf(
			'MATCH (%s) WHERE id(%s)={id_%s}',
			$this->id,
			$this->id,
			$this->id
		);
	}

	/**
	 * @return string
	 */
	public function getArguments() {
		return ['id_' . $this->id => $this->node->getId()];
	}

	/**
	 * @return NodeMatcher
	 */
	public function getMatcher() {
		return $this;
	}
}