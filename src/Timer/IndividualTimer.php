<?php
namespace Gt\Async\Timer;

class IndividualTimer extends Timer {
	public function __construct(float $triggerTime) {
		parent::__construct();
		$this->addTriggerTime($triggerTime);
	}

	public function addTriggerTime(float $triggerTime):void {
		$this->triggerTimeQueue[] = $triggerTime;
		sort($this->triggerTimeQueue);
	}
}