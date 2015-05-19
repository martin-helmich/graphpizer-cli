<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Persistence\Backend;
use Helmich\Graphizer\Persistence\BulkOperation;
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