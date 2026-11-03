<?php declare(strict_types = 1);

namespace Tests\Cases\Events;

use Contributte\Tester\Toolkit;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMSetup;
use Nette\DI\Container;
use Nettrine\ORM\Events\ContainerEventManager;
use RuntimeException;
use Tester\Assert;
use Tests\Mocks\LifecycleSubscriber;

require_once __DIR__ . '/../../bootstrap.php';

// Returned objects can be suspended and restored in order without changing other events.
Toolkit::test(function (): void {
	$first = new LifecycleSubscriber();
	$second = new LifecycleSubscriber();
	$container = new Container();
	$container->addService('first', $first);
	$container->addService('second', $second);
	$events = new ContainerEventManager($container);
	$events->addEventListener([Events::onClear, Events::postFlush], 'first');
	$events->addEventListener(Events::onClear, 'second');
	$listeners = $events->getListeners(Events::onClear);
	Assert::same([$first, $second], array_values($listeners));
	foreach ($listeners as $listener) {
		$events->removeEventListener(Events::onClear, $listener);
	}

	Assert::false($events->hasListeners(Events::onClear));
	Assert::same([], $events->getListeners(Events::onClear));
	Assert::same([$first], array_values($events->getListeners(Events::postFlush)));
	foreach ($listeners as $listener) {
		$events->addEventListener(Events::onClear, $listener);
	}

	Assert::same($listeners, $events->getListeners(Events::onClear));
	$events->dispatchEvent(Events::onClear);
	Assert::same(1, $first->clearCalls);
	Assert::same(1, $second->clearCalls);
});

// Registration after initialization stays lazy and is resolved on the next dispatch.
Toolkit::test(function (): void {
	$container = new Container();
	$container->addService('first', fn (): LifecycleSubscriber => new LifecycleSubscriber());
	$container->addService('second', fn (): LifecycleSubscriber => new LifecycleSubscriber());
	$events = new ContainerEventManager($container);
	Assert::same([], $events->getListeners(Events::onClear));
	$events->addEventListener(Events::onClear, 'first');
	Assert::false($container->isCreated('first'));
	$events->dispatchEvent(Events::onClear);
	$first = $container->getService('first');
	assert($first instanceof LifecycleSubscriber);
	Assert::same(1, $first->clearCalls);
	$events->addEventListener(Events::onClear, 'second');
	Assert::false($container->isCreated('second'));
	$events->dispatchEvent(Events::onClear);
	$second = $container->getService('second');
	assert($second instanceof LifecycleSubscriber);
	Assert::same(2, $first->clearCalls);
	Assert::same(1, $second->clearCalls);
});

// Re-registering an initialized service by object does not duplicate or move it.
Toolkit::test(function (): void {
	$first = new LifecycleSubscriber();
	$second = new LifecycleSubscriber();
	$container = new Container();
	$container->addService('first', $first);
	$events = new ContainerEventManager($container);
	$events->addEventListener(Events::onClear, 'first');
	$events->addEventListener(Events::onClear, $second);
	$before = $events->getListeners(Events::onClear);
	$events->addEventListener(Events::onClear, $first);
	Assert::same($before, $events->getListeners(Events::onClear));
	$events->dispatchEvent(Events::onClear);
	Assert::same(1, $first->clearCalls);
	$events->removeEventListener(Events::onClear, 'first');
	Assert::same([$second], array_values($events->getListeners(Events::onClear)));
});

// Service aliases and an object registration dispatch the same instance only once.
Toolkit::test(function (): void {
	$listener = new LifecycleSubscriber();
	$container = new Container();
	$container->addService('first', $listener);
	$container->addService('alias', $listener);
	$events = new ContainerEventManager($container);
	$events->addEventListener(Events::onClear, 'first');
	$events->addEventListener(Events::onClear, $listener);
	$events->addEventListener(Events::onClear, 'alias');
	$events->dispatchEvent(Events::onClear);
	Assert::same(1, $listener->clearCalls);
	Assert::same([$listener], array_values($events->getListeners(Events::onClear)));
	$events->removeEventListener(Events::onClear, $listener);
	Assert::false($events->hasListeners(Events::onClear));
});

// Removing by service name before initialization does not construct the service.
Toolkit::test(function (): void {
	$container = new Container();
	$container->addService('lazy', fn (): LifecycleSubscriber => new LifecycleSubscriber());
	$events = new ContainerEventManager($container);
	$events->addEventListener(Events::onClear, 'lazy');
	$events->removeEventListener(Events::onClear, 'lazy');
	Assert::false($container->isCreated('lazy'));
	Assert::false($events->hasListeners(Events::onClear));
});

// Removing an already created object also finds pending service-name registrations.
Toolkit::test(function (): void {
	$listener = new LifecycleSubscriber();
	$container = new Container();
	$container->addService('existing', $listener);
	$container->addService('lazy', fn (): LifecycleSubscriber => new LifecycleSubscriber());
	$events = new ContainerEventManager($container);
	$events->addEventListener(Events::onClear, 'existing');
	$events->addEventListener(Events::onClear, 'lazy');
	$events->removeEventListener(Events::onClear, $listener);
	Assert::false($container->isCreated('lazy'));
	Assert::same(0, $listener->clearCalls);
	$events->dispatchEvent(Events::onClear);
	Assert::same(0, $listener->clearCalls);
	Assert::count(1, $events->getListeners(Events::onClear));
});

// Doctrine close can be retried without onClear and the exact subscriptions restored.
Toolkit::test(function (): void {
	$throwing = new LifecycleSubscriber();
	$throwing->throwOnClear = true;
	$following = new LifecycleSubscriber();
	$container = new Container();
	$container->addService('throwing', $throwing);
	$container->addService('following', $following);
	$events = new ContainerEventManager($container);
	$events->addEventListener([Events::onClear, Events::postFlush], 'throwing');
	$events->addEventListener([Events::onClear, Events::postFlush], 'following');
	$connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
	$config = ORMSetup::createAttributeMetadataConfiguration([], true);
	$first = new EntityManager($connection, $config, $events);
	$second = new EntityManager($connection, $config, $events);
	$otherEvent = $events->getListeners(Events::postFlush);

	foreach ([$first, $second] as $manager) {
		Assert::exception(fn () => $manager->close(), RuntimeException::class, 'Listener failed');
		Assert::true($manager->isOpen());
		$listeners = $events->getListeners(Events::onClear);
		foreach ($listeners as $listener) {
			$events->removeEventListener(Events::onClear, $listener);
		}

		try {
			$manager->close();
		} finally {
			foreach ($listeners as $listener) {
				$events->addEventListener(Events::onClear, $listener);
			}
		}

		Assert::false($manager->isOpen());
		Assert::same([], $manager->getUnitOfWork()->getIdentityMap());
		Assert::same($listeners, $events->getListeners(Events::onClear));
		Assert::same($otherEvent, $events->getListeners(Events::postFlush));
	}

	Assert::same(2, $throwing->clearCalls);
	Assert::same(0, $following->clearCalls);
	$throwing->throwOnClear = false;
	$events->dispatchEvent(Events::onClear);
	$events->dispatchEvent(Events::postFlush);
	Assert::same(3, $throwing->clearCalls);
	Assert::same(1, $following->clearCalls);
	Assert::same(1, $throwing->flushCalls);
	Assert::same(1, $following->flushCalls);
	Assert::false($connection->isConnected());
});
