<?php
require_once __DIR__ . '/../vendor/autoload.php';

$lexer = new \PhpParser\Lexer();
$parser = new \PhpParser\Parser($lexer);

$file = file_get_contents(__DIR__ . '/Hydrator/NodeWriter.php');
$tree = $parser->parse($file);

//echo (new \PhpParser\NodeDumper())->dump($tree);

$client = new \Everyman\Neo4j\Client();
$client->getTransport()->setAuth('neo4j', 'martin123');

$backend = new \Helmich\Graphizer\Persistence\Backend($client);
$backend->wipe();

$converter = new \Helmich\Graphizer\Writer\NodeWriter($client, $backend);
$nodes = $converter->writeNodeCollection($tree);

$modeler = new \Helmich\Graphizer\Modeler\ClassModelGenerator($client, $backend);
$modeler->run();

//var_dump($nodes);