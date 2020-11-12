<?php
namespace Gt\Async\Test;

use Gt\Async\Loop;
use Gt\Async\Timer\Timer;
use PHPUnit\Framework\TestCase;

class LoopTest extends TestCase {
	public function testRunWithNoTimer() {
		$sut = new Loop();
		$sut->run();
		self::assertEquals(0, $sut->getTriggerCount());
	}

	public function testWaitUntil() {
		$sut = new Loop();
		$epoch = microtime(true);
		$epochPlus50ms = $epoch + 0.05;
		$epochPlus51ms = $epoch + 0.051;

		$sut->waitUntil($epochPlus50ms);
		$epochAfter = microtime(true);
		self::assertGreaterThanOrEqual($epochPlus50ms, $epochAfter);
		self::assertLessThan($epochPlus51ms, $epochAfter);
	}

	public function testWaitUntilNegative() {
		$sut = new Loop();
		$epoch = microtime(true);
		$epochMinus50ms = $epoch - 0.05;
		$tolerance = 0.001; // There should be less than a 0.001s delay.

		$sut->waitUntil($epochMinus50ms);
		$epochAfter = microtime(true);
		self::assertLessThan($tolerance, $epochAfter - $epoch);
	}

	public function testRunWithTimer() {
		$epoch = microtime(true);
		$epochIn10milliseconds = $epoch + 0.01;
		$timer = self::createMock(Timer::class);
		$timer->method("getNextRunTime")
			->willReturn(
				$epochIn10milliseconds,
				null
			);

		$sut = new Loop();
		$sut->addTimer($timer);
		$sut->run();

		self::assertEquals(1, $sut->getTriggerCount());
	}

	public function testRunWithTimerNoNextRunTime() {
		$timer = self::createMock(Timer::class);
		$timer->method("getNextRunTime")
			->willReturn(null);

		$sut = new Loop();
		$sut->addTimer($timer);
		$sut->run();

		self::assertEquals(0, $sut->getTriggerCount());
	}

	public function testRunWithTimerThatTakesLongerThanNextTimerDueTime() {
		$timerCallbacks = [];

		$epoch = microtime(true);
		$epochPlus10ms = $epoch + 0.01;
		$epochPlus20ms = $epoch + 0.02;
		$timer1 = self::createMock(Timer::class);
		$timer1->method("getNextRunTime")
			->willReturn($epochPlus10ms, null);
		$timer1->method("tick")
			->willReturnCallback(function() use (&$timerCallbacks) {
// We are waiting for a tenth of a second,
// which will be longer than the timer2's due time.
				usleep(0.5 * 1_000_000);
				$timerCallbacks[] = "timer1";
			});

		$timer2 = self::createMock(Timer::class);
		$timer2->method("getNextRunTime")
			->willReturn($epochPlus20ms, null);
		$timer2->method("tick")
			->willReturnCallback(function() use (&$timerCallbacks) {
				$timerCallbacks[] = "timer2";
			});

		$sut = new Loop();
		$sut->addTimer($timer1);
		$sut->addTimer($timer2);
		$sut->run();

		self::assertEquals(2, $sut->getTriggerCount());
// The order of the timer callbacks should be 1 first.
		self::assertEquals("timer1", $timerCallbacks[0]);
		self::assertEquals("timer2", $timerCallbacks[1]);
	}

	public function testRunWithTimerThatTakesLongerThanNextTimerDueTimeOutOfOrder() {
		$timerCallbacks = [];

		$epoch = microtime(true);
		$epochPlus10ms = $epoch + 0.01;
		$epochPlus20ms = $epoch + 0.02;
		$timer1 = self::createMock(Timer::class);
		$timer1->method("getNextRunTime")
			->willReturn($epochPlus10ms, null);
		$timer1->method("tick")
			->willReturnCallback(function() use (&$timerCallbacks) {
				usleep(0.5 * 1_000_000);
				$timerCallbacks[] = "timer1";
			});

		$timer2 = self::createMock(Timer::class);
		$timer2->method("getNextRunTime")
			->willReturn($epochPlus20ms, null);
		$timer2->method("tick")
			->willReturnCallback(function() use (&$timerCallbacks) {
				$timerCallbacks[] = "timer2";
			});

		$sut = new Loop();
// Here we add the later timer first. Below we will make sure the order
// is still correct.
		$sut->addTimer($timer2);
		$sut->addTimer($timer1);
		$sut->run();

		self::assertEquals(2, $sut->getTriggerCount());
// The order of the timer callbacks should be 1 first.
		self::assertEquals("timer1", $timerCallbacks[0]);
		self::assertEquals("timer2", $timerCallbacks[1]);
	}
}