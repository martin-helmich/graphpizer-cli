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

class ProjectTransformationConfiguration {

	/** @var string */
	private $when;

	/** @var string */
	private $cypher;

	public function __construct($when, $cypher) {
		$this->when   = $when;
		$this->cypher = $cypher;
	}

	/**
	 * @return string
	 */
	public function getWhen() {
		return $this->when;
	}

	/**
	 * @return string
	 */
	public function getCypher() {
		return $this->cypher;
	}


}