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
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\ResultSet;
use Faker\Factory;
use Helmich\Graphizer\Persistence\PreparedStatement;
use Helmich\Graphizer\Tests\Provider\CypherQuery;
use Helmich\Graphizer\Tests\Unit\AbstractUnitTestCase;
use Traversable;

class PreparedStatementTest extends AbstractUnitTestCase {

	/**
	 * @param $cypher
	 * @param $arguments
	 * @param $resultVar
	 * @test
	 * @dataProvider buildCypherExampleQueries
	 */
	public function shouldBuildAndExecuteQuery($cypher, $arguments, $resultVar) {
		$client = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();

		$result = $this->getMockBuilder(ResultSet::class)->disableOriginalConstructor()->getMock();

		$query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();
		$query->expects(once())->method('getResultSet')->willReturn($result);

		$expectedArguments = [];
		foreach($arguments as $key => $value) {
			if ($value instanceof Node) {
				$expectedArguments[$key] = $value->getId();
			} else {
				$expectedArguments[$key] = $value;
			}
		}

		$statement = new PreparedStatement($client, $cypher, $resultVar);
		$statement->setQueryFactory(
			function (array $actualArguments) use ($expectedArguments, $query) {
				assertThat($actualArguments, equalTo($expectedArguments));
				return $query;
			}
		);

		$actualResult = $statement->execute($arguments);
		if (NULL === $resultVar) {
			assertThat($actualResult, isInstanceOf(ResultSet::class));
		} else {
			assertThat($actualResult, isInstanceOf(Traversable::class));
		}
	}

	public function buildCypherExampleQueries() {
		$f = Factory::create('de_DE');
		$f->addProvider(new CypherQuery($f));

		for ($i = 0; $i < 5; $i++) {
			yield $f->cypherQuery;
		}

		$node = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
		$node->expects(any())->method('getId')->willReturn(1234);

		yield [
			'MATCH (c) WHERE id(c)={node} RETURN c',
			['node' => $node],
			NULL
		];
	}
}