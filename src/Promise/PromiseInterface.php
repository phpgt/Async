<?php
namespace Gt\Async\Promise;

interface PromiseInterface {
	public function then(
		callable $onFulfilled = null,
		callable $onRejected = null
	):self;

	public function catch(
		callable $onRejected = null
	):self;

	public function done(
		callable $onFulfilled = null,
		callable $onRejected = null
	):void;

	public function cancel():void;
}