<?php
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