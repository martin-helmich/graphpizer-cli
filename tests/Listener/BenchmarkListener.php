<?php
namespace Helmich\Graphizer\Tests\Listener;

use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;

class BenchmarkListener extends \PHPUnit_Framework_BaseTestListener {

	protected $filename;

	protected $times = [];

	public function __construct($filename) {
		$this->filename = $filename;
	}

	public function endTest(PHPUnit_Framework_Test $test, $time) {
		if ($test instanceof \PHPUnit_Framework_TestCase) {
			$this->times[$test->getName()] = $time;
		}
	}

	public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
		$out = json_encode($this->times, JSON_PRETTY_PRINT);
		file_put_contents($this->filename, $out);
	}

}