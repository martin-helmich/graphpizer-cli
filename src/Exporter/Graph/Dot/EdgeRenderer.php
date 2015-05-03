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

namespace Helmich\Graphizer\Exporter\Graph\Dot;

use Everyman\Neo4j\Relationship;

trait EdgeRenderer {

	public function getLineStyle(Relationship $edge) {
		switch ($edge->getType()) {
			case 'USES_TRAIT':
			case 'USES':
				return 'dashed';
			default:
				return 'solid';
		}
	}

	public function getArrowheadShape(Relationship $edge) {
		switch ($edge->getType()) {
			case 'IMPLEMENTS':
				return 'onormal';
			case 'EXTENDS':
			case 'USES_TRAIT':
				return 'normal';
			default:
				return 'vee';
		}
	}
}