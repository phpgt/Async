<?php
namespace Gt\Async;

use Gt\Async\Timer\GlobalTimer;

class GlobalLoop {
	private static Loop $loopInstance;

	public static function get():Loop {
		if(!isset(self::$loopInstance)) {
			self::$loopInstance = new Loop();
			self::$loopInstance->addTimer(GlobalTimer::get());
		}

		return self::$loopInstance;
	}
}