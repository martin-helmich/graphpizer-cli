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

use JsonSchema\RefResolver;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;

class ConfigurationReader {

	/** @var object */
	private $schema;

	/** @var Validator */
	private $validator;

	public function __construct() {
		$retriever     = new UriRetriever();
		$baseDirectory = realpath(__DIR__ . '/../../res');

		$this->schema = $retriever->retrieve('file://' . $baseDirectory . '/config-schema.json');

		$refResolver = new RefResolver($retriever);
		$refResolver->resolve($this->schema, 'file://' . $baseDirectory . '/config-schema.json');

		$this->validator = new Validator();
	}

	public function readConfigurationFromFile($filename) {
		if (!file_exists($filename)) {
			throw new \InvalidArgumentException('File "' . $filename . '" does not exist!');
		}

		$baseDirectory = realpath(dirname($filename));

		$content = file_get_contents($filename);
		$data    = json_decode($content);

		$this->validator->check($data, $this->schema);
		if (!$this->validator->isValid()) {
			$errorText = '';
			foreach ($this->validator->getErrors() as $error) {
				$errorText .= sprintf("[%s] %s\n", $error['property'], $error['message']);
			}
			$errorText = trim($errorText);
			throw new \InvalidArgumentException("File {$filename} contains invalid configuration:\n{$errorText}");
		}

		$config = $this->buildConfigurationFromData($data->config);

		if (isset($data->subConfigs)) {
			foreach ($data->subConfigs as $path => $subConfigData) {
				$subPath = $baseDirectory . '/' . trim($path, '/');
				$config->addConfigurationForSubPath($subPath, $this->buildConfigurationFromData($subConfigData));
			}
		}

		return $config;
	}

	/**
	 * @param object $config
	 * @return Configuration
	 */
	protected function buildConfigurationFromData($config) {
		$package = NULL;
		$project = NULL;

		if (isset($config->package)) {
			$packageData = $config->package;
			$package     = new PackageConfiguration($packageData->name, $packageData->description);
		}

		if (isset($config->project)) {
			$projectData = $config->project;

			$transformations = [];
			if (isset($projectData->additionalTransformations)) {
				foreach ($projectData->additionalTransformations as $transformation) {
					$transformations[] = new ProjectTransformationConfiguration(
						$transformation->when,
						$transformation->cypher
					);
				}
			}

			$project = new ProjectConfiguration(
				$projectData->slug,
				$projectData->name,
				$transformations
			);
		}

		return new Configuration(
			isset($config->matchPatterns) ? $config->matchPatterns : [],
			isset($config->excludePatterns) ? $config->excludePatterns : [],
			$package,
			$project
		);
	}
}
