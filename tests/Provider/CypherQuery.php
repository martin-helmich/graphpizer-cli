<?php
namespace Helmich\Graphizer\Tests\Provider;

use Faker\Provider\Base;

class CypherQuery extends Base {

	public function cypherQuery() {
		$queries = [
			[
				'MATCH (c:Person) WHERE c.firstName={firstname} AND c.lastName={lastName} RETURN c',
				['firstname' => '{{firstName}}', 'lastname' => '{{lastName}}'],
				'c'
			],
			[
				'CREATE (x:Person {firstName: {firstname1}})-[:KNOWS]->(y:Person {firstName: {firstname2}})',
				['firstname1' => '{{firstName}}', 'firstname2' => '{{firstName}}'],
				NULL
			]
		];

		$element = self::randomElement($queries);
		return [$element[0], array_map([$this->generator, 'parse'], $element[1]), $element[2]];
	}
}