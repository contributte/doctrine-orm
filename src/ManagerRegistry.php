<?php declare(strict_types = 1);

namespace Nettrine\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\ObjectManagerDecorator;
use Doctrine\Persistence\Proxy;
use Nette\DI\Container;
use Nettrine\ORM\Utils\Binder;

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

	/**
	 * @param ObjectManagerDecorator<EntityManager>|EntityManager $manager
	 */
	public static function reopen(ObjectManagerDecorator|EntityManager $manager): void
	{
		// @phpcs:disable
		Binder::use($manager, function (): void {
			if ($this instanceof EntityManager) {  // @phpstan-ignore-line
				$this->closed = false; // @phpstan-ignore-line
			} elseif ($this instanceof ObjectManagerDecorator) {
				Binder::use($this->wrapped, function (): void { // @phpstan-ignore-line
					if ($this instanceof EntityManager) { // @phpstan-ignore-line
						$this->closed = false;
					}
				});
			}
		});
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
