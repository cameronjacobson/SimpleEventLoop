<?php

namespace SimpleEventLoop;

trait EventTrait
{
	private $observers;
	private $once;
	private $iteratorMode;
	private $signalHandler;
	private $signalData;

	public function __construct(){
		$this->once = array();
		$this->signalHandler = array();
		$this->signalData = array();
	}

	protected function onSignal($eventName,$persist){
		$persist = (int)(bool)$persist;
		if(empty($this->signalHandler[$eventName][$persist])){
			$this->signalHandler[$eventName][$persist] = function() use($eventName,$persist) {
				$arguments = count($this->signalData[$eventName][$persist])<1 
					? array() 
					: $this->signalData[$eventName][$persist]->dequeue();
				$this->emit('__signal'.$persist.$eventName, $arguments);
				if(!$persist){
					unset($this->signalHandler[$eventName][$persist]);
				}
			};
			$this->setSignal($eventName,$this->signalHandler[$eventName][$persist],$persist);
		}
	}

	protected function emitSignal($eventName,$arguments = array()){
		// key [0] and [1] equal [NOT_PERSISTENT] / [PERSISTENT] respectively
		if(!isset($this->signalData[$eventName])){
			$this->signalData[$eventName][0] = new \SplQueue();
			$this->signalData[$eventName][0]->setIteratorMode($this->iteratorMode);
			$this->signalData[$eventName][1] = new \SplQueue();
			$this->signalData[$eventName][1]->setIteratorMode($this->iteratorMode);
		}
		if(!empty($this->signalHandler[$eventName][0])){
			$this->signalData[$eventName][0]->enqueue($arguments);
		}
		if(!empty($this->signalHandler[$eventName][1])){
			$this->signalData[$eventName][1]->enqueue($arguments);
		}
		if(!empty($this->signalHandler[$eventName][0]) 
			|| !empty($this->signalHandler[$eventName][1])){
			posix_kill(posix_getpid(), $eventName);
			pcntl_signal_dispatch();
		}
	}

	public function emit($eventName,$arguments = array()){
		if(is_int($eventName)){
			$this->setTimeout(function() use($eventName,$arguments){
				$this->emitSignal($eventName,$arguments);
			},0);
		}
		else{
			if(!empty($this->observers[$eventName])){
				foreach($this->observers[$eventName] as $func){
					$this->setTimeout($func($arguments),0);
				}
			}
			if(!empty($this->once[$eventName])){
				while(count($this->once[$eventName]) > 0){
					$func = $this->once[$eventName]->dequeue();
					$this->setTimeout($func($arguments),0);
				}
			}
		}
	}

	public function on($eventName, $callback) {
		if(is_int($eventName)){
			$this->onSignal($eventName,true);
			$eventName = '__signal1'.$eventName;
		}
		$this->observers[$eventName][] = function($arguments) use ($callback) {
			return function() use($callback,$arguments){
				call_user_func($callback,$arguments);
			};
		};
	}

	public function once($eventName, $callback) {
		if(is_int($eventName)){
			$this->onSignal($eventName,false);
			$eventName = '__signal0'.$eventName;
		}
		if(empty($this->once[$eventName])){
			$this->once[$eventName] = new \SplQueue();
			$this->once[$eventName]->setIteratorMode($this->iteratorMode);
		}
		$cb = function($arguments) use($callback) {
			return function() use($callback,$arguments){
				call_user_func($callback,$arguments);
			};
		};
		$this->once[$eventName]->enqueue($cb);
	}

	public function removeListener(){

	}

	public function removeAllListeners($eventName){
        if ($eventName !== null) {
            unset($this->listeners[$eventName]);
        } else {
            $this->listeners = array();
        }
	}

	public function listeners($eventName){
		return $this->observers[$eventName] ?: array();
	}

}
