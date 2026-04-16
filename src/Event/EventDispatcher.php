<?php
namespace GT\Async\Event;

class EventDispatcher {
	/** @var array<string, array<int, callable>> */
	private array $subscriberList;

	public function __construct() {
		$this->subscriberList = [];
	}

	public function subscribe(string $eventName, callable $callback):void {
		$this->subscriberList[$eventName] ??= [];
		$this->subscriberList[$eventName][] = $callback;
	}

	public function publish(Event $event):void {
		foreach($this->subscriberList[$event->getName()] ?? [] as $callback) {
			call_user_func($callback, $event);
		}
	}
}
