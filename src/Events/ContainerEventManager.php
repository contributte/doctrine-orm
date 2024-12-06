<?php declare(strict_types = 1);

namespace Nettrine\ORM\Events;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use Nette\DI\Container;

class ContainerEventManager extends EventManager
{

	protected Container $container;

	/** @var array<string, bool> */
	protected array $initialized = [];

	/** @var array<string, array<string, string|object>> */
	protected array $listeners = [];

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * {@inheritDoc}
	 */
	public function dispatchEvent(string $eventName, ?EventArgs $eventArgs = null): void
	{
		if (!$this->hasListeners($eventName)) {
			return;
		}

		$eventArgs ??= EventArgs::getEmptyInstance();

		foreach ($this->getInitializedListeners($eventName) as $listener) {
			$callback = [$listener, $eventName];
			assert(is_callable($callback));
			$callback($eventArgs);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getListeners(string $event): array
	{
		return $this->getInitializedListeners($event);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAllListeners(): array
	{
		$result = [];

		foreach ($this->listeners as $eventName => $listeners) {
			$result[$eventName] = $this->getInitializedListeners($eventName);
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function hasListeners(string $event): bool
	{
		return ($this->listeners[$event] ?? []) !== [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function addEventListener(array|string $events, string|object $listener): void
	{
		// Picks the hash code related to that listener
		$hash = $this->calculateHash($listener);

		foreach ((array) $events as $event) {
			// Overrides listener if a previous one was associated already
			// Prevents duplicate listeners on same event (same instance only)
			$this->listeners[$event][$hash] = $listener;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function removeEventListener(array|string $events, string|object $listener): void
	{
		// Picks the hash code related to that listener
		$hash = $this->calculateHash($listener);

		foreach ((array) $events as $event) {
			unset($this->listeners[$event][$hash]);
		}
	}

	/**
	 * @return array<string, object>
	 */
	private function getInitializedListeners(string $event): array
	{
		$initialized = $this->initialized[$event] ?? false;

		if ($initialized) {
			return $this->listeners[$event] ?? []; // @phpstan-ignore-line
		}

		foreach ($this->listeners[$event] ?? [] as $hash => $listener) {
			if (!is_object($listener)) {
				$this->listeners[$event][$hash] = $this->container->getService($listener);
			}
		}

		$this->initialized[$event] = true;

		return $this->listeners[$event] ?? []; // @phpstan-ignore-line
	}

	private function calculateHash(string|object $listener): string
	{
		return is_object($listener) ? spl_object_hash($listener) : 'service@' . $listener;
	}

}
