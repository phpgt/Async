<?php
namespace Gt\Async\Test\Event;

use Gt\Async\Event\Event;
use Gt\Async\Event\EventDispatcher;
use PHPUnit\Framework\TestCase;

class EventDispatcherTest extends TestCase {
	public function testPublishInvokesSubscribedCallbackWithEventObject():void {
		$receivedEvent = null;

		$sut = new EventDispatcher();
		$sut->subscribe("tick", function(Event $event) use(&$receivedEvent) {
			$receivedEvent = $event;
		});

		$event = new Event("tick");
		$sut->publish($event);

		self::assertSame($event, $receivedEvent);
	}

	public function testPublishPassesEventDataToSubscriber():void {
		$receivedDetail = null;

		$sut = new EventDispatcher();
		$sut->subscribe("message", function(Event $event) use(&$receivedDetail) {
			$receivedDetail = $event->getDetail();
		});

		$sut->publish(new Event("message", "hello"));

		self::assertSame("hello", $receivedDetail);
	}

	public function testPublishInvokesAllSubscribersForMatchingEventInSubscriptionOrder():void {
		$callOrder = [];

		$sut = new EventDispatcher();
		$sut->subscribe("tick", function() use(&$callOrder) {
			$callOrder[] = "first";
		});
		$sut->subscribe("tick", function() use(&$callOrder) {
			$callOrder[] = "second";
		});

		$sut->publish(new Event("tick"));

		self::assertSame(["first", "second"], $callOrder);
	}

	public function testPublishOnlyInvokesSubscribersForMatchingEventName():void {
		$tickCount = 0;
		$messageCount = 0;

		$sut = new EventDispatcher();
		$sut->subscribe("tick", function() use(&$tickCount) {
			$tickCount++;
		});
		$sut->subscribe("message", function() use(&$messageCount) {
			$messageCount++;
		});

		$sut->publish(new Event("tick"));

		self::assertSame(1, $tickCount);
		self::assertSame(0, $messageCount);
	}

	public function testPublishWithNoSubscribersDoesNothing():void {
		$this->expectNotToPerformAssertions();

		$sut = new EventDispatcher();
		$sut->publish(new Event("tick"));
	}
}
