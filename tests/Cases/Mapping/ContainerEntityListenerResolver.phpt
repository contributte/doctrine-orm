<?php declare(strict_types = 1);

namespace Tests\Cases\Mapping;

use Contributte\Tester\Toolkit;
use Nette\DI\Container;
use Nettrine\ORM\Mapping\ContainerEntityListenerResolver;
use Tester\Assert;
use Tests\Mocks\DummyEntityListener;

require_once __DIR__ . '/../../bootstrap.php';

// Resolve from container creates new instance (container has no getByType returning same)
Toolkit::test(function (): void {
	$container = new Container();

	$resolver = new ContainerEntityListenerResolver($container);
	$resolved = $resolver->resolve(DummyEntityListener::class);

	Assert::type(DummyEntityListener::class, $resolved);
});

// Resolve creates new instance if not in container
Toolkit::test(function (): void {
	$container = new Container();

	$resolver = new ContainerEntityListenerResolver($container);
	$resolved = $resolver->resolve(DummyEntityListener::class);

	Assert::type(DummyEntityListener::class, $resolved);
});

// Resolve caches instances
Toolkit::test(function (): void {
	$container = new Container();

	$resolver = new ContainerEntityListenerResolver($container);
	$resolved1 = $resolver->resolve(DummyEntityListener::class);
	$resolved2 = $resolver->resolve(DummyEntityListener::class);

	Assert::same($resolved1, $resolved2);
});

// Resolve handles leading backslashes
Toolkit::test(function (): void {
	$container = new Container();

	$resolver = new ContainerEntityListenerResolver($container);
	$resolved = $resolver->resolve('\\' . DummyEntityListener::class);

	Assert::type(DummyEntityListener::class, $resolved);
});

// Register listener manually
Toolkit::test(function (): void {
	$listener = new DummyEntityListener();
	$container = new Container();

	$resolver = new ContainerEntityListenerResolver($container);
	$resolver->register($listener);

	$resolved = $resolver->resolve(DummyEntityListener::class);

	Assert::same($listener, $resolved);
});

// Clear specific listener
Toolkit::test(function (): void {
	$container = new Container();

	$resolver = new ContainerEntityListenerResolver($container);
	$resolved1 = $resolver->resolve(DummyEntityListener::class);

	$resolver->clear(DummyEntityListener::class);

	$resolved2 = $resolver->resolve(DummyEntityListener::class);

	Assert::notSame($resolved1, $resolved2);
});

// Clear specific listener with leading backslash
Toolkit::test(function (): void {
	$container = new Container();

	$resolver = new ContainerEntityListenerResolver($container);
	$resolved1 = $resolver->resolve(DummyEntityListener::class);

	$resolver->clear('\\' . DummyEntityListener::class);

	$resolved2 = $resolver->resolve(DummyEntityListener::class);

	Assert::notSame($resolved1, $resolved2);
});

// Clear all listeners
Toolkit::test(function (): void {
	$container = new Container();

	$resolver = new ContainerEntityListenerResolver($container);
	$resolved1 = $resolver->resolve(DummyEntityListener::class);

	$resolver->clear();

	$resolved2 = $resolver->resolve(DummyEntityListener::class);

	Assert::notSame($resolved1, $resolved2);
});

// Clear non-existent listener does not throw
Toolkit::test(function (): void {
	$container = new Container();
	$resolver = new ContainerEntityListenerResolver($container);

	// Should not throw
	$resolver->clear('NonExistentClass');

	Assert::true(true);
});
