<?php
namespace Gt\Async;

class Loop {
	/** @var Timer[] */
	private array $timerList;
	private int $triggerCount;

	public function __construct() {
		$this->timerList = [];
		$this->triggerCount = 0;
	}

	public function run():void {
		foreach($this->timerList as $timer) {
		}
	}

	public function getTriggerCount():int {
		return $this->triggerCount;
	}
}