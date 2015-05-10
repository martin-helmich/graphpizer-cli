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

namespace Helmich\Graphizer\Modeler\TypeInference;

use Everyman\Neo4j\Node;

class SymbolTable {

	/** @var SymbolTable[] */
	private $scopes = [];

	/** @var array */
	private $symbols = [];

	public function scope($name) {
		if (FALSE === array_key_exists($name, $this->scopes)) {
			$this->scopes[$name] = new SymbolTable();
		}
		return $this->scopes[$name];
	}

	public function addSymbol($symbolName, array $types = []) {
		$this->symbols[$symbolName] = [];
		foreach ($types as $type) {
			$this->addTypeForSymbol($symbolName, $type);
		}
	}

	public function addTypeForSymbol($symbolName, Node $type) {
		if (!$this->hasSymbol($symbolName)) {
			$this->addSymbol($symbolName);
		}
		$this->symbols[$symbolName][$type->getProperty('name')] = $type;
	}

	public function hasSymbol($symbolName) {
		return array_key_exists($symbolName, $this->symbols);
	}

	public function getTypesForSymbol($symbolName) {
		if (!$this->hasSymbol($symbolName)) {
			throw new \InvalidArgumentException("Unknown symbol: \"{$symbolName}\"");
		}
		return array_values($this->symbols[$symbolName]);
	}

	public function dump($indent = 0) {
		foreach($this->scopes as $key => $symbols) {
			echo str_repeat(' ', $indent) . "Sub-Scope {$key}:\n";
			$symbols->dump($indent + 2);
		}

		foreach($this->symbols as $key => $types) {
			echo str_repeat(' ', $indent) .
				"{$key}: " .
				implode(', ', array_map(function(Node $type) { return $type->getProperty('name'); }, $types)) .
				"\n";
		}
	}
}