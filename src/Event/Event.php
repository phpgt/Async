<?php
namespace GT\Async\Event;

class Event {
	private string $name;
	private mixed $detail;

	public function __construct(string $name, mixed $detail = null) {
		$this->name = $name;
		$this->detail = $detail;
	}

	public function getName():string {
		return $this->name;
	}

	public function getDetail():mixed {
		return $this->detail;
	}
}
