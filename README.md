Promise-based non-blocking operations.
======================================

To be able to run asynchronous code in PHP, a loop needs to run in the background to observe and dispatch events, and handle the resolution of promises.

This repository provides the concepts of a `Loop`, different `Timer` implementations and a publish-subscribe model for `Event` objects.

***

<a href="https://github.com/PhpGt/Async/actions" target="_blank">
	<img src="https://badge.status.php.gt/async-build.svg" alt="PHP.Gt/Async build status" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Async" target="_blank">
	<img src="https://badge.status.php.gt/async-quality.svg" alt="PHP.Gt/Async code quality" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Async" target="_blank">
	<img src="https://badge.status.php.gt/async-coverage.svg" alt="PHP.Gt/Async code coverage" />
</a>
<a href="https://packagist.PhpGt/packages/PhpGt/Async" target="_blank">
	<img src="https://badge.status.php.gt/async-version.svg" alt="PHP.Gt/Async latest release" />
</a>
<a href="http://www.php.gt/Async" target="_blank">
	<img src="https://badge.status.php.gt/async-docs.svg" alt="PHP.Gt/Async documentation" />
</a>

Example usage
-------------

A loop with three individual timers at 1 second, 5 seconds and 10 seconds.

```php
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
```
