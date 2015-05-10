<?php
namespace Helmich\Graphizer\Persistence;


use Everyman\Neo4j\Query\ResultSet;

class TypedResultSetProxy extends ResultSet {

	/**
	 * @var ResultSet
	 */
	private $resultSet;

	public function __construct(ResultSet $resultSet) {
		$this->resultSet = $resultSet;
	}

	/**
	 * @return \Everyman\Neo4j\Query\QueryStatistics
	 */
	public function getStatistics() {
		return $this->resultSet->getStatistics();
	}

	/**
	 * Return the current element
	 *
	 * @return mixed Can return any type.
	 */
	public function current() {
		$current = $this->resultSet->current();
		return new TypedResultRowAdapter($current);
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
		return new TypedResultRowAdapter($value);
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
	}}