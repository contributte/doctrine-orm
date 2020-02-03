<?php declare(strict_types = 1);

namespace Tests\Cases;

use Nettrine\ORM\ManagerRegistry;
use Tests\Toolkit\Nette\ContainerBuilder;
use Tests\Toolkit\TestCase;

final class ManagerRegistryTest extends TestCase
{

	public function testResetEntityManager(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->build();

		$registry = $container->getByType(ManagerRegistry::class);
		$this->assertInstanceOf(ManagerRegistry::class, $registry);

		$registry->getManager()->close();
		$this->assertFalse($registry->getManager()->isOpen());

		$registry->resetManager();
		$this->assertTrue($registry->getManager()->isOpen());
	}

}
