<?php
namespace Gt\Async\Test;

use Gt\Async\Loop;
use PHPUnit\Framework\TestCase;

class LoopTest extends TestCase {
	public function testIsRunningFalseAtStart() {
		$sut = new Loop();
		self::assertFalse($sut->isRunning());
	}

	public function testIsRunningTrueAfterStart() {
		$sut = new Loop();
		$sut->start();
		self::assertTrue($sut->isRunning());
	}

	public function testIsRunningFalseAfterStop() {
		$sut = new Loop();
		$sut->start();
		$sut->stop();
		self::assertFalse($sut->isRunning());
	}
}