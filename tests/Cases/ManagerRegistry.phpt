<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Doctrine\Persistence\ManagerRegistry;
use Tester\Assert;
use Tests\Toolkit\Container;

require_once __DIR__ . '/../bootstrap.php';

Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->build();

	$registry = $container->getByType(ManagerRegistry::class);
	Assert::type(ManagerRegistry::class, $registry);

	$registry->getManager()->close();
	Assert::false($registry->getManager()->isOpen());

	$registry->resetManager();
	Assert::true($registry->getManager()->isOpen());
});
