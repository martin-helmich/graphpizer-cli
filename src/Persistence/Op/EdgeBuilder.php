<?php
namespace Helmich\Graphizer\Persistence\Op;

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