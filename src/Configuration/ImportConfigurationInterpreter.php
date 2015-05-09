<?php
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