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

namespace Helmich\Graphizer\Console\Listener;

class VerboseFileWriterListener extends NormalFileWriterListener {

	public function onFileReading($filename) {
		$this->output->write('Processing file <comment>' . $filename . '</comment>... ');
	}

	public function onFileRead($filename, $milliseconds) {
		parent::onFileRead($filename, $milliseconds);
		$this->output->writeln(sprintf('Done in <info>%.2d</info>ms', $milliseconds));
	}

	public function onFileFailed($filename, \Exception $error) {
		$this->output->writeln('');
		parent::onFileFailed($filename, $error);
	}


	public function onFileSkipped($filename) {
		$this->output->writeln('Skipping <comment>' . $filename . '</comment>');
	}

	public function onConfigApplied($configFilename) {
		$this->output->writeln('Applying configuration from <comment>' . $configFilename . '</comment>');
	}

}