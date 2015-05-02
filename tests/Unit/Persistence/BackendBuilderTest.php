<?php

/*
 * GraPHPizer - Store PHP syntax trees in a Neo4j database
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

namespace Helmich\Graphizer\Tests\Unit\Persistence;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Transport;
use Faker\Factory;
use Helmich\Graphizer\Persistence\BackendBuilder;
use Helmich\Graphizer\Persistence\DebuggerInterface;
use Helmich\Graphizer\Tests\Unit\AbstractUnitTestCase;

class BackendBuilderTest extends AbstractUnitTestCase {

	/**
	 * @test
	 * @dataProvider buildConnectionData
	 */
	public function shouldBuildBackendConnectionWithoutCredentials($hostname, $port, $user, $password, $useDebugger) {
		$transport = $this->getMockBuilder(Transport::class)->disableOriginalConstructor()->getMock();

		$client = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
		$client->expects(any())->method('getTransport')->willReturn($transport);

		if ($user) {
			$transport->expects(once())->method('setAuth')->with($user, $password);
		} else {
			$transport->expects(never())->method('setAuth');
		}

		$builder = new BackendBuilder();
		$builder
			->setHost($hostname)
			->setPassword($password)
			->setUser($user)
			->setPort($port)
			->setClientFactory(
				function ($host, $port) use (&$client, $hostname, $port) {
					assertThat($host, equalTo($hostname));
					assertThat($port, equalTo($port));
					return $client;
				}
			);

		if ($useDebugger) {
			$debugger = $this->getMockBuilder(DebuggerInterface::class)->getMock();
			$builder->setDebugger($debugger);
		}

		$backend = $builder->build();
		assertThat($backend->getClient(), equalTo($client));
	}

	public function buildConnectionData() {
		$faker = Factory::create('de_DE');
		for ($i = 0; $i < 10; $i++) {
			$user   = $faker->optional()->userName;
			yield [
				$faker->domainName,
				$faker->numberBetween(10, 10000),
				$user,
				$user ? $faker->password : NULL,
				$faker->boolean()
			];
		}
	}
}