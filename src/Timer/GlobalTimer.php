<?php
namespace Gt\Async\Timer;

class GlobalTimer {
	public static float $period = 0.01;
	private static Timer $timerInstance;

	public static function get():Timer {
		if(!isset(self::$timerInstance)) {
			self::$timerInstance = new PeriodicTimer(self::$period);
		}

		return self::$timerInstance;
	}
}