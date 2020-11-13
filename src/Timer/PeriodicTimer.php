<?php
namespace Gt\Async\Timer;

class PeriodicTimer extends Timer {
	const TRIGGER_POOL_SIZE = 1_000;

	private float $period;
	private bool $immediate;

	/**
	 * @param float $period The number of seconds between each tick trigger.
	 * @param bool $immediate Set to true to have the first tick trigger
	 * immediately, followed by the period of delay. Set to false to wait
	 * the period of delay before the first tick trigger.
	 */
	public function __construct(float $period, bool $immediate = false) {
		parent::__construct();

		$this->period = $period;
		$this->immediate = $immediate;
	}

	public function isScheduled():bool {
		$this->scheduleTriggerPool();
		return parent::isScheduled();
	}

	public function getNextRunTime():?float {
		$this->scheduleTriggerPool();
		return parent::getNextRunTime();
	}

	private function scheduleTriggerPool():void {
		$now = call_user_func($this->timeFunction);
		$queueSize = count($this->triggerTimeQueue);

		if($queueSize === 0
		&& $this->immediate) {
			$this->triggerTimeQueue[] = $now;
			$this->immediate = false;
		}

		for($i = 0; $i < self::TRIGGER_POOL_SIZE - $queueSize; $i++) {
			$this->triggerTimeQueue[] = $now + ((1 + $i) * $this->period);
		}
	}
}