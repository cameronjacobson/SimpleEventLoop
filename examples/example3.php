<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use \SimpleEventLoop\EventLoop;

$app = new EventLoop();

$app->on(SIGUSR1,function($args){
	echo '33333 EVENT FIRED'.PHP_EOL;
	print_r($args);
});
$app->once(SIGUSR1,function($args){
	echo '22222 EVENT FIRED'.PHP_EOL;
	print_r($args);
});
$app->once(SIGUSR1,function($args){
	echo '11111 EVENT FIRED'.PHP_EOL;
	print_r($args);
});

$app->emit(SIGUSR1,array(time(),'blah555'));

$app->setInterval(function() use($app) {
	$app->once(SIGUSR1,function($args){
		echo 'INTERVAL EVENT FIRED'.PHP_EOL;
		print_r($args);
	});
	echo 'EMITTING SIGUSR1'.PHP_EOL;
	$app->emit(SIGUSR1,array(time(),'blah555'));
},1);

$app->run();
