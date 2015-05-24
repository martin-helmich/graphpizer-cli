<?php
namespace Helmich\Graphizer\Exporter\Graph;

use Helmich\Graphizer\Persistence\Neo4j\Backend;

class GexfExporter implements ExporterInterface {

	/**
	 * @var \Helmich\Graphizer\Persistence\Neo4j\Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function export(ExportConfiguration $configuration) {
		$domDocument = new \DOMDocument('1.0', 'UTF-8');
		$domDocument->formatOutput = $configuration->isPretty();

		$root = $domDocument->createElement('gexf');
		$root->setAttribute('xmlns', 'http://www.gexf.net/1.2draft');
		$root->setAttribute('version', '1.2');

		$graphElement = $domDocument->createElement('graph');
		$graphElement->setAttribute('defaultedgetype', 'directed');

		$nodesElement = $domDocument->createElement('nodes');

		$q = $this->backend->createQuery('MATCH c WHERE c:Class OR c:Interface OR c:Trait RETURN c', 'c');
		foreach($q->execute() as $node) {
			$nodeElement = $domDocument->createElement('node');
			$nodeElement->setAttribute('id', $node->getId());
			$nodeElement->setAttribute('label', $node->getProperty('fqcn'));

			$nodesElement->appendChild($nodeElement);
		}

		$edgedElement = $domDocument->createElement('edges');

		$q = $this->backend->createQuery('MATCH (a)-[r]->(b) WHERE (a:Class OR a:Interface OR a:Trait) AND (b:Class OR b:Interface OR b:Trait) RETURN r');
		foreach($q->execute() as $row) {
			$r = $row->relationship('r');

			$edgeElement = $domDocument->createElement('edge');
			$edgeElement->setAttribute('id', $r->getId());
			$edgeElement->setAttribute('source', $r->getStartNode()->getId());
			$edgeElement->setAttribute('target', $r->getEndNode()->getId());

			$edgedElement->appendChild($edgeElement);
		}

		$graphElement->appendChild($nodesElement);
		$graphElement->appendChild($edgedElement);

		$root->appendChild($graphElement);
		$domDocument->appendChild($root);

		return $domDocument->saveXML();
	}
}