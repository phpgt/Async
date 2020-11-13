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
		$actualDelay = null;

		$sut = new Loop();
		$sut->setSleepFunction(function(int $milliseconds) use (&$actualDelay) {
			$actualDelay = $milliseconds;
		});

		$epoch = microtime(true);
		$epochPlus5s = $epoch + 5;
		$sut->waitUntil($epochPlus5s);
		self::assertEquals(
			round(5_000_000 / 100),
// Check that the delayed time is within a threshold of 1/10,000 of a second:
			round($actualDelay / 100)
		);
	}

	public function testWaitUntilNegative() {
		$numCalls = 0;

		$sut = new Loop();
		$sut->setSleepFunction(function() use (&$numCalls) {
			$numCalls++;
		});

		$epoch = microtime(true);
		$epochMinus5s = $epoch - 5;

// Because the delay time is in the past, the sleep function should never be called.
		$sut->waitUntil($epochMinus5s);
		self::assertEquals(0, $numCalls);
	}

	public function testRunWithTimer() {
		$epoch = microtime(true);
		$timer = self::createMock(Timer::class);
		$timer->method("getNextRunTime")
			->willReturn(
				$epoch + 1,
				null
			);

		$sut = new Loop();
		$sut->setSleepFunction(function() {});
		$sut->addTimer($timer);
		$sut->run();

		self::assertEquals(1, $sut->getTriggerCount());
	}

	public function testRunWithTimerMultiple() {
		$epoch = microtime(true);
		$timer = self::createMock(Timer::class);
		$timer->method("getNextRunTime")
			->willReturn(
				$epoch + 1,
				$epoch + 2,
				$epoch + 3,
				null
			);

		$sut = new Loop();
		$sut->setSleepFunction(function() {});
		$sut->addTimer($timer);
		$sut->run();

		self::assertEquals(3, $sut->getTriggerCount());
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

	public function testGetTimerList() {
		$epoch = microtime(true);

		$timer1 = self::createMock(Timer::class);
		$timer1->method("getNextRunTime")
			->willReturn($epoch + 1, null);
		$timer2 = self::createMock(Timer::class);
		$timer2->method("getNextRunTime")
			->willReturn($epoch + 2, null);

		$sut = new Loop();
		$sut->setSleepFunction(function() {});
		$sut->addTimer($timer1);
		$sut->addTimer($timer2);

		$timerOrder = $sut->getTimerOrder();
		self::assertSame($timer1, $timerOrder[0][1]);
		self::assertSame($timer2, $timerOrder[1][1]);
	}

	/**
	 * The only difference here to the test above is that timer1 is set to
	 * be due after timer2, so the order of the timers will be different.
	 */
	public function testGetTimerListOutOfOrder() {
		$epoch = microtime(true);

		$timer1 = self::createMock(Timer::class);
		$timer1->method("getNextRunTime")
			->willReturn($epoch + 2, null);
		$timer2 = self::createMock(Timer::class);
		$timer2->method("getNextRunTime")
			->willReturn($epoch + 1, null);

		$sut = new Loop();
		$sut->setSleepFunction(function() {});
		$sut->addTimer($timer1);
		$sut->addTimer($timer2);

		$timerOrder = $sut->getTimerOrder();
		self::assertSame($timer2, $timerOrder[0][1]);
		self::assertSame($timer1, $timerOrder[1][1]);
	}
}