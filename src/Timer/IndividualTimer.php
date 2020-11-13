<?php
namespace Gt\Async\Timer;

class IndividualTimer extends Timer {
	/**
	 * @param float $triggerSeconds The number of seconds in the future
	 * that the timer will trigger. To set an absolute time,
	 * use addTriggerTime().
	 */
	public function __construct(float $triggerSeconds = null) {
		parent::__construct();

		if(!is_null($triggerSeconds)) {
			$this->addTriggerTime(
				call_user_func($this->timeFunction)
				+ $triggerSeconds
			);
		}
	}

	/**
	 * @param float $triggerTime The unix epoch of when to trigger.
	 */
	public function addTriggerTime(float $triggerTime):void {
		$this->triggerTimeQueue[] = $triggerTime;
		sort($this->triggerTimeQueue);
	}
}