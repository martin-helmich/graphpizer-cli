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

namespace Helmich\Graphizer\Persistence;

use GuzzleHttp\Client;
use Helmich\Graphizer\Configuration\ProjectConfiguration;

interface BackendInterface {

	/**
	 * Returns the raw HTTP client
	 *
	 * @return Client
	 */
	public function getClient();

	/**
	 * Creates or updates a project on the remote server
	 *
	 * @param ProjectConfiguration $project The project to create/update
	 * @return void
	 */
	public function upsertProject(ProjectConfiguration $project);

	/**
	 * Wipes all imported source data from a project
	 *
	 * @param ProjectConfiguration $project The project for which to wipe the imported data
	 * @return void
	 */
	public function wipe(ProjectConfiguration $project);

	/**
	 * @param ProjectConfiguration $project
	 * @return BulkOperation
	 */
	public function createBulkOperation(ProjectConfiguration $project);

	/**
	 * @param ProjectConfiguration $project
	 * @param string               $filename
	 * @param string               $checksum
	 * @return bool
	 */
	public function isFileUnchanged(ProjectConfiguration $project, $filename, $checksum);

}