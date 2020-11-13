<?php
/**
 * This example shows how a PeriodicTimer can be used to schedule ticks
 * forever, combined with an IndividualTimer that will cause the loop to halt
 * after a specified period.
 *
 * To articulate the use of asynchronous methods, a "tiny tick timer" is
 * introduced that runs very frequently, alongside the countdown. You can
 * imagine the concurrent timers being used to execute actual workloads.
 */

use Gt\Async\Loop;
use Gt\Async\Timer\IndividualTimer;
use Gt\Async\Timer\PeriodicTimer;

require("../vendor/autoload.php");

$stopTimer = new IndividualTimer(5);
$periodicTimer = new PeriodicTimer(1, true);
$tinyTickTimer = new PeriodicTimer(0.1, true);
$countdownNumber = 5;

$loop = new Loop();

$periodicTimer->addCallback(function() use(&$countdownNumber) {
	echo "Countdown: $countdownNumber", PHP_EOL;
	$countdownNumber--;
});
$tinyTickTimer->addCallback(function() {
	echo ".";
});
$stopTimer->addCallback(function() use($loop) {
	echo "LIFT OFF!", PHP_EOL;
	$loop->halt();
});

$loop->addTimer($periodicTimer);
$loop->addTimer($tinyTickTimer);
$loop->addTimer($stopTimer);
echo "Starting...", PHP_EOL;
$loop->run();