<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Definitions\Statement;
use Nette\DI\Helpers;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\ORM\Decorator\SimpleEntityManagerDecorator;
use Nettrine\ORM\DI\Definitions\SmartStatement;
use Nettrine\ORM\Exception\Logical\InvalidArgumentException;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use Nettrine\ORM\ManagerProvider;
use Nettrine\ORM\ManagerRegistry;
use Nettrine\ORM\Mapping\ContainerEntityListenerResolver;
use stdClass;
use Tracy\Debugger;

/**
 * @property-read stdClass $config
 */
final class OrmExtension extends AbstractExtension
{

	public const MAPPING_DRIVER_TAG = 'nettrine.orm.mapping.driver';

	public function __construct(private ?bool $debugMode = null)
	{
		if ($this->debugMode === null) {
			$this->debugMode = class_exists(Debugger::class) && Debugger::$productionMode === false;
		}
	}

	public function getConfigSchema(): Schema
	{
		$parameters = $this->getContainerBuilder()->parameters;
		$proxyDir = isset($parameters['tempDir']) ? $parameters['tempDir'] . '/proxies' : null;

		return Expect::structure([
			'entityManagerDecoratorClass' => Expect::string(SimpleEntityManagerDecorator::class),
			'configurationClass' => Expect::string(Configuration::class),
			'configuration' => Expect::structure([
				'proxyDir' => Expect::string($proxyDir)->nullable(),
				'autoGenerateProxyClasses' => Expect::anyOf(Expect::int(), Expect::bool(), Expect::type(Statement::class))->default(true),
				'proxyNamespace' => Expect::string('Nettrine\Proxy')->nullable(),
				'metadataDriverImpl' => Expect::string(),
				'entityNamespaces' => Expect::array(),
				'resolveTargetEntities' => Expect::array(),
				'customStringFunctions' => Expect::array(),
				'customNumericFunctions' => Expect::array(),
				'customDatetimeFunctions' => Expect::array(),
				'customHydrationModes' => Expect::array(),
				'classMetadataFactoryName' => Expect::string(),
				'defaultRepositoryClassName' => Expect::string(),
				'namingStrategy' => Expect::anyOf(Expect::string(), Expect::type(Statement::class))->default(UnderscoreNamingStrategy::class),
				'quoteStrategy' => Expect::anyOf(Expect::string(), Expect::type(Statement::class)),
				'entityListenerResolver' => Expect::anyOf(Expect::string(), Expect::type(Statement::class)),
				'repositoryFactory' => Expect::anyOf(Expect::string(), Expect::type(Statement::class)),
				'defaultQueryHints' => Expect::array(),
				'filters' => Expect::arrayOf(
					Expect::structure([
						'class' => Expect::string()->required(),
						'enabled' => Expect::bool(false),
					])
				),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		$this->loadDoctrineConfiguration();
		$this->loadEntityManagerConfiguration();
		$this->loadMappingConfiguration();
	}

	public function loadDoctrineConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$globalConfig = $this->config;
		$config = $globalConfig->configuration;

		// @validate configuration class is subclass of origin one
		$configurationClass = $globalConfig->configurationClass;
		assert(is_string($configurationClass));

		if (!is_a($configurationClass, Configuration::class, true)) {
			throw new InvalidArgumentException('Configuration class must be subclass of ' . Configuration::class . ', ' . $configurationClass . ' given.');
		}

		$configuration = $builder->addDefinition($this->prefix('configuration'))
			->setType($configurationClass);

		if ($config->proxyDir !== null) {
			$configuration->addSetup('setProxyDir', [Helpers::expand($config->proxyDir, $builder->parameters)]);
		}

		if (is_bool($config->autoGenerateProxyClasses)) {
			$defaultStrategy = $this->debugMode === true ? AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS_OR_CHANGED : AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS;
			$configuration->addSetup('setAutoGenerateProxyClasses', [
				$config->autoGenerateProxyClasses === true ? $defaultStrategy : AbstractProxyFactory::AUTOGENERATE_NEVER,
			]);
		} elseif (is_int($config->autoGenerateProxyClasses)) {
			$configuration->addSetup('setAutoGenerateProxyClasses', [$config->autoGenerateProxyClasses]);
		} elseif ($config->autoGenerateProxyClasses instanceof Statement) {
			$configuration->addSetup('setAutoGenerateProxyClasses', [$config->autoGenerateProxyClasses]);
		}

		if ($config->proxyNamespace !== null) {
			$configuration->addSetup('setProxyNamespace', [$config->proxyNamespace]);
		}

		if ($config->metadataDriverImpl !== null) {
			$configuration->addSetup('setMetadataDriverImpl', [$config->metadataDriverImpl]);
		} else {
			$configuration->addSetup('setMetadataDriverImpl', [$this->prefix('@mappingDriver')]);
		}

		if ($config->entityNamespaces !== []) {
			$configuration->addSetup('setEntityNamespaces', [$config->entityNamespaces]);
		}

		// Custom functions
		$configuration
			->addSetup('setCustomStringFunctions', [$config->customStringFunctions])
			->addSetup('setCustomNumericFunctions', [$config->customNumericFunctions])
			->addSetup('setCustomDatetimeFunctions', [$config->customDatetimeFunctions])
			->addSetup('setCustomHydrationModes', [$config->customHydrationModes]);

		if ($config->classMetadataFactoryName !== null) {
			$configuration->addSetup('setClassMetadataFactoryName', [$config->classMetadataFactoryName]);
		}

		if ($config->defaultRepositoryClassName !== null) {
			$configuration->addSetup('setDefaultRepositoryClassName', [$config->defaultRepositoryClassName]);
		}

		if ($config->namingStrategy !== null) {
			$configuration->addSetup('setNamingStrategy', [SmartStatement::from($config->namingStrategy)]);
		}

		if ($config->quoteStrategy !== null) {
			$configuration->addSetup('setQuoteStrategy', [SmartStatement::from($config->quoteStrategy)]);
		}

		if ($config->entityListenerResolver !== null) {
			$configuration->addSetup('setEntityListenerResolver', [SmartStatement::from($config->entityListenerResolver)]);
		} else {
			$builder->addDefinition($this->prefix('entityListenerResolver'))
				->setType(ContainerEntityListenerResolver::class);
			$configuration->addSetup('setEntityListenerResolver', [$this->prefix('@entityListenerResolver')]);
		}

		if ($config->repositoryFactory !== null) {
			$configuration->addSetup('setRepositoryFactory', [SmartStatement::from($config->repositoryFactory)]);
		}

		if ($config->defaultQueryHints !== []) {
			$configuration->addSetup('setDefaultQueryHints', [$config->defaultQueryHints]);
		}

		if ($config->filters !== []) {
			foreach ($config->filters as $filterName => $filter) {
				$configuration->addSetup('addFilter', [$filterName, $filter->class]);
			}
		}
	}

	public function loadEntityManagerConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		// @validate entity manager decorator has a real class
		$entityManagerDecoratorClass = $config->entityManagerDecoratorClass;

		if (!class_exists($entityManagerDecoratorClass)) {
			throw new InvalidStateException(sprintf('EntityManagerDecorator class "%s" not found', $entityManagerDecoratorClass));
		}

		// Entity Manager
		$original = new Statement(DoctrineEntityManager::class . '::create', [
			$builder->getDefinitionByType(Connection::class), // Nettrine/DBAL
			$this->prefix('@configuration'),
		]);

		// Entity Manager Decorator
		$decorator = $builder->addDefinition($this->prefix('entityManagerDecorator'))
			->setFactory($entityManagerDecoratorClass, [$original]);

		// Configuration filters
		if ($config->configuration->filters !== []) {
			foreach ($config->configuration->filters as $filterName => $filter) {
				if ($filter->enabled) {
					$decorator->addSetup(new Statement('$service->getFilters()->enable(?)', [$filterName]));
				}
			}
		}

		if ($config->configuration->resolveTargetEntities !== []) {
			$resolver = $builder->addDefinition($this->prefix('targetEntityResolver'))
				->setType(ResolveTargetEntityListener::class);

			foreach ($config->configuration->resolveTargetEntities as $name => $implementation) {
				$resolver->addSetup('addResolveTargetEntity', [$name, $implementation, []]);
			}
		}

		// Manager Registry
		$builder->addDefinition($this->prefix('managerRegistry'))
			->setFactory(ManagerRegistry::class, [
				'@' . Connection::class,
				$this->prefix('@entityManagerDecorator'),
			]);

		// Manager Provider
		$builder->addDefinition($this->prefix('managerProvider'))
			->setFactory(ManagerProvider::class, [
				$this->prefix('@managerRegistry'),
			]);
	}

	public function loadMappingConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		// Driver Chain
		$builder->addDefinition($this->prefix('mappingDriver'))
			->setFactory(MappingDriverChain::class)
			->addTag(self::MAPPING_DRIVER_TAG);
	}

}
