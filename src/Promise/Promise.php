<?php
namespace Gt\Async\Promise;

use Gt\Async\AsyncException;
use Throwable;

class Promise {
	private Resolver $resolver;
	private ?Canceller $canceller;
	private array $handlers;
	/** @var mixed */
	private $result;

	public function __construct(
		Resolver $resolver,
		Canceller $canceller = null
	) {
		$this->resolver = $resolver;
		$this->canceller = $canceller;
		$this->handlers = [];

		try {
			$this->resolver->call();
		}
		catch(Throwable $e) {
			$this->reject($e);
		}
	}

	public function then(
		callable $onFulfilled = null,
		callable $onRejected = null
	):self {

	}

	public function catch(
		callable $onRejected = null
	):self {

	}

	public function cancel():void {

	}

	private function reject(Throwable $reason):void {
		if(is_null($this->result)) {
			$this->settle(new RejectedPromise($reason));
		}
	}

	private function settle(PromiseInterface $promise) {
		$result = $this->unwrap($promise);

		if($result === $this) {
			throw new AsyncException("A promise can't settle itself");
		}

		if($result instanceof self) {
			$result->requiredCancelRequests++;
		}
		else {
// TODO: React docs: Unset canceller only when not following a pending promise.
			$this->canceller = null;
		}

		$handlers = $this->handlers;
		$this->handlers = [];
		$this->result = $result;

		foreach($handlers as $handler) {
			$handler($result);
		}
	}

	private function unwrap(PromiseInterface $promise):PromiseInterface {
		while($promise instanceof self
		&& !is_null($promise->result)) {
			$promise = $promise->result;
		}

		return $promise;
	}
}