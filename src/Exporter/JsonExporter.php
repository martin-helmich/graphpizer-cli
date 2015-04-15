<?php
namespace Helmich\Graphizer\Exporter;

use Everyman\Neo4j\Label;
use Helmich\Graphizer\Persistence\Backend;

class JsonExporter implements ExporterInterface {

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function export($withMethods = FALSE, $withProperties = FALSE, $pretty = FALSE) {
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
				'id'   => $node->getId(),
				'fqcn' => $node->getProperty('fqcn'),
				'type' => in_array('Class', $labels) ? 'class' : (in_array('Interface', $labels) ? 'Interface' : 'Trait')
			];
			$idMap[$node->getId()] = $c;

			$c++;
		}

		$q =
			$this->backend->createQuery(
				'MATCH (a)-[r]->(b) WHERE (a:Class OR a:Interface OR a:Trait) AND (b:Class OR b:Interface OR b:Trait) RETURN r'
			);
		foreach ($q->execute() as $row) {
			$r       = $row->relationship('r');
			$edges[] = [
				'id'     => $r->getId(),
				'type'   => $r->getType(),
				'source' => $idMap[$r->getStartNode()->getId()],
				'target' => $idMap[$r->getEndNode()->getId()]
			];
		}

		return json_encode([
			'nodes' => $nodes,
			'edges' => $edges
		]);
	}
}