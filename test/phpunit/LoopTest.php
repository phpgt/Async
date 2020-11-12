<?php
namespace Gt\Async\Test;

use Gt\Async\Loop;
use Gt\Async\Timer;
use PHPUnit\Framework\TestCase;

class LoopTest extends TestCase {
	public function testRunWithNoTimer() {
		$sut = new Loop();
		$sut->run();
		self::assertEquals(0, $sut->getTriggerCount());
	}
}