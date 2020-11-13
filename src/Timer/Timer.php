<?php
namespace Gt\Async\Timer;

/**
 * Represents one or more trigger times. If the tick function is called when
 * a timer is due, it will execute the timer's callback(s).
 *
 * The Timer's tick function could be called in an infinite loop, or for better
 * use of CPU cycles, sleep for the duration until the Timer's next run time.
 */
abstract class Timer {
	/** @var float[] */
	protected array $triggerTimeQueue;
	/** @var callable[] */
	protected array $callbackList;

	public function __construct() {
		$this->triggerTimeQueue = [];
		$this->callbackList = [];
	}

//	public function addCallback(callable $callback):void {
//		$this->callbackList[] = $callback;
//	}

	public function getNextRunTime():?float {
		return $this->triggerTimeQueue[0] ?? null;
	}

	/**
	 * @return bool True if the timer ticks (it was due). False if the tick
	 * doesn't occur (is was not due).
	 */
	public function tick():bool {
		$now = microtime(true);

		do {
			$triggerTime = $this->triggerTimeQueue[0] ?? null;
			if(is_null($triggerTime)
			|| $triggerTime > $now) {
				return false;
			}

			$this->executeCallbacks();
			array_shift($this->triggerTimeQueue);
		}
		while(isset($this->triggerTimeQueue[0])
		&& $this->triggerTimeQueue[0] <= $now);

		return true;
	}

	private function executeCallbacks():void {
		foreach($this->callbackList as $callback) {
			call_user_func($callback);
		}
	}
}