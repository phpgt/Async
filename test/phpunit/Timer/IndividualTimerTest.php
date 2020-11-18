<?php
namespace Gt\Async\Test\Timer;

use Gt\Async\Timer\IndividualTimer;
use PHPUnit\Framework\TestCase;

class IndividualTimerTest extends TestCase {
	public function testConstructWithFutureTime() {
		$epoch = microtime(true);
		$epochPlus5s = $epoch + 5;
		$sut = new IndividualTimer(5);
		self::assertEquals(
// The timer must be scheduled within one hundredth of a second of the expectation:
			round($epochPlus5s, 2),
			round($sut->getNextRunTime(), 2)
		);
	}

	public function testConstructWithPastTime() {
		$epoch = microtime(true);
		$epochMinus5s = $epoch - 5;
		$sut = new IndividualTimer(-5);
		self::assertEquals(
			round($epochMinus5s, 2),
			round($sut->getNextRunTime(), 2)
		);
	}

	public function testTickWithFutureTime() {
		$sut = new IndividualTimer(100);
		self::assertFalse($sut->tick());
	}

	public function testTickWithPastTime() {
		$sut = new IndividualTimer(-100);
		self::assertTrue($sut->tick());
	}

	public function testIsScheduledFalseAfterRunning() {
		$sut = new IndividualTimer(0);
		self::assertTrue($sut->isScheduled());
		$sut->tick();
		self::assertFalse($sut->isScheduled());
	}

	public function testIsScheduledTrueAfterRunningMultipleScheduled() {
		$sut = new IndividualTimer(1);
		$sut->addTriggerTime(2);
		$sut->addTriggerTime(3);
		self::assertTrue($sut->isScheduled());
		$sut->tick();
		$sut->tick();
		$sut->tick();
// This should always remain true while the current epoch is earlier than the scheduled time(s).
		self::assertTrue($sut->isScheduled());
	}

	public function testIsScheduledFalseAfterRunningMultipleScheduledAndTimeAdvances() {
		$epoch = 1000;

		$sut = new IndividualTimer();
		$sut->setTimeFunction(function() use(&$epoch) { return ++$epoch; });
		$sut->addTriggerTime(1001);
		$sut->addTriggerTime(1002);
		$sut->addTriggerTime(1003);
		self::assertTrue($sut->isScheduled());
		$sut->tick();
		self::assertTrue($sut->isScheduled());
		$sut->tick();
		self::assertTrue($sut->isScheduled());
		$sut->tick();
// After ticking three times, the internal epoch should advance past the point of the last trigger.
		self::assertFalse($sut->isScheduled());
	}

	public function testAddCallbackNotTriggeredInFuture() {
		$callbackCount = 0;

		$sut = new IndividualTimer(1);
		$sut->addCallback(function() use(&$callbackCount) {
			$callbackCount++;
		});
		$sut->tick();
		self::assertEquals(0, $callbackCount);
	}

	public function testAddCallbackTriggeredInPast() {
		$callbackCount = 0;

		$sut = new IndividualTimer(0);
		$sut->addCallback(function() use(&$callbackCount) {
			$callbackCount++;
		});
		$sut->tick();
		self::assertEquals(1, $callbackCount);
	}

	public function testAddCallbackTriggeredAtCorrectTime() {
		$callbackCount = 0;

		$epoch = 1000;
		$sut = new IndividualTimer();
		$sut->addTriggerTime($epoch);
		$sut->addTriggerTime($epoch + 1);
		$sut->addTriggerTime($epoch + 2);
		$sut->addTriggerTime($epoch + 5);
		$sut->setTimeFunction(function() use(&$epoch) {
			return $epoch++;
		});

		$sut->addCallback(function() use(&$callbackCount) {
			$callbackCount++;
		});

		$sut->tick(); // 1000
		self::assertEquals(1, $callbackCount);
		$sut->tick(); // 1001
		self::assertEquals(2, $callbackCount);
		$sut->tick(); // 1002
		self::assertEquals(3, $callbackCount);
		$sut->tick(); // 1003
		self::assertEquals(3, $callbackCount);
		$sut->tick(); // 1004
		self::assertEquals(3, $callbackCount);
		$sut->tick(); // 1005
		self::assertEquals(4, $callbackCount);
		$sut->tick(); // 1006
		self::assertEquals(4, $callbackCount);
	}

	public function testRemoveCallback() {
		$exampleCallbackCount = 0;
		$exampleCallback = function() use(&$exampleCallbackCount) {
			$exampleCallbackCount++;
		};

		$epoch = 1000;
		$sut = new IndividualTimer();
		$sut->addTriggerTime($epoch);
		$sut->addTriggerTime($epoch + 1);
		$sut->setTimeFunction(function() use(&$epoch) {
			return $epoch++;
		});

		$sut->addCallback($exampleCallback);
		$sut->tick();
		$sut->removeCallback($exampleCallback);
		$sut->tick();
		self::assertEquals(1, $exampleCallbackCount);
	}
}