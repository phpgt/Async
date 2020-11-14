<?php
namespace Gt\Async\Promise;

use Throwable;

class Deferred {
	private Resolver $resolver;
	private Promise $promise;
	private array $processFunctionArray;
	private bool $isActive;

	public function __construct(
		Canceller $canceller = null,
		Resolver $resolver = null
	) {
		$this->resolver = $resolver ?? new Resolver();
		$this->promise = new Promise($this->resolver, $canceller);
		$this->processFunctionArray = [];
		$this->isActive = true;
	}

	public function getPromise():Promise {
		return $this->promise;
	}

	public function isActive():bool {
		return $this->isActive;
	}

	/** @param mixed|null $value */
	public function resolve($value = null):void {
		$this->resolver->resolve($value);
		$this->isActive = false;
	}

	public function reject(Throwable $reason):void {
		$this->resolver->reject($reason);
		$this->isActive = false;
	}

	public function addProcessFunction(callable $function):void {
		$this->processFunctionArray[] = $function;
	}

	/** @return callable[] */
	public function getProcessFunctionArray():array {
		return $this->processFunctionArray;
	}
}