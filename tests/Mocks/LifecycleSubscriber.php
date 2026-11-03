<?php declare(strict_types = 1);

namespace Tests\Mocks;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use RuntimeException;

final class LifecycleSubscriber implements EventSubscriber
{

	public int $clearCalls = 0;

	public int $flushCalls = 0;

	public bool $throwOnClear = false;

	public function onClear(EventArgs $args): void
	{
		$this->clearCalls++;
		if ($this->throwOnClear) {
			throw new RuntimeException('Listener failed');
		}
	}

	public function postFlush(EventArgs $args): void
	{
		$this->flushCalls++;
	}

	/**
	 * @return string[]
	 */
	public function getSubscribedEvents(): array
	{
		return [Events::onClear, Events::postFlush];
	}

}
