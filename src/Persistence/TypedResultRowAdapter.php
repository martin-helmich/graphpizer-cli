<?php
namespace Helmich\Graphizer\Persistence;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;

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