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

namespace Helmich\Graphizer\Persistence;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;

class TypedResultRowAdapter implements \Iterator, \Countable, \ArrayAccess {

	/**
	 * @var Row
	 */
	private $realRow;

	public function __construct(Row $realRow) {
		$this->realRow = $realRow;
	}

	/**
	 * @param string $key
	 * @return Node
	 */
	public function node($key) {
		return $this[$key];
	}

	/**
	 * @param string $key
	 * @return Relationship
	 */
	public function relationship($key) {
		return $this[$key];
	}

	public function current() {
		return $this->realRow->current();
	}

	public function next() {
		$this->realRow->next();
	}

	public function key() {
		return $this->realRow->key();
	}

	public function valid() {
		return $this->realRow->valid();
	}

	public function rewind() {
		$this->realRow->rewind();
	}

	public function offsetExists($offset) {
		return $this->realRow->offsetExists($offset);
	}

	public function offsetGet($offset) {
		return $this->realRow->offsetGet($offset);
	}

	public function offsetSet($offset, $value) {
		$this->realRow->offsetSet($offset, $value);
	}

	public function offsetUnset($offset) {
		$this->realRow->offsetUnset($offset);
	}

	public function count() {
		return $this->realRow->count();
	}
}