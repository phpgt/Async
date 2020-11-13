<?php
/**
 * This example shows how an IndividualTimer can be used to schedule some ticks
 * in the future. Here, the callback function simply calculates how many seconds
 * have passed since the script's start and echos. You can see the correlation
 * with the output to the three trigger times that are added after creating
 * the IndividualTimer object.
 */

use Gt\Async\Loop;
use Gt\Async\Timer\IndividualTimer;
require("../vendor/autoload.php");

$timeAtScriptStart = microtime(true);

$timer = new IndividualTimer();
$timer->addTriggerTime($timeAtScriptStart + 1);
$timer->addTriggerTime($timeAtScriptStart + 5);
$timer->addTriggerTime($timeAtScriptStart + 10);

$timer->addCallback(function() use($timeAtScriptStart) {
	$now = microtime(true);
	$secondsPassed = round($now - $timeAtScriptStart);
	echo "Number of seconds passed: $secondsPassed", PHP_EOL;
});

$loop = new Loop();
$loop->addTimer($timer);
echo "Starting...", PHP_EOL;
$loop->run();