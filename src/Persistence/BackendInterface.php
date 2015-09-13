<?php
namespace Helmich\Graphizer\Persistence;


use Helmich\Graphizer\Configuration\ProjectConfiguration;

interface BackendInterface {

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