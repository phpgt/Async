<?php
namespace Gt\Async;

use Gt\Async\Timer\Timer;

/**
 * The core event loop class, used to dispatch all events via different added
 * Timer objects.
 *
 * For efficiency, when the loop's run function is called, timers are sorted
 * by their next run time, and the script is delayed by that amount of time,
 * rather than wasting CPU cycles in an infinite loop.
 */
class Loop {
	/** @var Timer[] */
	private array $timerList;
	private int $triggerCount;
	/** @var callable Function that delays execution by (int $milliseconds) */
	private $sleepFunction;

	public function __construct() {
		$this->timerList = [];
		$this->triggerCount = 0;
		$this->sleepFunction = "usleep";
	}

	public function addTimer(Timer $timer):void {
		$this->timerList [] = $timer;
	}

	public function setSleepFunction(callable $sleepFunction):void {
		$this->sleepFunction = $sleepFunction;
	}

	public function run():void {
		do {
			$numTriggered = $this->triggerNextTimers();
			$this->triggerCount += $numTriggered;
		}
		while($numTriggered > 0);
	}

	public function getTriggerCount():int {
		return $this->triggerCount;
	}

	public function waitUntil(float $waitUntilEpoch):void {
		$epoch = microtime(true);
		$diff = $waitUntilEpoch - $epoch;
		if($diff <= 0) {
			return;
		}

		call_user_func(
			$this->sleepFunction,
			$diff * 1_000_000
		);
	}

// TODO: The epochList is a perfect candidate for one of SPL's Iterators.
// Probably the MultipleIterator...
	public function getTimerOrder() {
		$epochList = [];

// Create a list of all timers that have a next run time.
		foreach($this->timerList as $timer) {
			if($epoch = $timer->getNextRunTime()) {
				$epochList[] = [$epoch, $timer];
			}
		}

// Sort the epoch list so that they are in order of next run time.
		usort(
			$epochList,
			fn($a, $b) => $a[0] < $b[0] ? -1 : 1
		);

		return $epochList;
	}

	private function triggerNextTimers():int {
		$epochList = $this->getTimerOrder();
// If there are no more timers to run, return early.
		if(empty($epochList)) {
			return 0;
		}

// Wait until the first epoch is due, then trigger the timer.
		$this->waitUntil($epochList[0][0]);
		$this->trigger($epochList[0][1]);
		$triggered = 1;

// Triggering the timer may have caused time to pass so that
// other timers are now due.
		array_shift($epochList);
		$triggered += $this->executeAllReadyTimers($epochList);
		return $triggered;
	}

	private function trigger(Timer $timer):void {
		$timer->tick();
	}

	/** @param array[] $epochList [$epoch, $timer] */
	private function executeAllReadyTimers(array $epochList):int {
$now = microtime(true);
		$triggered = 0;

		while(isset($epochList[0])
		&& $epochList[0][0] <= $now) {
			$this->trigger($epochList[0][1]);
			array_shift($epochList);
			$triggered++;

$now = microtime(true);
		}

		return $triggered;
	}
}