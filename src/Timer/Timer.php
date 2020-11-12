<?php
namespace Gt\Async\Timer;

abstract class Timer {
	abstract public function tick():void;
	abstract public function getNextRunTime():?float;
}