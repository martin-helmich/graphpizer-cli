<?php
namespace Helmich\Graphizer\Exporter\Graph;

use Everyman\Neo4j\Label;
use Helmich\Graphizer\Persistence\Neo4j\Backend;

class JsonExporter implements ExporterInterface {

	/**
	 * @var \Helmich\Graphizer\Persistence\Neo4j\Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function export(ExportConfiguration $configuration) {
		$nodes = [];
		$edges = [];

		$idMap = [];

		$q = $this->backend->createQuery('MATCH c WHERE c:Class OR c:Interface OR c:Trait RETURN c', 'c');
		$c = 0;
		foreach ($q->execute() as $node) {
			$labels = array_map(function(Label $l) {
				return $l->getName();
			}, $node->getLabels());

			$nodes[$c] = [
				'id'                   => $node->getId(),
				'fqcn'                 => $node->getProperty('fqcn'),
				'type'                 => in_array('Class', $labels) ? 'class' : (in_array('Interface', $labels) ? 'Interface' : 'Trait'),
				'nodeCount'            => $node->getProperty('nodeCount'),
				'cyclomaticComplexity' => $node->getProperty('cyclomaticComplexity'),
			];
			$idMap[$node->getId()] = $c;

			$c++;
		}

		$q =
			$this->backend->createQuery('
				MATCH (a)-[r]->(b) WHERE (a:Class OR a:Interface OR a:Trait) AND (b:Class OR b:Interface OR b:Trait) RETURN a, b, r UNION
				MATCH (a)-[r]->(t:Type)-[:IS]->(b) WHERE (a:Class OR a:Interface OR a:Trait) AND (b:Class OR b:Interface OR b:Trait) RETURN a, b, r'
			);
		foreach ($q->execute() as $row) {
			$r       = $row->relationship('r');
			$edges[] = [
				'id'     => $r->getId(),
				'type'   => $r->getType(),
				'source' => $idMap[$row->node('a')->getId()],
				'target' => $idMap[$row->node('b')->getId()],
				'comment' => $row->node('a')->getProperty('fqcn') . ' ' . $r->getType() . ' ' . $row->node('b')->getProperty('fqcn')
			];
		}

		$flags = 0;
		if ($configuration->isPretty()) {
			$flags = JSON_PRETTY_PRINT;
		}

		return json_encode([
			'nodes' => $nodes,
			'edges' => $edges
		], $flags);
	}
}