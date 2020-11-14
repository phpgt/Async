<?php
/**
 * This example counts the number of vowels in example1.txt and the number of
 * consonants in example2.txt, showing how concurrent slow file reads can
 * use Promises to defer work, with a future callback when the work is complete.
 *
 * A PeriodicLoop is used with a long period and the file reading code
 * is done one byte at a time, to simulate a slow connection.
 *
 * Note: This is an example wrapped in a class, showing an example of how a
 * framework could offer promise-driven filesystem functionality.
 */

use Gt\Async\Loop;
use Gt\Async\Promise\Deferred;
use Gt\Async\Promise\Promise;
use Gt\Async\Timer\PeriodicTimer;

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
	public function countCharacters(string $charMap):Promise {
// A new Deferred is created to assign this class's specific process function.
		$this->deferred = new Deferred();
		$this->deferred->addProcessFunction(
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
	public function processNextCharacter(string $charMap):void {
// TODO: Nice. Got this far. Now it's time to finish Promise implementation,
// and then we can complete this example's functionality here.
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
$loop = new Loop();
$loop->addTimer($timer);
$loop->haltWhenAllDeferredComplete(true);

$reader1 = new SlowFileReader("example.txt");
$reader1->setLoop($loop);
$reader2 = new SlowFileReader("example.txt");
$reader2->setLoop($loop);

$reader1->countCharacters("aeiou");
//->then(function(int $numVowels):void {
//	echo "Example text has $numVowels vowels.", PHP_EOL;
//});
//
//$reader2->countCharacters("bcdfghjklmnpqrstvwxyz")
//->then(function(int $numConsonants):void {
//	echo "Example text has $numConsonants consonants.", PHP_EOL;
//});

$loop->run();
echo "Complete!", PHP_EOL;
