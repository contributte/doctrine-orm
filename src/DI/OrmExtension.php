<?php

declare(strict_types = 1);

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
				->setClass(ContainerEntityListenerResolver::class);

			$configuration->addSetup('setEntityListenerResolver', [$this->prefix('@entityListenerResolver')]);
		}
		if ($config['repositoryFactory'] !== null) {
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


		$dbalConnection = $builder->findByType(\Doctrine\DBAL\Connection::class);
		$connections = [];

		foreach ($builder->findByType(\Doctrine\DBAL\Connection::class) as $k => $connection) {
			$match = \Nette\Utils\Strings::match($k, '#([a-zA-Z]+\.([a-zA-Z]+))\.connection#');

			if ($connection->getTag(\Nettrine\DBAL\DI\DbalExtension::TAG_CONNECTION) !== null && array_key_exists(1, $match) && is_string($match[1])) {
				$nameWithPrefix = $match[1];
				$name = $match[2];

			} else {
				continue;
			}

			$autowired = $name === \Nettrine\DBAL\DI\DbalExtension::DEFAULT_CONNECTION_NAME ? true : false;

			// Entity Manager
			$builder->addDefinition($this->prefix($name . '.entityManager'))
				->setClass($entityManagerClass)
				->setFactory(EntityManagerFactory::class . '::create', [
					$builder->getDefinition($nameWithPrefix . '.connection'), // Nettrine/DBAL
					$this->prefix('@configuration'),
					$builder->getDefinition($nameWithPrefix . '.eventManager'), // Nettrine/DBAL
					$entityManagerClass,
				])
				->setAutowired($autowired);

			// ManagerRegistry
			$builder->addDefinition($this->prefix($name . '.managerRegistry'))
				->setClass(ManagerRegistry::class)
				->setArguments([
					$builder->getDefinition($nameWithPrefix . '.connection'),
					$this->prefix('@' . $name . '.entityManager'),
				])
				->setAutowired($autowired);
		}
	}
}
