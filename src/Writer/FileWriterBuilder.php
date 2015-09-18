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

namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Configuration\Configuration;
use Helmich\Graphizer\Configuration\ConfigurationReader;
use Helmich\Graphizer\Configuration\ImportConfiguration;
use Helmich\Graphizer\Parser\CachingDecorator;
use Helmich\Graphizer\Parser\FileParser;
use Helmich\Graphizer\Persistence\BackendInterface;
use PhpParser\Lexer;
use PhpParser\Parser;

class FileWriterBuilder {

	/** @var BackendInterface */
	private $backend;

	/**
	 * @var ImportConfiguration
	 */
	private $configuration;

	/** @var ConfigurationReader */
	private $configurationReader;

	public function __construct(BackendInterface $backend) {
		$this->backend = $backend;
	}

	/**
	 * @param ImportConfiguration $configuration
	 * @return self
	 */
	public function setConfiguration(ImportConfiguration $configuration) {
		$this->configuration = $configuration;
		return $this;
	}

	/**
	 * @param ConfigurationReader $configurationReader
	 * @return $this
	 */
	public function setConfigurationReader(ConfigurationReader $configurationReader) {
		$this->configurationReader = $configurationReader;
		return $this;
	}

	public function build() {
		if (NULL === $this->configuration) {
			$this->configuration = new Configuration();
		}

		if (NULL == $this->configurationReader) {
			$this->configurationReader = new ConfigurationReader();
		}

		$parser = new Parser(new Lexer());
		$fileParser = new CachingDecorator(new FileParser($parser), getcwd() . '/.graphizer-cache');

		return new FileWriter(
			$this->backend,
			(new NodeWriterBuilder())->build(),
			$fileParser,
			$this->configuration,
			$this->configurationReader
		);
	}
}