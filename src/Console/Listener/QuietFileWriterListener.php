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

namespace Helmich\Graphizer\Console\Listener;

use Helmich\Graphizer\Writer\FileWriterListener;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;

class QuietFileWriterListener implements FileWriterListener {

	/** @var OutputInterface */
	protected $output;

	/** @var FormatterHelper */
	protected $formatterHelper;

	public function __construct(OutputInterface $output, FormatterHelper $formatterHelper) {
		$this->output          = $output;
		$this->formatterHelper = $formatterHelper;
	}

	public function onFileReading($filename) {
	}

	public function onFileRead($filename, $milliseconds) {
	}

	public function onFileFailed($filename, \Exception $error) {
		$block = $this->formatterHelper->formatBlock(
			['An error occurred while parsing the file "' . $filename . '":', $error->getMessage()],
			'error'
		);

		$this->output->writeln($block);
	}

	public function onFileSkipped($filename) {
	}

	public function onConfigApplied($configFilename) {
	}

	public function onFinish($target) {
	}

	public function onFileUnchanged($filename) {
	}


}