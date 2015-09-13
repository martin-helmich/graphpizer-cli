<?php
namespace Helmich\Graphizer\Persistence\Engine;


use Helmich\Graphizer\Configuration\ProjectConfiguration;
use Helmich\Graphizer\Persistence\BulkOperation;

class JsonBulkOperation extends BulkOperation {

	/** @var Backend */
	private $backend;

	/** @var ProjectConfiguration */
	private $project;

	public function __construct(ProjectConfiguration $project, Backend $backend) {
		$this->backend = $backend;
		$this->project = $project;
	}

	/**
	 * @return \Traversable
	 */
	public function evaluate() {
		$result = [
			'nodes' => [],
			'relationships' => []
		];

		foreach ($this->operations as $operation) {
			$enc = $operation->toJson();

			foreach ($enc as $key => $value) {
				if (!array_key_exists($key, $result)) {
					$result[$key] = $value;
				} else {
					$result[$key] = array_merge($result[$key], $value);
				}
			}
		}

		$this->backend->import($this->project, $result);
	}

}