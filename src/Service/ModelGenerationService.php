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

namespace Helmich\Graphizer\Service;

use Helmich\Graphizer\Configuration\ProjectConfiguration;
use Helmich\Graphizer\Persistence\BackendInterface;

class ModelGenerationService {

	/**
	 * @var BackendInterface
	 */
	private $backend;

	public function __construct(BackendInterface $backend) {
		$this->backend = $backend;
	}

	public function generateModel(ProjectConfiguration $project) {
		$client = $this->backend->getClient();
		$uri = '/projects/' . urlencode($project->getSlug()) . '/model/generate';

		$client->post($uri);
	}
}