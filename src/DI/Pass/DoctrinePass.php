<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI\Pass;

use Nette\DI\Definitions\ServiceDefinition;
use Nettrine\ORM\DI\Helpers\BuilderMan;
use Nettrine\ORM\ManagerProvider;
use Nettrine\ORM\ManagerRegistry;
use Nettrine\ORM\Mapping\ContainerEntityListenerResolver;

class DoctrinePass extends AbstractPass
{

	public function loadPassConfiguration(): void
	{
		$builder = $this->extension->getContainerBuilder();

		// Manager Registry
		$builder->addDefinition($this->prefix('managerRegistry'))
			->setFactory(ManagerRegistry::class, [
				'@container',
				[],
				[],
			]);

		// Manager Provider
		$builder->addDefinition($this->prefix('managerProvider'))
			->setFactory(ManagerProvider::class, [
				$this->prefix('@managerRegistry'),
			]);

		// Entity Listener Resolver
		$builder->addDefinition($this->prefix('entityListenerResolver'))
			->setType(ContainerEntityListenerResolver::class);
	}

	public function beforePassCompile(): void
	{
		$builder = $this->extension->getContainerBuilder();

		$managerRegistryDef = $builder->getDefinition($this->prefix('managerRegistry'));
		assert($managerRegistryDef instanceof ServiceDefinition);

		$managerRegistryDef->setArgument(1, BuilderMan::of($this)->getConnectionsMap());
		$managerRegistryDef->setArgument(2, BuilderMan::of($this)->getManagersMap());
	}

}
