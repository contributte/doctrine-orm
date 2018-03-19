<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Exception;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\DI\Statement;
use Nette\InvalidStateException;
use Nettrine\ORM\EntityManager;
use Nettrine\ORM\EntityManagerFactory;
use Nettrine\ORM\Mapping\ContainerEntityListenerResolver;

final class OrmExtension extends CompilerExtension
{

	/** @var mixed[] */
	private $defaults = [
		'entityManagerClass' => EntityManager::class,
		'configuration' => [
			'proxyDir' => '%tempDir%/proxies',
			'autoGenerateProxyClasses' => NULL,
			'proxyNamespace' => 'Nettrine\Proxy',
			'metadataDriverImpl' => NULL,
			'entityNamespaces' => [],
			//TODO named query
			//TODO named native query
			'customStringFunctions' => [],
			'customNumericFunctions' => [],
			'customDatetimeFunctions' => [],
			'customHydrationModes' => [],
			'classMetadataFactoryName' => NULL,
			//TODO filters
			'defaultRepositoryClassName' => NULL,
			'namingStrategy' => UnderscoreNamingStrategy::class,
			'quoteStrategy' => NULL,
			'entityListenerResolver' => NULL,
			'repositoryFactory' => NULL,
			'defaultQueryHints' => [],
		],
	];

	/**
	 * @return void
	 * @throws Exception
	 */
	public function loadConfiguration(): void
	{
		$this->validateConfig($this->defaults);
		$this->loadDoctrineConfiguration();
		$this->loadEntityManagerConfiguration();
	}

	/**
	 * @return void
	 */
	public function loadDoctrineConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults['configuration'], $this->config['configuration']);
		$config = Helpers::expand($config, $builder->parameters);

		$configuration = $builder->addDefinition($this->prefix('configuration'))
			->setClass(Configuration::class);

		if ($config['proxyDir'] !== NULL) {
			$configuration->addSetup('setProxyDir', [$config['proxyDir']]);
		}
		if ($config['autoGenerateProxyClasses'] !== NULL) {
			$configuration->addSetup('setAutoGenerateProxyClasses', [$config['autoGenerateProxyClasses']]);
		}
		if ($config['proxyNamespace'] !== NULL) {
			$configuration->addSetup('setProxyNamespace', [$config['proxyNamespace']]);
		}
		if ($config['metadataDriverImpl'] !== NULL) {
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

		if ($config['classMetadataFactoryName'] !== NULL) {
			$configuration->addSetup('setClassMetadataFactoryName', [$config['classMetadataFactoryName']]);
		}
		if ($config['defaultRepositoryClassName'] !== NULL) {
			$configuration->addSetup('setDefaultRepositoryClassName', [$config['defaultRepositoryClassName']]);
		}

		if ($config['namingStrategy'] !== NULL) {
			$configuration->addSetup('setNamingStrategy', [new Statement($config['namingStrategy'])]);
		}
		if ($config['quoteStrategy'] !== NULL) {
			$configuration->addSetup('setQuoteStrategy', [$config['quoteStrategy']]);
		}
		if ($config['entityListenerResolver'] !== NULL) {
			$configuration->addSetup('setEntityListenerResolver', [$config['entityListenerResolver']]);
		} else {
			$builder->addDefinition($this->prefix('entityListenerResolver'))
				->setClass(ContainerEntityListenerResolver::class);
			$configuration->addSetup('setEntityListenerResolver', [$this->prefix('@entityListenerResolver')]);
		}
		if ($config['repositoryFactory'] !== NULL) {
			$configuration->addSetup('setRepositoryFactory', [$config['repositoryFactory']]);
		}
		if ($config['defaultQueryHints']) {
			$configuration->addSetup('setDefaultQueryHints', [$config['defaultQueryHints']]);
		}
	}

	/**
	 * @return void
	 */
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
			->setClass($entityManagerClass)
			->setFactory(EntityManagerFactory::class . '::create', [
				$builder->getDefinitionByType(Connection::class), // Nettrine/DBAL
				$this->prefix('@configuration'),
				$builder->getDefinitionByType(EventManager::class), // Nettrine/DBAL
				$entityManagerClass,
			]);
	}

}
