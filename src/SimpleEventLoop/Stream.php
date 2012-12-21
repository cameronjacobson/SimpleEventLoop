<?php

namespace SimpleEventLoop;

use SimpleEventLoop\EventTrait;

class Stream
{
	use EventTrait;

	public $handle;
	private $base;
	private $events;
	private $deleted;

	public function __construct(&$loop){
		$this->base = $loop->base;
		$this->deleted = new \SplQueue();
		$this->iteratorMode = \SplDoublyLinkedList::IT_MODE_FIFO | \SplDoublyLinkedList::IT_MODE_DELETE;
		$this->deleted->setIteratorMode($this->iteratorMode);
	}

	public function defaultTimeout(){
		return 0;
	}

	public function isReady(){
		
	}

	public function handle(){
		
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

}
