<?php
namespace Gt\Async\Test\Timer;

use Gt\Async\Timer\PeriodicTimer;
use PHPUnit\Framework\TestCase;

class PeriodicTimerTest extends TestCase {
	public function testIsScheduled() {
		$sut = new PeriodicTimer(10);
		self::assertTrue($sut->isScheduled());
	}

	public function testGetNextRunTime() {
		$epoch = 1000;
		$period = 1;

		$sut = new PeriodicTimer($period);
		$sut->setTimeFunction(function() use(&$epoch) {
			return $epoch++;
		});

		self::assertEquals(
			$epoch + $period,
			$sut->getNextRunTime()
		);
	}

	public function testGetNextRunTimeDelayedAtStart() {
		$epoch = 1000;
		$period = 1;
		$sut = new PeriodicTimer($period);
		$sut->setTimeFunction(fn() => $epoch);
		$nextRunTime = $sut->getNextRunTime();
		self::assertEquals($epoch + $period, $nextRunTime);
	}

	public function testGetNextRunTimeDelayedAtStartLongPeriod() {
		$epoch = 1000;
		$period = 100;
		$sut = new PeriodicTimer($period);
		$sut->setTimeFunction(fn() => $epoch);
		$nextRunTime = $sut->getNextRunTime();
		self::assertEquals($epoch + $period, $nextRunTime);
	}

	public function testGetNextRunTimeImmediate() {
		$epoch = 1000;
		$period = 1;
		$sut = new PeriodicTimer($period, true);
		$sut->setTimeFunction(fn() => $epoch);
		$nextRunTime = $sut->getNextRunTime();
		self::assertEquals($epoch, $nextRunTime);
	}

	public function testGetNextRunTimeImmediateMultipleTicks() {
		$epochStart = 1000;
		$epoch = $epochStart;

		$sut = new PeriodicTimer(1, true);
		$sut->setTimeFunction(function() use(&$epoch) {
			return $epoch;
		});

		for($i = 0; $i < 100; $i++) {
			$nextRunTime = $sut->getNextRunTime();
			self::assertEquals(
				$epochStart + $i,
				$nextRunTime
			);
			$sut->tick();
			$epoch++;
		}
	}
}