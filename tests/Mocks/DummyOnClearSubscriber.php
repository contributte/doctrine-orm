<?php declare(strict_types = 1);

namespace Tests\Mocks;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Events;

final class DummyOnClearSubscriber implements EventSubscriber
{

	/** @var OnClearEventArgs[] */
	public array $events = [];

	public function onClear(OnClearEventArgs $args): void
	{
		$this->events[] = $args;
	}

	/**
	 * @return string[]
	 */
	public function getSubscribedEvents(): array
	{
		return [Events::onClear];
	}

}
