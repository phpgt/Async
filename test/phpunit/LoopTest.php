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
		$epoch = 1000;

		$sut = new Loop();
		$sut->setTimeFunction(fn() => $epoch);
		$sut->setSleepFunction(function(int $milliseconds) use (&$actualDelay) {
			$actualDelay = $milliseconds;
		});

		$epochPlus5s = $epoch + 5;
		$sut->waitUntil($epochPlus5s);

		self::assertEquals($epochPlus5s - $epoch, $actualDelay);
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
		$timer->method("isScheduled")
			->willReturn(true, false);
		$timer->method("getNextRunTime")
			->willReturn($epoch + 1, null);

		$sut = new Loop();
		$sut->setSleepFunction(function() {});
		$sut->addTimer($timer);
		$sut->run();

		self::assertEquals(1, $sut->getTriggerCount());
	}

	public function testRunWithTimerMultiple() {
		$epoch = microtime(true);
		$timer = self::createMock(Timer::class);
		$timer->method("isScheduled")
			->willReturn(true, true, true, false);
		$timer->method("getNextRunTime")
			->willReturn(
				$epoch + 100,
				$epoch + 200,
				$epoch + 300,
				null
			);

		$sut = new Loop();
		$sut->setSleepFunction(function() {});
		$sut->addTimer($timer);
		$sut->run();

		self::assertEquals(3, $sut->getTriggerCount());
	}

	public function testRunWithTimersConcurrent() {
		$epoch = microtime(true);
		$timerArray = [];
		$numExpectedDueTimers = 0;

		for($i = 0; $i < 100; $i++) {
// Randomise the epoch by +/- 100 seconds (roughly half of the timers will be due)
			$rand = rand(-100, 100);
			$timerEpoch = $epoch + $rand;

			$expectedToBeDue = $timerEpoch <= $epoch;
			if($expectedToBeDue) {
				$numExpectedDueTimers++;
			}

			$timer = self::createMock(Timer::class);
			$timer->method("isScheduled")
				->willReturn($expectedToBeDue);
			$timer->method("getNextRunTime")
				->willReturn($timerEpoch);
			$timerArray[] = $timer;
		}

		$sut = new Loop();
		$sut->setSleepFunction(function(){});
		foreach($timerArray as $timer) {
			$sut->addTimer($timer);
		}

		$sut->run(false);
		self::assertEquals(
			$numExpectedDueTimers,
			$sut->getTriggerCount()
		);
	}

	public function testRunWithTimerNoNextRunTime() {
		$timer = self::createMock(Timer::class);
		$timer->method("isScheduled")
			->willReturn(false);
		$timer->method("getNextRunTime")
			->willReturn(null);

		$sut = new Loop();
		$sut->addTimer($timer);
		$sut->run();

		self::assertEquals(0, $sut->getTriggerCount());
	}

	public function testHalt() {
		$epoch = microtime(true);
		$tickCount = 0;

		$sut = new Loop();

		$timer = self::createMock(Timer::class);
		$timer->method("isScheduled")
			->willReturn(true);
		$timer->method("getNextRunTime")
			->willReturn($epoch);
		$timer->method("tick")
			->willReturnCallback(function() use($sut, &$tickCount) {
				$tickCount++;

				if($tickCount === 10) {
					$sut->halt();
				}
				return true;
			});

		$sut->addTimer($timer);
		$sut->run();
		self::assertEquals(10, $tickCount);
	}

	public function testSleepActuallyDelays() {
		$startEpoch = microtime(true);
		$waitUntil = $startEpoch + 0.25;

		$sut = new Loop();
		$sut->waitUntil($waitUntil);
		$endEpoch = microtime(true);
		self::assertEquals(
// The delay should be accurate to a hundredth of a second.
			round($waitUntil, 2),
			round($endEpoch, 2)
		);
	}
}