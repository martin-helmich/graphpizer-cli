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

class NormalFileWriterListener extends QuietFileWriterListener {

	/** @var int */
	protected $counter = 0;

	public function onFileRead($filename, $milliseconds) {
		$this->counter++;
	}

	public function onFinish($target) {
		$this->output->writeln('Read <comment>' . $this->counter . '</comment> files from <comment>' . $target . '</comment>');
	}


}