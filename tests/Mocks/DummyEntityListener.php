<?php declare(strict_types = 1);

namespace Tests\Mocks;

use Doctrine\ORM\Event\PrePersistEventArgs;

final class DummyEntityListener
{

	/** @var PrePersistEventArgs[] */
	public array $events = [];

	public function prePersist(PrePersistEventArgs $args): void
	{
		$this->events[] = $args;
	}

}
