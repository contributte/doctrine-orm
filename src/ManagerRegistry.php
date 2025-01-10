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

	protected function getService(string $name): object
	{
		return $this->container->getService($name);
	}

	protected function resetService(string $name): void
	{
		$manager = $this->container->getService($name);

		Binder::use($manager, function (): void {
			if ($this instanceof EntityManager) { // @phpstan-ignore-line
				$this->closed = false;
			} elseif ($this instanceof ObjectManagerDecorator) { // @phpstan-ignore-line
				Binder::use($this->wrapped, function (): void {
					if ($this instanceof EntityManager) {// @phpstan-ignore-line
						$this->closed = false;
					}
				});
			}
		});
		//throw new \Exception($manager::class);

		$this->container->removeService($name);
	}

}
