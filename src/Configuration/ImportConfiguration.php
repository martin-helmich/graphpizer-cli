<?php
namespace Helmich\Graphizer\Configuration;

interface ImportConfiguration {

	/**
	 * @return array
	 */
	public function getExcludePatterns();

	/**
	 * @return array
	 */
	public function getMatchPatterns();

	/**
	 * @return ImportConfigurationInterpreter
	 */
	public function getInterpreter();

	/**
	 * @return bool
	 */
	public function hasExplicitPackageConfigured();

	/**
	 * @param string $directory
	 * @return bool
	 */
	public function hasConfigurationForSubDirectory($directory);

	/**
	 * @param string $directory
	 * @return ImportConfiguration
	 */
	public function getConfigurationForSubDirectory($directory);

	/**
	 * @return PackageConfiguration
	 */
	public function getPackage();

}