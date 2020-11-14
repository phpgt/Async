<?php
namespace Gt\Async\Promise;

use Throwable;

class RejectedPromise implements PromiseInterface {
	public function __construct(Throwable $reason) {

	}

	public function then(callable $onFulfilled = null, callable $onRejected = null):PromiseInterface {
		// TODO: Implement then() method.
	}

	public function catch(callable $onRejected = null):PromiseInterface {
		// TODO: Implement catch() method.
	}

	public function done(callable $onFulfilled = null, callable $onRejected = null):void {
		// TODO: Implement done() method.
	}

	public function cancel():void {
		// TODO: Implement cancel() method.
	}
}