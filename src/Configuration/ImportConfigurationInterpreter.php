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

namespace Helmich\Graphizer\Configuration;

class ImportConfigurationInterpreter {

	/**
	 * @var ImportConfiguration
	 */
	private $configuration;

	public function __construct(ImportConfiguration $configuration) {
		$this->configuration = $configuration;
	}

	public function isDirectoryEntryMatching($entry) {
		foreach($this->configuration->getExcludePatterns() as $excludePattern) {
			if (strstr($entry, $excludePattern) !== FALSE) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * @param string $entry
	 * @return bool
	 */
	public function isFileMatching($entry) {
		if (!$this->isDirectoryEntryMatching($entry)) {
			return FALSE;
		}

		foreach ($this->configuration->getMatchPatterns() as $matchPattern) {
			if (preg_match($matchPattern, $entry)) {
				return TRUE;
			}
		}

		return FALSE;
	}
}