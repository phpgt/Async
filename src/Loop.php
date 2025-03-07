<?php
namespace Gt\Async;

use Gt\Async\Timer\Timer;
use Gt\Async\Timer\TimerOrder;
use Gt\Promise\Deferred;

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
	/** @var callable Function that delays execution by (float $seconds) */
	private $sleepFunction;
	/** @var callable Function that delivers the current time in milliseconds as a float */
	private $timeFunction;
	private bool $forever;
	private bool $haltWhenAllDeferredComplete;
	/** @var Deferred[] */
	private array $activeDeferred;
	/** @var callable[] */
	private array $haltCallbackList;

	public function __construct() {
		$this->timerList = [];
		$this->triggerCount = 0;
		$this->sleepFunction = function(float $seconds):void {
			usleep((int)($seconds * 1_000_000));
		};
		$this->timeFunction = function():float {
			return microtime(true);
		};
		$this->haltWhenAllDeferredComplete = false;
		$this->activeDeferred = [];
		$this->haltCallbackList = [];
	}

	public function addTimer(Timer $timer):void {
		$this->timerList [] = $timer;
	}

	public function addDeferredToTimer(
		Deferred $deferred,
		?Timer $timer = null
	):void {
		$timer = $timer ?? $this->timerList[0];

		$deferred->onComplete(
			function() use ($deferred, $timer) {
				$this->removeDeferredFromTimer(
					$deferred,
					$timer
				);
			});

		foreach($deferred->getProcessList() as $function) {
			$timer->addCallback($function);
		}

		$this->activeDeferred[] = $deferred;
	}


	public function removeDeferredFromTimer(
		Deferred $deferred,
		?Timer $timer = null
	):void {
		$timer = $timer ?? $this->timerList[0];

		foreach($deferred->getProcessList() as $function) {
			$timer->removeCallback($function);
		}
		$activeDeferredIndex = array_search(
			$deferred,
			$this->activeDeferred
		);
		if($activeDeferredIndex !== false) {
			unset($this->activeDeferred[$activeDeferredIndex]);
		}
		if($this->haltWhenAllDeferredComplete
		&& empty($this->activeDeferred)) {
			$this->halt();
		}
	}

	public function setSleepFunction(callable $sleepFunction):void {
		$this->sleepFunction = $sleepFunction;
	}

	public function setTimeFunction(callable $timeFunction):void {
		$this->timeFunction = $timeFunction;
	}

	public function run(bool $forever = true):void {
		$this->forever = $forever;

		do {
			$numTriggered = $this->triggerNextTimers();
			$this->triggerCount += $numTriggered;
		}
		while($numTriggered > 0 && $this->forever);
	}

	public function halt():void {
		$this->forever = false;

		foreach($this->haltCallbackList as $callback) {
			call_user_func($callback);
		}
	}

	public function haltWhenAllDeferredComplete(
		bool $shouldHalt = true
	):void {
		$this->haltWhenAllDeferredComplete = $shouldHalt;
	}

	public function addHaltCallback(callable $callback):void {
		array_push($this->haltCallbackList, $callback);
	}

	public function getTriggerCount():int {
		return $this->triggerCount;
	}

	public function waitUntil(float $waitUntilEpoch):void {
		$epoch = call_user_func($this->timeFunction);
		$diff = $waitUntilEpoch - $epoch;
		if($diff <= 0) {
			return;
		}

		call_user_func($this->sleepFunction, $diff);
	}

	private function triggerNextTimers():int {
		$timerOrder = new TimerOrder($this->timerList);

// If there are no more timers to run, return early.
		if(count($timerOrder) === 0) {
			return 0;
		}

// Wait until the first epoch is due, then trigger the timer.
		$this->waitUntil($timerOrder->getCurrentEpoch());
		$this->trigger($timerOrder->getCurrentTimer());

// Triggering the timer may have caused time to pass so that
// other timers are now due.
		$timerOrder->next();
		$timerOrderReady = $timerOrder->subset();
		$this->executeTimers($timerOrderReady);

// This function will always execute at least 1 timer, it will always wait for
// the next one to trigger, but could have triggered more during the wait for
// the first Timer's execution.
		return 1 + count($timerOrderReady);
	}

	private function trigger(Timer $timer):void {
		$timer->tick();
	}

	private function executeTimers(TimerOrder $timerOrder):void {
		foreach($timerOrder as $item) {
			$this->trigger($item["timer"]);
		}
	}
}
