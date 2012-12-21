<?php

/**
 * Usage:  start up server, then client
 *  $ php example1.php --server
 *  $ php example1.php
 */

require_once(dirname(__DIR__).'/vendor/autoload.php');

use \SimpleEventLoop\EventLoop;
use \SimpleEventLoop\Stream;

if(@$argv[1] == '--server'){
	// TEST SERVER
	$socket = stream_socket_server("tcp://127.0.0.1:6000", $errno, $errstr);
	if (!$socket) {
		echo "$errstr ($errno)<br />\n";
	} else {
		while ($conn = stream_socket_accept($socket)) {
			$x = fgets($conn);
			fwrite($conn, $x.PHP_EOL);
			fclose($conn);
		}
		fclose($socket);
	}
}
else{
	// TEST CLIENT -- tests 1000 connections
	gc_enable();
	$app = new EventLoop();

	$stream = new Stream($app);

	echo microtime(true).PHP_EOL;
	$stream->once('add',function() use ($stream,$app) {
		for($x=0;$x<1000;$x++){
			$app->setTimeout(function() use($stream,$app,$x){
				$handle = stream_socket_client('tcp://127.0.0.1:6000',$errno,$errstr,30) or die($errno.' '.$errstr.' failed');

				fwrite($handle, microtime(true).PHP_EOL);
				$app->setTimeout(function() use ($stream,$handle,$app,$x) {
					while(!feof($handle)){
						fgets($handle, 1024);
					}
					fclose($handle);
					unset($handle);

					if(!($x%1000)){
						gc_collect_cycles();
					}

					if($x===999){
						echo 'FINISHED: '.microtime(true).PHP_EOL;
						$app->stop();
					}
				},0);
			},0);
		}
		echo microtime(true).PHP_EOL;
	});
	$app->addStream($stream);

	$app->run(true);
}
