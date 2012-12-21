<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use \SimpleEventLoop\EventLoop;

$app = new EventLoop();

$closure = function($value) use($app) {
	$app->setInterval(function(){},0.01);
	return function() use($value){
//		echo $value.PHP_EOL;
	};
};

$app->setTimeout($closure('timeout'),0.001);
$app->setInterval($closure('interval'),0.002);
$app->setInterval(function() use ($app) {
	echo 'MEMORY: '.memory_get_usage(true).PHP_EOL;
},2);

$app->run();
