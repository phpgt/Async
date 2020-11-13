<?php
namespace Gt\Async\Test\Timer;

use Gt\Async\Timer\IndividualTimer;
use PHPUnit\Framework\TestCase;

class IndividualTimerTest extends TestCase {
	public function testConstructWithFutureTime() {
		$epoch = microtime(true);
		$epochPlus50ms = $epoch + 0.05;
		$sut = new IndividualTimer($epochPlus50ms);
		self::assertEquals($epochPlus50ms, $sut->getNextRunTime());
	}

	public function testConstructWithPastTime() {
		$epoch = microtime(true);
		$epochMinus50ms = $epoch - 0.05;
		$sut = new IndividualTimer($epochMinus50ms);
		self::assertEquals($epochMinus50ms, $sut->getNextRunTime());
	}

	public function testTickWithFutureTime() {
		$sut = new IndividualTimer(microtime(true) + 1);
		self::assertFalse($sut->tick());
	}

	public function testTickWithPastTime() {
		$sut = new IndividualTimer(microtime(true) - 1);
		self::assertTrue($sut->tick());
	}

	public function testIsScheduledFalseAfterRunning() {
		$sut = new IndividualTimer(microtime(true));
		self::assertTrue($sut->isScheduled());
		$sut->tick();
		self::assertFalse($sut->isScheduled());
	}

	public function testIsScheduledTrueAfterRunningMultipleScheduled() {
		$sut = new IndividualTimer(microtime(true) + 1);
		$sut->addTriggerTime(microtime(true) + 2);
		$sut->addTriggerTime(microtime(true) + 3);
		self::assertTrue($sut->isScheduled());
		$sut->tick();
		$sut->tick();
		$sut->tick();
// This should always remain true while the current epoch is earlier than the scheduled time(s).
		self::assertTrue($sut->isScheduled());
	}

	public function testIsScheduledFalseAfterRunningMultipleScheduledAndTimeAdvances() {
		$epoch = 1000;

		$sut = new IndividualTimer(1001);
		$sut->setTimeFunction(function() use(&$epoch) { return ++$epoch; });
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

		$epoch = 1000;
		$sut = new IndividualTimer(1001);
		$sut->setTimeFunction(fn() => $epoch);
		$sut->addCallback(function() use(&$callbackCount) {
			$callbackCount++;
		});
		$sut->tick();
		self::assertEquals(0, $callbackCount);
	}

	public function testAddCallbackTriggeredInPast() {
		$callbackCount = 0;

		$epoch = 1000;
		$sut = new IndividualTimer(999);
		$sut->setTimeFunction(fn() => $epoch);
		$sut->addCallback(function() use(&$callbackCount) {
			$callbackCount++;
		});
		$sut->tick();
		self::assertEquals(1, $callbackCount);
	}

	public function testAddCallbackTriggeredAtCorrectTime() {
		$callbackCount = 0;

		$epoch = 1000;
		$sut = new IndividualTimer($epoch);
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
}