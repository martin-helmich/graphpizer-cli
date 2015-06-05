<?php
namespace Helmich\Graphizer\Persistence;


interface BackendInterface {

	public function wipe();

	/**
	 * @return BulkOperation
	 */
	public function createBulkOperation();

	/**
	 * @param string $filename
	 * @param string $checksum
	 * @return bool
	 */
	public function isFileUnchanged($filename, $checksum);

}