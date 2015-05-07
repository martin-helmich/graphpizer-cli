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

namespace Helmich\Graphizer\Console\Command\Export;

use Helmich\Graphizer\Console\Command\AbstractCommand;
use Helmich\Graphizer\Exporter\Graph\Dot\CompactRenderingStrategy;
use Helmich\Graphizer\Exporter\Graph\Dot\RenderingStrategy;
use Helmich\Graphizer\Exporter\Graph\Dot\VerboseRenderingStrategy;
use Helmich\Graphizer\Exporter\Graph\DotExporter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportDotCommand extends AbstractCommand {

	protected function configure() {
		$this
			->setName('export:dot')
			->setDescription('Export into DOT format (http://www.graphviz.org/content/dot-language)')
			->addOption('strategy', 's', InputOption::VALUE_REQUIRED, 'Which output strategy to use ("verbose", "compact")', "verbose")
			->addOption('export', 'e', InputOption::VALUE_REQUIRED, 'Export as PNG to this file');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$backend  = $this->connect($input, $output);
		$strategy = $this->getStrategy($input->getOption('strategy'));
		$exporter = new DotExporter($backend, $strategy);

		$dot = $exporter->export(FALSE, FALSE, TRUE);

		if ($input->getOption('export')) {
			$layout = $this->getRendererForStrategy($input->getOption('strategy'));
			$file = escapeshellarg($input->getOption('export'));
			$descriptors = [
				0 => ['pipe', 'r'],
				1 => ['pipe', 'w'],
				2 => ['pipe', 'w']
			];

			$cmd = "dot -Tpng -K{$layout} -o{$file}";

			$output->writeln("Using layout engine <comment>{$layout}</comment>.");
			$output->writeln("Executing <comment>{$cmd}</comment>.");

			$proc = proc_open($cmd, $descriptors, $pipes);
			if (is_resource($proc)) {
				fwrite($pipes[0], $dot);
				fclose($pipes[0]);

				$out = stream_get_contents($pipes[1]);
				$err = stream_get_contents($pipes[2]);

				fclose($pipes[1]);
				fclose($pipes[2]);

				$result = proc_close($proc);
				if (0 !== $result) {
					throw new \Exception("GraphViz exited with status {$result}!\nCommand was:{$cmd}\nSTDOUT: ${out}\nSTDERR: ${err}");
				}
			}
		} else {
			$output->write($dot);
		}
	}

	private function getRendererForStrategy($key) {
		switch ($key) {
			case 'verbose':
				return 'fdp';
			case 'compact':
				return 'neato';
				break;
			default:
				throw new \InvalidArgumentException('"strategy" argument must be one of "verbose" or "compact".');
		}
	}

	/**
	 * @param string $key
	 * @return RenderingStrategy
	 */
	private function getStrategy($key) {
		switch ($key) {
			case 'verbose':
				return new VerboseRenderingStrategy();
			case 'compact':
				return new CompactRenderingStrategy();
			default:
				throw new \InvalidArgumentException('"strategy" argument must be one of "verbose" or "compact".');
		}
	}
}