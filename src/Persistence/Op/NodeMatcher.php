<?php
namespace Helmich\Graphizer\Persistence\Op;

interface NodeMatcher extends Operation {

	/**
	 * @return string
	 */
	public function getId();

	/**
	 * @return NodeMatcher
	 */
	public function getMatcher();

	/**
	 * @param string      $type
	 * @param NodeMatcher $other
	 * @param array       $properties
	 * @return CreateEdge
	 */
	public function relate($type, NodeMatcher $other, array $properties = []);

} 