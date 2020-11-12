<?php
namespace Gt\Async;

class Loop {
	private bool $running;

	public function __construct() {
		$this->running = false;
	}

	public function start():void {
		$this->running = true;
	}

	public function stop():void {
		$this->running = false;
	}

	public function isRunning():bool {
		return $this->running;
	}
}