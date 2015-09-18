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

use Everyman\Neo4j\Query\ResultSet;

class SinglePropertyFilter implements \Iterator, \Countable, \ArrayAccess {

	/**
	 * @var ResultSet
	 */
	private $resultSet;

	/**
	 * @var
	 */
	private $property;

	public function __construct(ResultSet $resultSet, $property) {
		$this->resultSet = $resultSet;
		$this->property  = $property;
	}

	/**
	 * Return the current element
	 *
	 * @return mixed Can return any type.
	 */
	public function current() {
		$current = $this->resultSet->current();
		return $current[$this->property];
	}

	/**
	 * Move forward to next element
	 *
	 * @return void Any returned value is ignored.
	 */
	public function next() {
		$this->resultSet->next();
	}

	/**
	 * Return the key of the current element
	 *
	 * @return mixed scalar on success, or null on failure.
	 */
	public function key() {
		return $this->resultSet->key();
	}

	/**
	 * Checks if current position is valid
	 *
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 *       Returns true on success or false on failure.
	 */
	public function valid() {
		return $this->resultSet->valid();
	}

	/**
	 * Rewind the Iterator to the first element
	 *
	 * @return void Any returned value is ignored.
	 */
	public function rewind() {
		$this->resultSet->rewind();
	}

	/**
	 * Whether a offset exists
	 *
	 * @param mixed $offset An offset to check for.
	 * @return boolean true on success or false on failure.
	 *                      The return value will be casted to boolean if non-boolean was returned.
	 */
	public function offsetExists($offset) {
		return $this->resultSet->offsetExists($offset);
	}

	/**
	 * Offset to retrieve
	 *
	 * @param mixed $offset The offset to retrieve.
	 * @return mixed Can return all value types.
	 */
	public function offsetGet($offset) {
		$value = $this->resultSet->offsetGet($offset);
		return $value[$this->property];
	}

	/**
	 * Offset to set
	 *
	 * @param mixed $offset The offset to assign the value to.
	 * @param mixed $value  The value to set.
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		throw new \BadMethodCallException("You cannot modify a query result.");
	}

	/**
	 * Offset to unset
	 *
	 * @param mixed $offset The offset to unset.
	 * @return void
	 */
	public function offsetUnset($offset) {
		throw new \BadMethodCallException("You cannot modify a query result.");
	}

	/**
	 * Count elements of an object
	 *
	 * @return int The custom count as an integer.
	 *       The return value is cast to an integer.
	 */
	public function count() {
		return $this->resultSet->count();
	}
}