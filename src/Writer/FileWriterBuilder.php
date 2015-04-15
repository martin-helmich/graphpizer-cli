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

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function build() {
		return new FileWriter(
			$this->backend,
			(new NodeWriterBuilder($this->backend))->build(),
			new Parser(new Lexer())
		);
	}
}