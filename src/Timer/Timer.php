<?php
namespace Gt\Async\Timer;

use Gt\Async\Asyncable;

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
	/** @var callable Function that delivers the current time in milliseconds as a float */
	protected $timeFunction;

	public function __construct() {
		$this->triggerTimeQueue = [];
		$this->callbackList = [];
		$this->timeFunction = fn() => microtime(true);
	}

	public function setTimeFunction(callable $callable):void {
		$this->timeFunction = $callable;
	}

	public function addCallback(callable $callback):void {
		$this->callbackList[] = $callback;
	}

	public function removeCallback(callable $callback):void {
// TODO: Throw exception if it doesn't exist.
		$callbackIndex = array_search($callback, $this->callbackList);
		unset($this->callbackList[$callbackIndex]);
	}

	public function isScheduled():bool {
		return !empty($this->triggerTimeQueue);
	}

	public function getNextRunTime():?float {
		return $this->triggerTimeQueue[0] ?? null;
	}

	/**
	 * @return bool True if the timer ticks (it was due). False if the tick
	 * doesn't occur (is was not due).
	 */
	public function tick():bool {
		$now = call_user_func($this->timeFunction);

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