<?php
namespace Gt\Async\Timer;

use ArrayIterator;
use Countable;
use MultipleIterator;

class TimerOrder extends MultipleIterator implements Countable {
	/** @var Timer[] $timerList */
	private array $timerList;

	/** @param Timer[] $timerList */
	public function __construct(array $timerList) {
		$timerList = array_filter(
			$timerList,
			fn(Timer $t) => $t->isScheduled()
		);
		usort(
			$timerList,
			fn(Timer $a, Timer $b) =>
			$a->getNextRunTime() < $b->getNextRunTime() ? -1 : 1
		);
		$epochList = array_map(
			fn(Timer $t) => $t->getNextRunTime(),
			$timerList
		);
		$this->timerList = $timerList;

		parent::__construct(MultipleIterator::MIT_KEYS_ASSOC);
		$this->attachIterator(
			new ArrayIterator($timerList),
			"timer"
		);
		$this->attachIterator(
			new ArrayIterator($epochList),
			"epoch"
		);
	}

	public function count():int {
		return count($this->timerList);
	}

	public function getCurrentTimer():Timer {
		return $this->current()["timer"];
	}

	public function getCurrentEpoch():float {
		$current = $this->current();
		return $current["epoch"];
	}

	public function subset():TimerOrder {
		$timerArray = [];
		while($this->valid()) {
			$timerArray[] = $this->current()["timer"];
			$this->next();
		}

		return new TimerOrder($timerArray);
	}
}