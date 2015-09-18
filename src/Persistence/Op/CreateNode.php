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

use Helmich\Graphizer\Persistence\Op\Builder\EdgeBuilder;
use Helmich\Graphizer\Persistence\Op\Builder\UpdateBuilder;

/**
 * Creates a new node.
 *
 * @package    Helmich\Graphizer
 * @subpackage Persistence\Op
 */
class CreateNode extends AbstractOperation implements NodeMatcher {

	use EdgeBuilder;
	use UpdateBuilder;
	use PropertyTrait;

	private $id;

	private $labels;

	/**
	 * @param string $label
	 * @param array  $properties
	 */
	public function __construct($label, array $properties = []) {
		$this->id         = uniqid('node');
		$this->labels     = [$label];
		$this->properties = $this->filterProperties($properties);
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
	public function getLabel() {
		return $this->labels[0];
	}

	/**
	 * @param string $label
	 * @return void
	 */
	public function addLabel($label) {
		$this->labels[] = $label;
	}

	/**
	 * @return string
	 */
	public function toCypher() {
		return sprintf(
			'CREATE (%s:%s {prop_%s})',
			$this->id,
			implode(':', $this->labels),
			$this->id
		);
	}

	/**
	 * @return array
	 */
	public function toJson() {
		$properties              = $this->properties;
		$properties['__node_id'] = $this->id;

		return [
			'nodes' => [
				[
					'labels'     => $this->labels,
					'properties' => (object) $properties,
					'merge'      => FALSE
				]
			]
		];
	}


	/**
	 * @return string
	 */
	public function getArguments() {
		$properties              = $this->properties;
		$properties['__node_id'] = $this->id;

		return ['prop_' . $this->id => $properties];
	}

	/**
	 * @return NodeMatcher
	 */
	public function getMatcher() {
		return new MatchNodeByIdProperty($this->id);
	}
}