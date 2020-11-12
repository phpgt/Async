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
}