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

/**
 * Caching decorator for PHP file parsers.
 *
 * @package Helmich\Graphizer
 * @subpackage Parser
 */
class CachingDecorator extends FileParser {

	/**
	 * @var string
	 */
	private $cachingDir;

	/**
	 * @var FileParser
	 */
	private $inner;

	/**
	 * @param FileParser $inner
	 * @param string     $cachingDir
	 */
	public function __construct(FileParser $inner, $cachingDir) {
		$this->inner = $inner;
		$this->cachingDir = $cachingDir;
	}

	public function parseFile($filename) {
		$checksum  = sha1_file($filename);
		$cacheFile = $this->cachingDir . '/' . substr($checksum, 0, 2) . '/' . substr($checksum, 2);

		if (file_exists($cacheFile)) {
			$nodes = unserialize(file_get_contents($cacheFile));
		} else {
			$nodes   = $this->inner->parseFile($filename);
			$dirname = dirname($cacheFile);

			if (!is_dir($dirname)) {
				mkdir($dirname, 0755, TRUE);
			}

			file_put_contents($cacheFile, serialize($nodes));
		}

		return $nodes;
	}


}