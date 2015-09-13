<?php
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