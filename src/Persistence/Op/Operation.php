<?php
namespace Helmich\Graphizer\Persistence\Op;

interface Operation {

	/**
	 * @return string
	 */
	public function toCypher();

	/**
	 * @return string
	 */
	public function getArguments();

	/**
	 * @return NodeMatcher[]
	 */
	public function getRequiredNodes();
} 