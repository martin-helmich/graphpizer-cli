<?php
namespace Helmich\Graphizer\Tests\Unit;

interface Logger {
	public function warn($str);
}

class FooTest extends AbstractUnitTestCase {

	public function testFoo() {
		$logger = $this->getMockBuilder(Logger::class)->getMock();
		$logger
			->expects($this->any())
			->method('warn')
			->with($this->callback(function($string) {
				return $string !== 'doNotCallMeWithThisString';
			}));

		$logger->warn('foo');
	}
}