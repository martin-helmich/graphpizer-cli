<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Persistence\Op\CreateNode;
use Helmich\Graphizer\Persistence\Op\NodeMatcher;
use PhpParser\Comment;

class CommentWriter {

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	/**
	 * @param Comment $comment
	 * @param Bulk    $bulk
	 * @return NodeMatcher
	 */
	public function writeComment(Comment $comment, Bulk $bulk) {
//		$id = uniqid('node');

//		$label = 'Comment';
//		if ($comment instanceof Comment\Doc) {
//			$label .= ':DocComment';
//		}

		$properties = ['text' => $comment->getText(), 'line' => $comment->getLine()];
		$commentOp = new CreateNode('Comment', $properties);

		if ($comment instanceof Comment\Doc) {
			$commentOp->addLabel('DocComment');
		}

		$bulk->push($commentOp);

//		$cypher = "CREATE ({$id}:{$label} {prop_{$id}})";
//		$bulk->push($cypher, ["prop_{$id}" => $properties]);

		return $commentOp;

//		$node = $this->backend->createNode(['text' => $comment->getText(), 'line' => $comment->getLine()], 'Comment');
//
//		if ($comment instanceof Comment\Doc) {
//			$this->backend->labelNode($node, 'DocComment');
//		}
//
//		return $node;
	}
}