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
