<?php
namespace Gt\Async;

class Loop {
	private bool $running;
	/** @var Timer[] */
	private array $timerList;

	public function __construct() {
		$this->running = false;
		$this->timerList = [];
	}

	public function start():void {
		$this->running = false;

		foreach($this->timerList as $timer) {
			$this->running = true;
		}
	}

	public function stop():void {
		$this->running = false;
	}

	public function isRunning():bool {
		return $this->running;
	}
}