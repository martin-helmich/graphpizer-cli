<?php
namespace Helmich\Graphizer\Persistence;


interface BackendInterface {

	public function wipe();

	/**
	 * @return BulkOperation
	 */
	public function createBulkOperation();

}