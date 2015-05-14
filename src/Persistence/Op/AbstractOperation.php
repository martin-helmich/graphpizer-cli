<?php
namespace Helmich\Graphizer\Persistence\Op;


abstract class AbstractOperation implements Operation {

	/**
	 * @return string
	 */
	public function getArguments() {
		return [];
	}

	/**
	 * @return NodeMatcher[]
	 */
	public function getRequiredNodes() {
		return [];
	}


} 