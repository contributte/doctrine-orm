<?php declare(strict_types = 1);

namespace Nettrine\ORM;

use Closure;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectManagerDecorator;
use Doctrine\Persistence\Proxy;
use Nette\DI\Container;

class ManagerRegistry extends AbstractManagerRegistry
{

	/**
	 * @param array<string, string> $connectionsMap
	 * @param array<string, string> $managersMap
	 * @param class-string $proxy
	 */
	public function __construct(
		private Container $container,
		array $connectionsMap,
		array $managersMap,
		string $defautConnection = 'default',
		string $defaultManager = 'default',
		string $proxy = Proxy::class
	)
	{
		parent::__construct(
			'ORM',
			$connectionsMap,
			$managersMap,
			$defautConnection,
			$defaultManager,
			$proxy
		);
	}

	public static function reopen(EntityManagerInterface $manager): void
	{
		if ($manager instanceof ObjectManagerDecorator) {
			$manager = Closure::bind(
				static fn (ObjectManagerDecorator $decorator): ObjectManager => $decorator->wrapped,
				null,
				ObjectManagerDecorator::class,
			)($manager);
		}

		if (!$manager instanceof EntityManager) {
			return;
		}

		Closure::bind(static function (EntityManager $manager): void {
			$manager->closed = false;
		}, null, EntityManager::class)($manager);

		Closure::bind(static function (UnitOfWork $unitOfWork): void {
			$unitOfWork->persisters = [];
		}, null, UnitOfWork::class)($manager->getUnitOfWork());
	}

	protected function getService(string $name): object
	{
		return $this->container->getService($name);
	}

	protected function resetService(string $name): void
	{
		$manager = $this->container->getService($name);

		self::reopen($manager);

		$this->container->removeService($name);
	}

}
