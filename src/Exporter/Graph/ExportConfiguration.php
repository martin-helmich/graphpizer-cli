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

namespace Helmich\Graphizer\Exporter\Graph;

class ExportConfiguration {

	protected $withUsages     = FALSE;

	protected $withProperties = FALSE;

	protected $withMethods    = FALSE;

	protected $pretty         = FALSE;

	/**
	 * @param boolean $withUsages
	 * @return self
	 */
	public function setWithUsages($withUsages) {
		$this->withUsages = $withUsages;
		return $this;
	}

	/**
	 * @param boolean $withProperties
	 * @return self
	 */
	public function setWithProperties($withProperties) {
		$this->withProperties = $withProperties;
		return $this;
	}

	/**
	 * @param boolean $withMethods
	 * @return self
	 */
	public function setWithMethods($withMethods) {
		$this->withMethods = $withMethods;
		return $this;
	}

	/**
	 * @param boolean $pretty
	 * @return self
	 */
	public function setPretty($pretty) {
		$this->pretty = $pretty;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isWithUsages() {
		return $this->withUsages;
	}

	/**
	 * @return boolean
	 */
	public function isWithProperties() {
		return $this->withProperties;
	}

	/**
	 * @return boolean
	 */
	public function isWithMethods() {
		return $this->withMethods;
	}

	/**
	 * @return boolean
	 */
	public function isPretty() {
		return $this->pretty;
	}
}