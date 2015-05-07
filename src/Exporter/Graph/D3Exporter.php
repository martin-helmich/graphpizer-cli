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

namespace Helmich\Graphizer\Exporter\Graph;

class D3Exporter implements ExporterInterface {

	/**
	 * @var JsonExporter
	 */
	private $jsonExporter;

	public function __construct(JsonExporter $jsonExporter) {
		$this->jsonExporter = $jsonExporter;
	}

	public function export(ExportConfiguration $configuration) {
		$loader = new \Twig_Loader_Filesystem(__DIR__ . '/../../../view');
		$twig   = new \Twig_Environment($loader);

		$template = $twig->loadTemplate('d3-graph.html');
		return $template->render(['graph' => $this->jsonExporter->export($configuration)]);
	}
}