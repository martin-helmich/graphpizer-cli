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

class Configuration implements ImportConfiguration {

	/** @var array */
	private $excludePatterns = [];

	/** @var array */
	private $matchPatterns = [];

	/** @var ImportConfigurationInterpreter */
	private $interpreter;

	/** @var PackageConfiguration */
	private $package;

	/** @var Configuration[] */
	private $subConfigurations = [];

	/** @var ProjectConfiguration */
	private $projectConfiguration;

	public function __construct(
		array $matchPatterns = [],
		array $excludePatterns = [],
		PackageConfiguration $packageConfiguration = NULL,
		ProjectConfiguration $projectConfiguration = NULL
	) {
		$this->matchPatterns        = $matchPatterns;
		$this->excludePatterns      = $excludePatterns;
		$this->interpreter          = new ImportConfigurationInterpreter($this);
		$this->package              = $packageConfiguration;
		$this->projectConfiguration = $projectConfiguration;
	}

	/**
	 * @return array
	 */
	public function getExcludePatterns() {
		return $this->excludePatterns;
	}

	/**
	 * @return array
	 */
	public function getMatchPatterns() {
		return $this->matchPatterns;
	}

	/**
	 * @return ImportConfigurationInterpreter
	 */
	public function getInterpreter() {
		return $this->interpreter;
	}

	/**
	 * @param string        $path
	 * @param Configuration $configuration
	 */
	public function addConfigurationForSubPath($path, Configuration $configuration) {
		$this->subConfigurations[$path] = $configuration;
	}

	/**
	 * @param Configuration $other
	 * @return Configuration
	 */
	public function merge(Configuration $other) {
		$merged = new Configuration(
			array_merge($this->matchPatterns, $other->matchPatterns),
			array_merge($this->excludePatterns, $other->excludePatterns),
			$other->getPackage() ? $other->getPackage() : $this->getPackage(),
			$other->getProject() ? $other->getProject() : $this->getProject()
		);

		$merged->subConfigurations = array_merge($this->subConfigurations, $other->subConfigurations);

		return $merged;
	}

	/**
	 * @return bool
	 */
	public function hasExplicitPackageConfigured() {
		return NULL !== $this->package;
	}

	/**
	 * @return PackageConfiguration
	 */
	public function getPackage() {
		return $this->package;
	}

	/**
	 * @return ProjectConfiguration
	 */
	public function getProject() {
		return $this->projectConfiguration;
	}

	/**
	 * @param string $directory
	 * @return bool
	 */
	public function hasConfigurationForSubDirectory($directory) {
		return array_key_exists($directory, $this->subConfigurations);
	}

	/**
	 * @param string $directory
	 * @return ImportConfiguration
	 */
	public function getConfigurationForSubDirectory($directory) {
		if ($this->hasConfigurationForSubDirectory($directory)) {
			return $this->subConfigurations[$directory];
		} else {
			return NULL;
		}
	}
}