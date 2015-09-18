<?php
/*
 * GraPHPizer source code analytics engine (cli component)
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

namespace Helmich\Graphizer\Reader;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use PhpParser\Comment;
use PhpParser\Comment\Doc;

class CommentReader {

	public function readComments(\Traversable $nodes){
		$comments = [];

		foreach($nodes as $node) {
			$comments[] = $this->readComment($node);
		}

		return $comments;
	}

	public function readComment(Node $commentNode) {
		if ($this->isDocComment($commentNode)) {
			return new Doc(
				$commentNode->getProperty('text'),
				$commentNode->getProperty('line')
			);
		} else {
			return new Comment(
				$commentNode->getProperty('text'),
				$commentNode->getProperty('line')
			);
		}
	}

	private function isDocComment(Node $commentNode) {
		foreach ($commentNode->getLabels() as $label) {
			/** @var Label $label */
			if ($label->getName() === 'DocComment') {
				return TRUE;
			}
		}
		return FALSE;
	}
}