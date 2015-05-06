<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Persistence\Backend;
use PhpParser\Lexer;
use PhpParser\Parser;

class FileWriterBuilder {

	/**
	 * @var Backend
	 */
	private $backend;

	/**
	 * @var array
	 */
	private $excludePatterns = [];

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function setExcludePatterns(array $excludeList) {
		$this->excludePatterns = $excludeList;
		return $this;
	}

	public function build() {
		return new FileWriter(
			$this->backend,
			(new NodeWriterBuilder($this->backend))->build(),
			new Parser(new Lexer()),
			$this->excludePatterns
		);
	}
}