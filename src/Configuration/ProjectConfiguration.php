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

namespace Helmich\Graphizer\Configuration;

class ProjectConfiguration implements \JsonSerializable {

	/** @var string */
	private $slug;

	/** @var string */
	private $name;

	/** @var ProjectTransformationConfiguration[] */
	private $additionalTransformations;

	/**
	 * @param string                               $slug
	 * @param string                               $name
	 * @param ProjectTransformationConfiguration[] $additionalTransformations
	 */
	public function __construct($slug, $name, array $additionalTransformations = []) {
		$this->slug                      = $slug;
		$this->name                      = $name;
		$this->additionalTransformations = $additionalTransformations;
	}

	/**
	 * @return string
	 */
	public function getSlug() {
		return $this->slug;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return ProjectTransformationConfiguration[]
	 */
	public function getAdditionalTransformations() {
		return $this->additionalTransformations;
	}

	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by `json_encode`, which is a value of any type other than a resource.
	 */
	function jsonSerialize() {
		return [
			'name' => $this->name,
			'additionalTransformations' => array_map(function(ProjectTransformationConfiguration $t) {
				return [
					'when' => $t->getWhen(),
					'cypher' => $t->getCypher()
				];
			}, $this->additionalTransformations)
		];
	}
}