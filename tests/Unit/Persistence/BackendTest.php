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
use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\ResultSet;
use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Persistence\DebuggerInterface;
use Helmich\Graphizer\Tests\Unit\AbstractUnitTestCase;

class BackendTest extends AbstractUnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $client;

	/**
	 * @var Backend
	 */
	private $backend;

	public function setUp() {
		$this->client  = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
		$this->backend = new Backend($this->client);
	}

	/**
	 * @test
	 */
	public function shouldCreateNewNodesWithPropertiesAndLabels() {
		$label = $this->getMockBuilder(Label::class)->disableOriginalConstructor()->getMock();

		$node = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
		$node->expects(once())->method('save');
		$node->expects(once())->method('addLabels')->with([$label]);

		$properties = ['foo' => 'bar'];

		$this->client->expects(once())->method('makeNode')->with($properties)->willReturn($node);
		$this->client->expects(once())->method('makeLabel')->with('SuperFoo')->willReturn($label);

		assertThat($this->backend->createNode($properties, 'SuperFoo'), identicalTo($node));
	}

	/**
	 * @test
	 */
	public function shouldAddLabelsToExistingNodes() {
		$label = $this->getMockBuilder(Label::class)->disableOriginalConstructor()->getMock();

		$node = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
		$node->expects(once())->method('addLabels')->with([$label]);

		$this->client->expects(once())->method('makeLabel')->with('SuperFoo')->willReturn($label);

		$this->backend->labelNode($node, 'SuperFoo');
	}

	/**
	 * @test
	 */
	public function shouldExecuteCypherQueries() {
		$cypher = 'CREATE (f:Foo {prop}) RETURN f';
		$args   = ['prop' => ['foo' => 'bar']];

		$this->client->expects(once())->method('executeCypherQuery')->with(
			callback(
				function ($query) use ($cypher, $args) {
					assertThat($query, isInstanceOf(Query::class));

					/** @var Query $query */
					assertThat($query->getQuery(), equalTo($cypher));
					assertThat($query->getParameters(), equalTo($args));
					return TRUE;
				}
			)
		);

		$this->backend->execute($cypher, $args);
	}

	/**
	 * @test
	 */
	public function shouldReturnClient() {
		assertThat($this->backend->getClient(), identicalTo($this->client));
	}

	/**
	 * @test
	 */
	public function shouldExecutePreparedStatements() {
		$cypher     = 'MATCH (c) WHERE id(c)={id} RETURN c';
		$parameters = ['id' => 1];

		$result = $this->getMockBuilder(ResultSet::class)->disableOriginalConstructor()->getMock();

		$this->client->expects(once())->method('executeCypherQuery')->with(
			callback(
				function (Query $query) use ($cypher, $parameters) {
					assertThat($query->getQuery(), equalTo($cypher));
					assertThat($query->getParameters(), equalTo($parameters));
					return TRUE;
				}
			)
		)->willReturn($result);

		$statement = $this->backend->createQuery($cypher);
		$statement->execute($parameters);
	}

	/**
	 * @test
	 */
	public function shouldWipeAllNodesAndEdges() {
		$this->client->expects(once())->method('executeCypherQuery')->with(
			callback(
				function (Query $query) {
					assertThat($query->getQuery(), equalTo('MATCH (n) OPTIONAL MATCH (n)-[r]-() DELETE n,r'));
					return TRUE;
				}
			)
		);
		$this->backend->wipe();
	}

	/**
	 * @test
	 * @depends shouldCreateNewNodesWithPropertiesAndLabels
	 */
	public function shouldNotifyDebuggerOnNewNodes() {
		$debugger = $this->getMockBuilder(DebuggerInterface::class)->getMock();
		$backend  = new Backend($this->client, $debugger);

		$label = $this->getMockBuilder(Label::class)->disableOriginalConstructor()->getMock();
		$node = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
		$node->expects(any())->method('getId')->willReturn(1234);

		$properties = ['foo' => 'bar'];

		$this->client->expects(once())->method('makeNode')->with($properties)->willReturn($node);
		$this->client->expects(once())->method('makeLabel')->with('SuperFoo')->willReturn($label);

		$debugger->expects(once())->method('nodeCreated')->with(1234, ['SuperFoo']);

		assertThat($backend->createNode($properties, 'SuperFoo'), identicalTo($node));
	}
}