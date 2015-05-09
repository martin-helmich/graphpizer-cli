<?php
namespace Helmich\Graphizer\Configuration;

class ImportConfiguration {

	/** @var array */
	private $excludePatterns = [];

	/** @var array */
	private $matchPatterns = [];

	/** @var ImportConfigurationInterpreter */
	private $interpreter;

	public function __construct(array $matchPatterns = [], array $excludePatterns = []) {
		$this->matchPatterns   = $matchPatterns;
		$this->excludePatterns = $excludePatterns;
		$this->interpreter     = new ImportConfigurationInterpreter($this);
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

	public function merge(ImportConfiguration $other) {
		return new ImportConfiguration(
			array_merge($this->matchPatterns, $other->matchPatterns),
			array_merge($this->excludePatterns, $other->excludePatterns)
		);
	}
}