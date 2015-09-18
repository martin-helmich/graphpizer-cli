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

namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Persistence\BulkOperation;
use Helmich\Graphizer\Persistence\Op\CreateNode;
use Helmich\Graphizer\Persistence\Op\NodeMatcher;
use PhpParser\Comment;

class CommentWriter {

	/**
	 * @param Comment       $comment
	 * @param BulkOperation $bulk
	 * @return NodeMatcher
	 */
	public function writeComment(Comment $comment, BulkOperation $bulk) {
		$properties = ['text' => $comment->getText(), 'line' => $comment->getLine()];
		$commentOp  = new CreateNode('Comment', $properties);

		if ($comment instanceof Comment\Doc) {
			$commentOp->addLabel('DocComment');
		}

		$bulk->push($commentOp);

		return $commentOp;
	}
}