<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\DI\Statement;
use Nettrine\ORM\EntityManager;
use Nettrine\ORM\EntityManagerFactory;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use Nettrine\ORM\ManagerRegistry;
use Nettrine\ORM\Mapping\ContainerEntityListenerResolver;

final class OrmExtension extends CompilerExtension
{

	/** @var mixed[] */
	private $defaults = [
		'entityManagerClass' => EntityManager::class,
		'configuration' => [
			'proxyDir' => '%tempDir%/proxies',
			'autoGenerateProxyClasses' => null,
			'proxyNamespace' => 'Nettrine\Proxy',
			'metadataDriverImpl' => null,
			'entityNamespaces' => [],
			//TODO named query
			//TODO named native query
			'customStringFunctions' => [],
			'customNumericFunctions' => [],
			'customDatetimeFunctions' => [],
			'customHydrationModes' => [],
			'classMetadataFactoryName' => null,
			//TODO filters
			'defaultRepositoryClassName' => null,
			'namingStrategy' => UnderscoreNamingStrategy::class,
			'quoteStrategy' => null,
			'entityListenerResolver' => null,
			'repositoryFactory' => null,
			'defaultQueryHints' => [],
		],
	];

	public function loadConfiguration(): void
	{
		$this->validateConfig($this->defaults);
		$this->loadDoctrineConfiguration();
		$this->loadEntityManagerConfiguration();
	}

	public function loadDoctrineConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults['configuration'], $this->config['configuration']);
		$config = Helpers::expand($config, $builder->parameters);

		$configuration = $builder->addDefinition($this->prefix('configuration'))
			->setType(Configuration::class);

		if ($config['proxyDir'] !== null) {
			$configuration->addSetup('setProxyDir', [$config['proxyDir']]);
		}
		if ($config['autoGenerateProxyClasses'] !== null) {
			$configuration->addSetup('setAutoGenerateProxyClasses', [$config['autoGenerateProxyClasses']]);
		}
		if ($config['proxyNamespace'] !== null) {
			$configuration->addSetup('setProxyNamespace', [$config['proxyNamespace']]);
		}
		if ($config['metadataDriverImpl'] !== null) {
			$configuration->addSetup('setMetadataDriverImpl', [$config['metadataDriverImpl']]);
		}
		if ($config['entityNamespaces']) {
			$configuration->addSetup('setEntityNamespaces', [$config['entityNamespaces']]);
		}

		// Custom functions
		$configuration
			->addSetup('setCustomStringFunctions', [$config['customStringFunctions']])
			->addSetup('setCustomNumericFunctions', [$config['customNumericFunctions']])
			->addSetup('setCustomDatetimeFunctions', [$config['customDatetimeFunctions']])
			->addSetup('setCustomHydrationModes', [$config['customHydrationModes']]);

		if ($config['classMetadataFactoryName'] !== null) {
			$configuration->addSetup('setClassMetadataFactoryName', [$config['classMetadataFactoryName']]);
		}
		if ($config['defaultRepositoryClassName'] !== null) {
			$configuration->addSetup('setDefaultRepositoryClassName', [$config['defaultRepositoryClassName']]);
		}

		if ($config['namingStrategy'] !== null) {
			$configuration->addSetup('setNamingStrategy', [new Statement($config['namingStrategy'])]);
		}
		if ($config['quoteStrategy'] !== null) {
			$configuration->addSetup('setQuoteStrategy', [$config['quoteStrategy']]);
		}
		if ($config['entityListenerResolver'] !== null) {
			$configuration->addSetup('setEntityListenerResolver', [$config['entityListenerResolver']]);
		} else {
			$builder->addDefinition($this->prefix('entityListenerResolver'))
				->setType(ContainerEntityListenerResolver::class);
			$configuration->addSetup('setEntityListenerResolver', [$this->prefix('@entityListenerResolver')]);
		}
		if ($config['repositoryFactory'] !== null) {
			$configuration->addSetup('setRepositoryFactory', [$config['repositoryFactory']]);
		}
		if ($config['defaultQueryHints']) {
			$configuration->addSetup('setDefaultQueryHints', [$config['defaultQueryHints']]);
		}
	}

	public function loadEntityManagerConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$entityManagerClass = $config['entityManagerClass'];
		if (!class_exists($entityManagerClass)) {
			throw new InvalidStateException(sprintf('EntityManager class "%s" not found', $entityManagerClass));
		}

		// Entity Manager
		$builder->addDefinition($this->prefix('entityManager'))
			->setType($entityManagerClass)
			->setFactory(EntityManagerFactory::class . '::create', [
				$builder->getDefinitionByType(Connection::class), // Nettrine/DBAL
				$this->prefix('@configuration'),
				$entityManagerClass,
			]);

		// ManagerRegistry
		$builder->addDefinition($this->prefix('managerRegistry'))
			->setFactory(ManagerRegistry::class, [
				$builder->getDefinitionByType(Connection::class),
				$this->prefix('@entityManager'),
			]);
	}

}
