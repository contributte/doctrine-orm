<?php declare(strict_types = 1);

namespace Nettrine\ORM\Events;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use Nette\DI\Container;

class ContainerEventManager extends EventManager
{

	protected Container $container;

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
			if (is_object($listener) && in_array($listener, $this->listeners[$event] ?? [], true)) {
				continue;
			}

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
			$registered = $this->listeners[$event][$hash] ?? $listener;
			unset($this->listeners[$event][$hash]);
			if (is_string($registered) && $this->container->hasService($registered) && $this->container->isCreated($registered)) {
				$registered = $this->container->getService($registered);
			}

			if (!is_object($registered)) {
				continue;
			}

			foreach ($this->listeners[$event] ?? [] as $key => $candidate) {
				// Match initialized services by identity, without constructing unrelated lazy services.
				if (
					$candidate === $registered
					|| (is_string($candidate)
						&& $this->container->hasService($candidate)
						&& $this->container->isCreated($candidate)
						&& $this->container->getService($candidate) === $registered)
				) {
					unset($this->listeners[$event][$key]);
				}
			}
		}
	}

	/**
	 * @return array<string, object>
	 */
	private function getInitializedListeners(string $event): array
	{
		do {
			foreach (array_keys($this->listeners[$event] ?? []) as $hash) {
				$listener = $this->listeners[$event][$hash] ?? null;
				if (!is_string($listener)) {
					continue;
				}

				$instance = $this->container->getService($listener);
				// A factory may remove this registration while creating the service.
				if (($this->listeners[$event][$hash] ?? null) === $listener) {
					$this->listeners[$event][$hash] = $instance;
				}
			}

			$result = [];
			$pending = false;
			foreach ($this->listeners[$event] ?? [] as $listener) {
				if (is_string($listener)) {
					// Resolve registrations appended by a factory before exposing the list.
					$pending = true;
					break;
				}

				// Build from current registrations: a factory may have removed earlier objects.
				$result[spl_object_hash($listener)] = $listener;
			}
		} while ($pending);

		return $result;
	}

	private function calculateHash(string|object $listener): string
	{
		return is_object($listener) ? spl_object_hash($listener) : 'service@' . $listener;
	}

}
