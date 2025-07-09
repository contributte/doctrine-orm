<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI\Pass;

use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\DI\Helpers;
use Nettrine\DBAL\DI\Helpers\SmartStatement;
use Nettrine\ORM\DI\Helpers\BuilderMan;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\Exception\LogicalException;

/**
 * @phpstan-import-type TManagerConfig from OrmExtension
 */
class ManagerPass extends AbstractPass
{

	public function __construct(
		CompilerExtension $extension,
		private bool $debugMode
	)
	{
		parent::__construct($extension);
	}

	public function loadPassConfiguration(): void
	{
		$config = $this->getConfig();

		// Configure managers
		foreach ($config->managers as $managerName => $managerConfig) {
			// Load connection configuration
			$this->loadManagerConfiguration($managerName, $managerConfig);
		}
	}

	public function beforePassCompile(): void
	{
		$config = $this->getConfig();

		// Configure managers
		foreach ($config->managers as $managerName => $managerConfig) {
			$this->beforeManagerCompile($managerName, $managerConfig);
		}
	}

	/**
	 * @phpstan-param TManagerConfig $managerConfig
	 */
	public function loadManagerConfiguration(string $managerName, mixed $managerConfig): void
	{
		$builder = $this->getContainerBuilder();

		// Configuration
		$configuration = $builder->addDefinition($this->prefix(sprintf('managers.%s.configuration', $managerName)))
			->setType($managerConfig->configurationClass)
			->addTag(OrmExtension::CONFIGURATION_TAG, ['name' => $managerName])
			->setAutowired(false);

		// Configuration: enabling lazy native objects
		if ($managerConfig->lazyNativeObjects !== null && method_exists($managerConfig->configurationClass, 'enableNativeLazyObjects')) {
			$configuration->addSetup('enableNativeLazyObjects', [$managerConfig->lazyNativeObjects]);
		}

		// Configuration: proxy dir
		if ($managerConfig->proxyDir !== null) {
			$configuration->addSetup('setProxyDir', [Helpers::expand($managerConfig->proxyDir, $builder->parameters)]);
		}

		// Configuration: auto generate proxy classes
		if (is_bool($managerConfig->autoGenerateProxyClasses)) {
			$defaultStrategy = $this->debugMode === true ? ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS_OR_CHANGED : ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS;
			$configuration->addSetup('setAutoGenerateProxyClasses', [
				$managerConfig->autoGenerateProxyClasses === true ? $defaultStrategy : ProxyFactory::AUTOGENERATE_NEVER,
			]);
		} elseif (is_int($managerConfig->autoGenerateProxyClasses)) {
			$configuration->addSetup('setAutoGenerateProxyClasses', [$managerConfig->autoGenerateProxyClasses]);
		} elseif ($managerConfig->autoGenerateProxyClasses instanceof Statement) {
			$configuration->addSetup('setAutoGenerateProxyClasses', [$managerConfig->autoGenerateProxyClasses]);
		}

		// Configuration: proxy namespace
		if ($managerConfig->proxyNamespace !== null) {
			$configuration->addSetup('setProxyNamespace', [$managerConfig->proxyNamespace]);
		}

		// Configuration: metadata driver
		if ($managerConfig->metadataDriverImpl !== null) {
			$configuration->addSetup('setMetadataDriverImpl', [$managerConfig->metadataDriverImpl]);
		} else {
			// Fallback to ChainMappingDriver
			$configuration->addSetup('setMetadataDriverImpl', [$this->prefix(sprintf('@managers.%s.mappingDriver', $managerName))]);
		}

		// Configuration: resolve target entities
		if ($managerConfig->entityNamespaces !== []) {
			$configuration->addSetup('setEntityNamespaces', [$managerConfig->entityNamespaces]);
		}

		// Configuration: custom functions
		$configuration
			->addSetup('setCustomStringFunctions', [$managerConfig->customStringFunctions])
			->addSetup('setCustomNumericFunctions', [$managerConfig->customNumericFunctions])
			->addSetup('setCustomDatetimeFunctions', [$managerConfig->customDatetimeFunctions])
			->addSetup('setCustomHydrationModes', [$managerConfig->customHydrationModes]);

		// Configuration: class metadata factory name
		if ($managerConfig->classMetadataFactoryName !== null) {
			$configuration->addSetup('setClassMetadataFactoryName', [$managerConfig->classMetadataFactoryName]);
		}

		// Configuration: default repository class name
		if ($managerConfig->defaultRepositoryClassName !== null) {
			$configuration->addSetup('setDefaultRepositoryClassName', [$managerConfig->defaultRepositoryClassName]);
		}

		// Configuration: naming strategy
		if ($managerConfig->namingStrategy !== null) {
			$configuration->addSetup('setNamingStrategy', [SmartStatement::from($managerConfig->namingStrategy)]);
		}

		// Configuration: quote strategy
		if ($managerConfig->quoteStrategy !== null) {
			$configuration->addSetup('setQuoteStrategy', [SmartStatement::from($managerConfig->quoteStrategy)]);
		}

		// Configuration: entity listener resolver
		if ($managerConfig->entityListenerResolver !== null) {
			$configuration->addSetup('setEntityListenerResolver', [SmartStatement::from($managerConfig->entityListenerResolver)]);
		} else {
			$configuration->addSetup('setEntityListenerResolver', [$this->prefix('@entityListenerResolver')]);
		}

		// Configuration: repository factory
		if ($managerConfig->repositoryFactory !== null) {
			$configuration->addSetup('setRepositoryFactory', [SmartStatement::from($managerConfig->repositoryFactory)]);
		}

		// Configuration: default query hints
		if ($managerConfig->defaultQueryHints !== []) {
			$configuration->addSetup('setDefaultQueryHints', [$managerConfig->defaultQueryHints]);
		}

		// Configuration: filters
		if ($managerConfig->filters !== []) {
			foreach ($managerConfig->filters as $filterName => $filter) {
				$configuration->addSetup('addFilter', [$filterName, $filter->class]);
			}
		}

		// Configuration: query cache
		if ($managerConfig->queryCache !== null) {
			$configuration->addSetup('setQueryCache', [SmartStatement::from($managerConfig->queryCache)]);
		} elseif ($managerConfig->defaultCache !== null) {
			$configuration->addSetup('setQueryCache', [SmartStatement::from($managerConfig->defaultCache)]);
		}

		// Configuration: result cache
		if ($managerConfig->resultCache !== null) {
			$configuration->addSetup('setResultCache', [SmartStatement::from($managerConfig->resultCache)]);
		} elseif ($managerConfig->defaultCache !== null) {
			$configuration->addSetup('setResultCache', [SmartStatement::from($managerConfig->defaultCache)]);
		}

		// Configuration: hydration cache
		if ($managerConfig->hydrationCache !== null) {
			$configuration->addSetup('setHydrationCache', [SmartStatement::from($managerConfig->hydrationCache)]);
		} elseif ($managerConfig->defaultCache !== null) {
			$configuration->addSetup('setHydrationCache', [SmartStatement::from($managerConfig->defaultCache)]);
		}

		// Configuration: metadata cache
		if ($managerConfig->metadataCache !== null) {
			$configuration->addSetup('setMetadataCache', [SmartStatement::from($managerConfig->metadataCache)]);
		} elseif ($managerConfig->defaultCache !== null) {
			$configuration->addSetup('setMetadataCache', [SmartStatement::from($managerConfig->defaultCache)]);
		}

		// Configuration: second level cache
		if ($managerConfig->secondLevelCache->enabled) {
			$cache = $managerConfig->secondLevelCache->cache ?? $managerConfig->defaultCache;

			// Validate second level cache
			if ($cache === null) {
				throw new LogicalException('Second level cache is enabled but no cache is set.');
			}

			$regionsConfiguration = $builder->addDefinition($this->prefix(sprintf('managers.%s.secondLevelCache.regionsConfiguration', $managerName)))
				->setFactory(RegionsConfiguration::class)
				->setAutowired(false);

			foreach ($managerConfig->secondLevelCache->regions as $regionName => $region) {
				$regionsConfiguration->addSetup('setLifetime', [$regionName, $region->lifetime]);
				$regionsConfiguration->addSetup('setLockLifetime', [$regionName, $region->lockLifetime]);
			}

			$cacheConfiguration = $builder->addDefinition($this->prefix(sprintf('managers.%s.secondLevelCache.cacheConfiguration', $managerName)))
				->setFactory(CacheConfiguration::class)
				->addSetup('setCacheFactory', [
					new Statement(DefaultCacheFactory::class, [
						$regionsConfiguration,
						SmartStatement::from($cache),
					]),
				])->addSetup('setRegionsConfiguration', [$regionsConfiguration])
				->setAutowired(false);

			if ($managerConfig->secondLevelCache->logger !== null) {
				$cacheConfiguration->addSetup('setCacheLogger', [SmartStatement::from($managerConfig->secondLevelCache->logger)]);
			}

			$configuration->addSetup('setSecondLevelCacheEnabled', [true]);
			$configuration->addSetup('setSecondLevelCacheConfiguration', [$cacheConfiguration]);
		}

		// Entity Manager
		$entityManager = $builder->addDefinition($this->prefix(sprintf('managers.%s.entityManager', $managerName)))
			->setFactory(EntityManager::class, [
				BuilderMan::of($this)->getConnectionByName($managerConfig->connection), // Nettrine/DBAL
				$this->prefix(sprintf('@managers.%s.configuration', $managerName)),
			])
			->addTag(OrmExtension::MANAGER_TAG, ['name' => $managerName])
			->setAutowired($managerName === 'default');

		// EntityManager: enable filters
		if ($managerConfig->filters !== []) {
			foreach ($managerConfig->filters as $filterName => $filter) {
				if ($filter->enabled) {
					$entityManager->addSetup(new Statement('$service->getFilters()->enable(?)', [$filterName]));
				}
			}
		}

		// EntityManager: decorator class
		if ($managerConfig->entityManagerDecoratorClass !== null) {
			$builder->addDefinition($this->prefix(sprintf('managers.%s.entityManagerDecorator', $managerName)))
				->setFactory($managerConfig->entityManagerDecoratorClass, [$entityManager])
				->addTag(OrmExtension::MANAGER_DECORATOR_TAG, ['name' => $managerName])
				->setAutowired($managerName === 'default');

			// Disable autowiring for the original EntityManager
			$entityManager->setAutowired(false);
		}

		// TargetResolvers
		if ($managerConfig->resolveTargetEntities !== []) {
			$resolver = $builder->addDefinition($this->prefix('targetEntityResolver'))
				->setType(ResolveTargetEntityListener::class)
				->setAutowired(false);

			foreach ($managerConfig->resolveTargetEntities as $name => $implementation) {
				$resolver->addSetup('addResolveTargetEntity', [$name, $implementation, []]);
			}
		}

		// MappingDriver
		$mappingDriver = $builder->addDefinition($this->prefix(sprintf('managers.%s.mappingDriver', $managerName)))
			->setFactory(MappingDriverChain::class)
			->addTag(OrmExtension::MAPPING_DRIVER_TAG, ['name' => $managerName])
			->setAutowired(false);

		// Mapping
		foreach ($managerConfig->mapping as $mapping) {
			if ($mapping->type === 'attributes') {
				$mappingDriver->addSetup('addDriver', [
					new Statement(AttributeDriver::class, [array_values($mapping->directories)]),
					$mapping->namespace,
				]);
			} elseif ($mapping->type === 'xml') {
				$mappingDriver->addSetup('addDriver', [
					new Statement(SimplifiedXmlDriver::class, [array_combine($mapping->directories, array_fill(0, count($mapping->directories), $mapping->namespace))]),
					$mapping->namespace,
				]);
			} else {
				throw new LogicalException(sprintf('Unknown mapping type "%s". Only attribute or xml is supported by default.', $mapping->type));
			}
		}
	}

	/**
	 * @phpstan-param TManagerConfig $managerConfig
	 */
	private function beforeManagerCompile(string $managerName, mixed $managerConfig): void
	{
		// No-op
	}

}
