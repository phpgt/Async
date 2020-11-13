<?php
/**
 * This example shows how a PeriodicTimer can be used to schedule ticks
 * forever, combined with an IndividualTimer that will cause the loop to halt
 * after a specified period.
 */

use Gt\Async\Loop;
use Gt\Async\Timer\IndividualTimer;
use Gt\Async\Timer\PeriodicTimer;

require("../vendor/autoload.php");

$stopTimer = new IndividualTimer(5);
$periodicTimer = new PeriodicTimer(1, true);
$countdownNumber = 5;

$loop = new Loop();

$periodicTimer->addCallback(function() use(&$countdownNumber) {
	echo "Countdown: $countdownNumber", PHP_EOL;
	$countdownNumber--;
});
$stopTimer->addCallback(function() use($loop) {
	echo "LIFT OFF!", PHP_EOL;
	$loop->halt();
});

$loop->addTimer($periodicTimer);
$loop->addTimer($stopTimer);
echo "Starting...", PHP_EOL;
$loop->run();