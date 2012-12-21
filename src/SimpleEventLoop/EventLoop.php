<?php

namespace SimpleEventLoop;

use SimpleEventLoop\EventTrait;
use SimpleEventLoop\Stream;

class EventLoop
{
	use EventTrait;

	public $base;
	private $events;
	private $deleted;

	public function __construct(){
		$this->base = event_base_new();
		$this->deleted = new \SplQueue();
		$this->streams = new \SplQueue();
		$this->iteratorMode = \SplDoublyLinkedList::IT_MODE_FIFO | \SplDoublyLinkedList::IT_MODE_DELETE;
		$this->deleted->setIteratorMode($this->iteratorMode);
	}

	public function setTimeout(callable $callback, $seconds){
        $this->setTimer($callback, $seconds);
	}

	public function setInterval(callable $callback, $seconds){
        $this->setTimer($callback, $seconds, true);
	}

	protected function setTimer($callback,$seconds,$persist = false){
        $event = $this->createEvent();
		$event->callback = $callback;
		$event->seconds = $seconds;
		$event->persist = $persist;

		event_timer_set($event->resource, $this->generateCallback($event));
		event_base_set($event->resource, $this->base);
		$this->dispatchEvent($event);
	}

	protected function generateCallback($event){
		return function() use ($event) {
			call_user_func($event->callback);
			if($event->persist){
				$this->dispatchEvent($event);
			}
			else{
				event_del($event->resource);
				$this->deleted->enqueue($event->hash);
			}
		};
	}

	protected function setSignal($signalNo, $callback, $persist = true){
		$event = $this->createEvent();
		$event->callback = $callback;
		$event->signalNo = $signalNo;
		$event->persist = $persist;
		$flags = $persist ? EV_SIGNAL|EV_PERSIST : EV_SIGNAL;
		event_set($event->resource, $signalNo, $flags, $this->generateCallback($event));
		event_base_set($event->resource, $this->base);
		$this->dispatchEvent($event);
	}

	protected function dispatchEvent($event){
		$seconds = isset($event->seconds) ? abs($event->seconds) * 1000000 : -1;
		event_add($event->resource,$seconds);
	}

	protected function createEvent(){

		if(count($this->deleted) > 0){
			$deleted = $this->deleted->dequeue();
			event_free($this->events[$deleted]->resource);
			unset($this->events[$deleted]);
		}

		$hash = spl_object_hash($event = new \stdClass());

		$event->resource = event_new();
		$event->hash = $hash;

		$this->events[$hash] = $event;
		return $event;
	}

	public function addStream($stream){
		$this->streams->enqueue($stream);
	}

	protected function streamHandler($stream){
		return function() use ($stream) {
			$timeout = $stream->defaultTimeout();
			if($stream->isReady()){
				$stream->handle();
				$timeout = 0;
			}
			$this->setTimeout($this->streamHandler($stream), $timeout);
		};
	}

	protected function initSocketTimer(){
		return function() {
			$timeout = count($this->streams) ? 0 : 0;
			while(count($this->streams)){
				$stream = $this->streams->dequeue();
				$stream->emit("add");
//				$this->setTimeout($this->streamHandler($stream), 0);
			}
			$this->setTimeout($this->initSocketTimer(), $timeout);
		};
	}

	public function run($withStreams = false){
		$this->withStreams = $withStreams || count($this->streams);
		$this->setTimeout(function(){
			if($this->withStreams){
				$this->setTimeout($this->initSocketTimer(), 0);
			}
		},0);
		event_base_loop($this->base);
	}

	public function stop(){
		event_base_loopexit($this->base);
	}

}
