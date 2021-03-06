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

namespace Helmich\Graphizer\Persistence\Op\Builder;

use Helmich\Graphizer\Persistence\Op\NodeMatcher;
use Helmich\Graphizer\Persistence\Op\CreateEdge;

/**
 * Trait for building new outgoing edges from existing nodes
 *
 * @package    Helmich\Graphizer
 * @subpackage Persistence\Op\Builder
 */
trait EdgeBuilder {

	/**
	 * @param string      $type
	 * @param NodeMatcher $other
	 * @param array       $properties
	 * @return CreateEdge
	 */
	public function relate($type, NodeMatcher $other, array $properties = []) {
		if ($this instanceof NodeMatcher) {
			return new CreateEdge($this, $other, $type, $properties);
		} else {
			throw new \BadMethodCallException(
				'The ' . self::class . ' trait can only be used in classes that implement ' . NodeMatcher::class . '!'
			);
		}
	}
}