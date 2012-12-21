<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use \SimpleEventLoop\EventLoop;

$app = new EventLoop();

$app->on('blah',function($args){
	print_r($args);
	echo 'blah'.PHP_EOL;
});

$app->once('blah',function($args){
	print_r($args);
	echo 'blah'.PHP_EOL;
});

$app->emit('blah',array('blah','blah2'));
$app->emit('blah',array('blah22','blah33'));

$app->run();
