<?php
namespace Helmich\Graphizer\Writer;

use Helmich\Graphizer\Persistence\Backend;
use PhpParser\Comment;

class CommentWriter {

	/**
	 * @var Backend
	 */
	private $backend;

	public function __construct(Backend $backend) {
		$this->backend = $backend;
	}

	public function writeComment(Comment $comment) {
		$node = $this->backend->createNode(['text' => $comment->getText(), 'line' => $comment->getLine()], 'Comment');

		if ($comment instanceof Comment\Doc) {
			$this->backend->labelNode($node, 'DocComment');
		}

		return $node;
	}
}