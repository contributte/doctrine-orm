<?php declare(strict_types = 1);

namespace Nettrine\ORM;

use Doctrine\Persistence\AbstractManagerRegistry;
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

	protected function getService(string $name): object
	{
		return $this->container->getService($name);
	}

	protected function resetService(string $name): void
	{
		$this->container->removeService($name);
	}

}
