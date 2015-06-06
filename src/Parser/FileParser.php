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

namespace Helmich\Graphizer\Parser;

use PhpParser\Parser;

/**
 * PHP file parser class.
 *
 * @package Helmich\Graphizer
 * @subpackage Parser
 */
class FileParser {

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @param Parser $parser
	 */
	public function __construct(Parser $parser){
		$this->parser = $parser;
	}

	public function parseFile($filename) {
		$code = file_get_contents($filename);
		return $this->parser->parse($code);
	}
}