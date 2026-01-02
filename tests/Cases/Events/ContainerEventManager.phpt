<?php declare(strict_types = 1);

namespace Tests\Cases\Events;

use Contributte\Tester\Toolkit;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Events;
use Mockery;
use Nette\DI\Container;
use Nette\Utils\Arrays;
use Nettrine\ORM\Events\ContainerEventManager;
use Tester\Assert;
use Tests\Mocks\DummyOnClearSubscriber;

require_once __DIR__ . '/../../bootstrap.php';

// Add subscriber as object
Toolkit::test(function (): void {
	$entityManager = Mockery::mock(EntityManager::class);

	$container = new Container();
	$eventManager = new ContainerEventManager($container);

	$subscriber = new DummyOnClearSubscriber();
	$eventManager->addEventSubscriber($subscriber);

	$event = new OnClearEventArgs($entityManager);

	Assert::count(0, $subscriber->events);
	$eventManager->dispatchEvent(Events::onClear, $event);
	Assert::count(1, $subscriber->events);

	Assert::same([$event], $subscriber->events);
});

// Add listener as string (service)
Toolkit::test(function (): void {
	$entityManager = Mockery::mock(EntityManager::class);
	$subscriber = new DummyOnClearSubscriber();

	$container = new Container();
	$container->addService('dummySubscriber', $subscriber);
	$eventManager = new ContainerEventManager($container);

	$eventManager->addEventListener(Events::onClear, 'dummySubscriber');

	$event = new OnClearEventArgs($entityManager);

	Assert::count(0, $subscriber->events);
	$eventManager->dispatchEvent(Events::onClear, $event);
	Assert::count(1, $subscriber->events);

	Assert::same([$event], $subscriber->events);
});

// Remove subscriber as object
Toolkit::test(function (): void {
	$entityManager = Mockery::mock(EntityManager::class);
	$subscriber = new DummyOnClearSubscriber();

	$container = new Container();
	$container->addService('dummySubscriber', $subscriber);
	$eventManager = new ContainerEventManager($container);

	$eventManager->addEventSubscriber($subscriber);
	$eventManager->removeEventSubscriber($subscriber);

	Assert::count(0, $subscriber->events);
	$eventManager->dispatchEvent(Events::onClear, new OnClearEventArgs($entityManager, 'foo'));
	Assert::count(0, $subscriber->events);
});

// Remove listener as string (service)
Toolkit::test(function (): void {
	$entityManager = Mockery::mock(EntityManager::class);
	$subscriber = new DummyOnClearSubscriber();

	$container = new Container();
	$container->addService('dummySubscriber', $subscriber);
	$eventManager = new ContainerEventManager($container);

	$eventManager->addEventListener(Events::onClear, 'dummySubscriber');
	$eventManager->removeEventListener(Events::onClear, 'dummySubscriber');

	Assert::count(0, $subscriber->events);
	$eventManager->dispatchEvent(Events::onClear, new OnClearEventArgs($entityManager, 'foo'));
	Assert::count(0, $subscriber->events);
});

// Get all listener
Toolkit::test(function (): void {
	$subscriber = new DummyOnClearSubscriber();

	$container = new Container();
	$container->addService('dummySubscriber', $subscriber);
	$eventManager = new ContainerEventManager($container);

	$eventManager->addEventListener(Events::onClear, 'dummySubscriber');

	Assert::count(1, $eventManager->getAllListeners()); // one event
	Assert::count(1, $eventManager->getAllListeners()[Events::onClear]); // one subscriber
	Assert::type(DummyOnClearSubscriber::class, Arrays::first($eventManager->getAllListeners()[Events::onClear])); // one subscriber
});

// hasListeners - empty
Toolkit::test(function (): void {
	$container = new Container();
	$eventManager = new ContainerEventManager($container);

	Assert::false($eventManager->hasListeners(Events::onClear));
});

// hasListeners - with listeners
Toolkit::test(function (): void {
	$subscriber = new DummyOnClearSubscriber();

	$container = new Container();
	$eventManager = new ContainerEventManager($container);
	$eventManager->addEventSubscriber($subscriber);

	Assert::true($eventManager->hasListeners(Events::onClear));
});

// getListeners for specific event
Toolkit::test(function (): void {
	$subscriber = new DummyOnClearSubscriber();

	$container = new Container();
	$eventManager = new ContainerEventManager($container);
	$eventManager->addEventSubscriber($subscriber);

	$listeners = $eventManager->getListeners(Events::onClear);

	Assert::count(1, $listeners);
	Assert::type(DummyOnClearSubscriber::class, Arrays::first($listeners));
});

// dispatchEvent with null args - dispatches with empty EventArgs
Toolkit::test(function (): void {
	$container = new Container();
	$eventManager = new ContainerEventManager($container);

	// Add a generic listener that accepts EventArgs
	$called = false;
	$listener = new class ($called) {

		public function __construct(private bool &$called)
		{
		}

		public function onClear(\Doctrine\Common\EventArgs $args): void
		{
			$this->called = true;
		}

	};

	$eventManager->addEventListener(Events::onClear, $listener);

	// This should not throw - null args should be handled
	$eventManager->dispatchEvent(Events::onClear, null);

	Assert::true($called);
});

// addEventListener with multiple events
Toolkit::test(function (): void {
	$subscriber = new DummyOnClearSubscriber();

	$container = new Container();
	$eventManager = new ContainerEventManager($container);

	$eventManager->addEventListener([Events::onClear, Events::prePersist], $subscriber);

	Assert::true($eventManager->hasListeners(Events::onClear));
	Assert::true($eventManager->hasListeners(Events::prePersist));
});

// addEventListener prevents duplicate same listener
Toolkit::test(function (): void {
	$subscriber = new DummyOnClearSubscriber();

	$container = new Container();
	$eventManager = new ContainerEventManager($container);

	$eventManager->addEventListener(Events::onClear, $subscriber);
	$eventManager->addEventListener(Events::onClear, $subscriber);

	Assert::count(1, $eventManager->getListeners(Events::onClear));
});

// removeEventListener from multiple events
Toolkit::test(function (): void {
	$subscriber = new DummyOnClearSubscriber();

	$container = new Container();
	$eventManager = new ContainerEventManager($container);

	$eventManager->addEventListener([Events::onClear, Events::prePersist], $subscriber);
	$eventManager->removeEventListener([Events::onClear, Events::prePersist], $subscriber);

	Assert::false($eventManager->hasListeners(Events::onClear));
	Assert::false($eventManager->hasListeners(Events::prePersist));
});

// Lazy loading - service listeners only loaded on dispatch
Toolkit::test(function (): void {
	$subscriber = new DummyOnClearSubscriber();

	$container = new Container();
	$container->addService('lazySubscriber', $subscriber);
	$eventManager = new ContainerEventManager($container);

	$eventManager->addEventListener(Events::onClear, 'lazySubscriber');

	// Before dispatch, getAllListeners should still return the service (lazy loaded)
	$listeners = $eventManager->getAllListeners();
	Assert::count(1, $listeners[Events::onClear]);

	// After first access, the listener should be resolved
	Assert::type(DummyOnClearSubscriber::class, Arrays::first($listeners[Events::onClear]));
});

// dispatchEvent does nothing when no listeners
Toolkit::test(function (): void {
	$entityManager = Mockery::mock(EntityManager::class);

	$container = new Container();
	$eventManager = new ContainerEventManager($container);

	// Should not throw
	$eventManager->dispatchEvent(Events::onClear, new OnClearEventArgs($entityManager));

	Assert::true(true);
});
