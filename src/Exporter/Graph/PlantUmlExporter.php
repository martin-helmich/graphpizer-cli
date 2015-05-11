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

namespace Helmich\Graphizer\Exporter\Graph;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Relationship;
use Helmich\Graphizer\Persistence\Backend;

/**
 * Exports a model into a DOT format file.
 *
 * @package    Helmich\Graphizer
 * @subpackage Exporter\Graph
 */
class PlantUmlExporter implements ExporterInterface {

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	private function processClassName($name) {
		return str_replace('\\', '_', $name);
	}

	private function getLabelForNode(Node $node) {
		/** @var Label $label */
		foreach ($node->getLabels() as $label) {
			if ($label->getName() === 'Class') {
				if ($node->getProperty('abstract')) {
					return 'abstract class';
				} else {
					return 'class';
				}
			} else if ($label->getName() === 'Interface') {
				return 'interface';
			} else if ($label->getName() === 'Trait') {
				return 'class';
			}
		}
		return 'class';
	}

	private function getOptionsForNode(Node $node) {
		/** @var Label $label */
		foreach ($node->getLabels() as $label) {
			if ($label->getName() === 'Trait') {
				return '(T,#FF7700)';
			}
		}
		return '';
	}

	public function export(ExportConfiguration $configuration) {
		$output = "@startuml\n";

		$q = $this->backend->createQuery('MATCH c WHERE c:Class OR c:Interface OR c:Trait RETURN c', 'c');
		foreach ($q->execute() as $node) {
			$options = $this->getOptionsForNode($node);
			$tag     = '';

			$presentation = $options . ' ' . $tag;
			$presentation = (!empty(trim($presentation))) ? "<< {$presentation} >>" : "";

			$output .= sprintf(
				"%s %s %s {\n",
				$this->getLabelForNode($node),
				$this->processClassName($node->getProperty('fqcn')),
				$presentation
			);

			if ($configuration->isWithProperties()) {
				/** @var Relationship $rel */
				foreach ($node->getRelationships('HAS_PROPERTY', Relationship::DirectionOut) as $rel) {
					$property = $rel->getEndNode();

					$typeRelations = $property->getRelationships('POSSIBLE_TYPE', Relationship::DirectionOut);
					$types         = [];
					/** @var Relationship $typeRelation */
					foreach ($typeRelations as $typeRelation) {
						$types[] = $typeRelation->getEndNode();
					}

					$output .= sprintf(
						"    %s %s%s\n",
						$this->convertNodeVisibility($property),
						$property->getProperty('name'),
						count($types) ? (' : ' . $this->renderTypeList($types)) : ''
					);
				}
			}

			if ($configuration->isWithMethods()) {
				/** @var Relationship $rel */
				foreach ($node->getRelationships('HAS_METHOD', Relationship::DirectionOut) as $rel) {
					$method = $rel->getEndNode();

					$paramRelations   = $method->getRelationships('HAS_PARAMETER', Relationship::DirectionOut);
					$paramDefinitions = [];

					/** @var Relationship $paramRelation */
					foreach ($paramRelations as $paramRelation) {
						$param         = $paramRelation->getEndNode();
						$typeRelations = $param->getRelationships('POSSIBLE_TYPE', Relationship::DirectionOut);
						$types         = [];
						/** @var Relationship $typeRelation */
						foreach ($typeRelations as $typeRelation) {
							$type = $typeRelation->getEndNode();
							if ($type->getProperty('name') !== 'null') {
								$types[] = $type;
							}
						}

						$paramDefinition = $param->getProperty('name');
						if (count($types)) {
							$paramDefinition .= ' : ' . $this->renderTypeList($types);
						}
						$paramDefinitions[] = $paramDefinition;
					}

					$typeRelations = $method->getRelationships('POSSIBLE_TYPE', Relationship::DirectionOut);
					$types         = [];
					/** @var Relationship $typeRelation */
					foreach ($typeRelations as $typeRelation) {
						$type = $typeRelation->getEndNode();
						if ($type->getProperty('name') !== 'null') {
							$types[] = $type;
						}
					}

					$output .= sprintf(
						"    %s %s(%s)%s\n",
						$this->convertNodeVisibility($method),
						$method->getProperty('name'),
						implode(', ', $paramDefinitions),
						count($types) ? (' : ' . $this->renderTypeList($types)) : ''
					);
				}
			}

			$output .= "}\n";
		}

		$q = $this->backend->createQuery(
			'MATCH (a)-[r]->(b) WHERE (a:Class OR a:Interface OR a:Trait) AND (b:Class OR b:Interface OR b:Trait) RETURN a, b, r UNION
			 MATCH (a)-[r]->(t:Type)-[:IS]->(b) WHERE (a:Class OR a:Interface OR a:Trait) AND (b:Class OR b:Interface OR b:Trait) RETURN a, b, r'
		);
		foreach ($q->execute() as $row) {
			$r = $row->relationship('r');

			$arrow = '--';
			if ($r->getType() === 'EXTENDS') {
				$arrow = '--|>';
			} else if ($r->getType() === 'IMPLEMENTS') {
				$arrow = '..|>';
			} else if ($r->getType() === 'USES_TRAIT') {
				$arrow = '..|>';
			} else if ($r->getType() === 'USES') {
				$arrow = '..>';
			}

			$output .=
				$this->processClassName($row->node('a')->getProperty('fqcn')) .
				' ' .
				$arrow .
				' ' .
				$this->processClassName($row->node('b')->getProperty('fqcn')) .
				"\n";
		}

		$output .= "@enduml\n";
		return $output;
	}

	private function renderTypeList($types) {
		return implode(
			'|',
			array_map(
				function (Node $type) {
					return $type->getProperty('name');
				},
				$types
			)
		);
	}

	private function convertNodeVisibility(Node $node) {
		if ($node->getProperty('private')) {
			return '-';
		} elseif ($node->getProperty('protected')) {
			return '#';
		} else {
			return '+';
		}
	}
}