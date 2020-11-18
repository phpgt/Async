<?php
/**
 * This example counts the number of vowels and the number of consonants
 * in example.txt, showing how concurrent slow file reads can use Promises
 * to defer work, with a future callback when the work is complete.
 *
 * A PeriodicLoop is used with a purposefully long period with the file
 * reading code being done one byte at a time, to simulate a slow connection.
 *
 * Note: This is an example wrapped in a class, showing an example of how a
 * framework could offer promise-driven filesystem functionality.
 */

use Gt\Async\Loop;
use Gt\Async\Timer\PeriodicTimer;
use Gt\Promise\Deferred;
use Gt\Promise\PromiseInterface;

require("../vendor/autoload.php");

class SlowFileReader {
	private SplFileObject $file;
	private Loop $loop;
	private Deferred $deferred;
	private int $characterCount;

	public function __construct(string $filename) {
		$this->file = new SplFileObject($filename);
	}

// We need to inject the background loop that will dispatch the processing calls.
	public function setLoop(Loop $loop):void {
		$this->loop = $loop;
	}

// This is the public function that will be called, returning a Promise that
// represents the completed work.
	public function countCharacters(string $charMap):PromiseInterface {
// A new Deferred is created to assign this class's specific process function.
		$this->deferred = new Deferred();
		$this->deferred->addProcess(
			fn() => $this->processNextCharacter($charMap)
		);
// The Deferred is added to the Loop's default timer, which will call its
// process function each tick.
		$this->loop->addDeferredToTimer($this->deferred);
// The Deferred creates its own Promise, so it knows what to resolve when the
// work is complete.
		return $this->deferred->getPromise();
	}

// This functions is called by the Deferred, as the Deferred is invoked by the
// background Loop. It must not do much work per call, as to not block the
// execution of other deferred tasks.
	private function processNextCharacter(string $charMap):void {
		if(!isset($this->characterCount)) {
			$this->characterCount = 0;
		}

		if($this->file->eof()) {
			$this->deferred->resolve($this->characterCount);
			return;
		}

		$char = $this->file->fread(1);
		$char = strtolower($char);
		if(strlen($char) <= 0) {
			return;
		}

		if(strstr($charMap, $char)) {
			$this->characterCount++;
		}
	}
}

// A periodic timer will be used to call the deferred tasks ten times per
// second. This is actually quite slow, used to illustrate how concurrent tasks
// will behave.
$timer = new PeriodicTimer(0.1, true);
$timer->addCallback(function() {
	echo ".";
});

// This loop will be called to run forever by the run() function at the bottom
// of this file, but here we are setting the loop to halt if all internal
// Deferred objects complete.
$loop = new Loop();
$loop->addTimer($timer);
$loop->haltWhenAllDeferredComplete(true);

// Create the example classes to slowly loop over the characters of the file.
$reader1 = new SlowFileReader("example.txt");
$reader1->setLoop($loop);
$reader2 = new SlowFileReader("example.txt");
$reader2->setLoop($loop);

// The countCharacters function returns a Promise, meaning it will not actually
// undertake any work itself, but will resolve the promise when the work
// completes. The work is undertaken by the Deferred object, which is triggered
// by the Loop's timers.
$reader1->countCharacters("aeiou")
->then(function(int $numVowels):void {
	echo "Example text has $numVowels vowels.", PHP_EOL;
});

// Another Promise can be added, so their Deferred's work is undertaken
// concurrently.
$reader2->countCharacters("bcdfghjklmnpqrstvwxyz")
->then(function(int $numConsonants):void {
	echo "Example text has $numConsonants consonants.", PHP_EOL;
});

// Here we execute the loop, which has been set to halt when all Deferred
// objects complete.
$loop->run();
echo "Complete!", PHP_EOL;
