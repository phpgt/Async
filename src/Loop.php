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

	public function __construct() {
		$this->timerList = [];
		$this->triggerCount = 0;
	}

	public function addTimer(Timer $timer):void {
		$this->timerList [] = $timer;
	}

	public function run():void {
		$epochList = [];

		// Create a list of all timers that have a next run time.
		foreach($this->timerList as $timer) {
			if($epoch = $timer->getNextRunTime()) {
				$epochList[] = [$epoch, $timer];
			}
		}

		// If there are no more timers to run, return early.
		// Returning here ends the recursive call.
		if(empty($epochList)) {
			return;
		}

		// Sort the epoch list so that they are in order of next run time.
		uasort(
			$epochList,
			fn($a, $b) => $a[0] < $b[0] ? -1 : 1
		);

		// Wait until the first epoch is due, then trigger the timer.
		$this->waitUntil($epochList[0][0]);
		$this->trigger($epochList[0][1]);

		// Triggering the timer may have caused time to pass so that
		// other timers are now due.
		array_shift($epochList);
		$this->executeAllReadyTimers($epochList);

		// The recursive call will continue forever until there are
		// no timers left with a next run time.
		$this->run();
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

		usleep($diff * 1_000_000);
	}

	private function trigger(Timer $timer):void {
		$timer->tick();
		$this->triggerCount++;
	}

	/** @param array[] $epochList [$epoch, $timer] */
	private function executeAllReadyTimers(array $epochList):void {
		while(isset($epochList[0])
		&& $epochList[0][0] <= microtime(true)) {
			$this->trigger($epochList[0][1]);
			array_shift($epochList);
		}
	}
}