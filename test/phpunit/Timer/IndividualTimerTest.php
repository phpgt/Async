<?php
namespace Gt\Async\Test\Timer;

use Gt\Async\Timer\IndividualTimer;

class IndividualTimerTest extends \PHPUnit\Framework\TestCase {
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
}