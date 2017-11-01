<?php

namespace Nettrine\ORM\DI;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Configuration;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nettrine\ORM\EntityManagerFactory;
use Nettrine\ORM\Mapping\AnnotationDriver;

final class OrmExtension extends CompilerExtension
{

	/** @var mixed[] */
	private $defaults = [
		'configuration' => [
			'proxyDir' => '%tempDir%/proxies',
			'autoGenerateProxyClasses' => NULL,
			'proxyNamespace' => 'Doctrine\Proxy',
			'metadataDriverImpl' => NULL,
			'entityNamespaces' => [],
			'queryCacheImpl' => NULL,
			'hydrationCacheImpl' => NULL,
			'metadataCacheImpl' => NULL,
			//TODO named query
			//TODO named native query
			'customStringFunctions' => [],
			'customNumericFunctions' => [],
			'customDatetimeFunctions' => [],
			'customHydrationModes' => [],
			'classMetadataFactoryName' => NULL,
			//TODO filters
			'defaultRepositoryClassName' => NULL,
			'namingStrategy' => NULL,
			'quoteStrategy' => NULL,
			'entityListenerResolver' => NULL,
			'repositoryFactory' => NULL,
			'isSecondLevelCacheEnabled' => FALSE,
			'secondLevelCacheConfiguration' => NULL,
			'defaultQueryHints' => [],
		],
	];

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$this->validateConfig($this->defaults);
		$config = $this->validateConfig($this->defaults['configuration'], $this->config['configuration']);
		$config = Helpers::expand($config, $builder->parameters);

		$configuration = $builder->addDefinition($this->prefix('configuration'))
			->setClass(Configuration::class)
			->setAutowired(FALSE);

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
		} else {
			if ($builder->getByType(AnnotationReader::class) === NULL) {
				throw new \Exception('AnnotationReader missing in DIC, please use Nettrine/Annotatitions 
				or implement own MetadataProvider.');
			}
			$annotationDriver = $builder->addDefinition($this->prefix('annotationDriver'))
				->setClass(AnnotationDriver::class, [1 => [$builder->expand('%appDir%')]]);
			$configuration->addSetup('setMetadataDriverImpl', [$annotationDriver]);
		}
		$configuration->addSetup('setEntityNamespaces', [$config['entityNamespaces']]);

		// Cache configuration
		if ($config['queryCacheImpl'] !== NULL) {
			$configuration->addSetup('setQueryCacheImpl', [$config['queryCacheImpl']]);
		}
		if ($config['hydrationCacheImpl'] !== NULL) {
			$configuration->addSetup('setHydrationCacheImpl', [$config['hydrationCacheImpl']]);
		}
		if ($config['metadataCacheImpl'] !== NULL) {
			$configuration->addSetup('setMetadataCacheImpl', [$config['metadataCacheImpl']]);
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
			$configuration->addSetup('setNamingStrategy', [$config['namingStrategy']]);
		}
		if ($config['quoteStrategy'] !== NULL) {
			$configuration->addSetup('setQuoteStrategy', [$config['quoteStrategy']]);
		}
		if ($config['entityListenerResolver'] !== NULL) {
			$configuration->addSetup('setEntityListenerResolver', [$config['entityListenerResolver']]);
		}
		if ($config['repositoryFactory'] !== NULL) {
			$configuration->addSetup('setRepositoryFactory', [$config['repositoryFactory']]);
		}

		// Second level cache
		$configuration->addSetup('setSecondLevelCacheEnabled', [$config['isSecondLevelCacheEnabled']]);
		if ($config['secondLevelCacheConfiguration'] !== NULL) {
			$configuration->addSetup('setSecondLevelCacheConfiguration', [$config['secondLevelCacheConfiguration']]);
		}

		$configuration->addSetup('setDefaultQueryHints', [$config['defaultQueryHints']]);

		// Entity Manager
		$builder->addDefinition($this->prefix('entityManager'))
			->setFactory(EntityManagerFactory::class . '::create', [1 => $configuration,]);
	}

}
