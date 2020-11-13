<?php
namespace Gt\Async\Timer;

class IndividualTimer extends Timer {
	public function __construct(float $triggerTime = null) {
		parent::__construct();

		if(!is_null($triggerTime)) {
			$this->addTriggerTime($triggerTime);
		}
	}

	public function addTriggerTime(float $triggerTime):void {
		$this->triggerTimeQueue[] = $triggerTime;
		sort($this->triggerTimeQueue);
	}
}