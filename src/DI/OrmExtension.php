<?php

namespace Nettrine\ORM\DI;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand;
use Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand;
use Doctrine\ORM\Tools\Console\Command\InfoCommand;
use Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand;
use Doctrine\ORM\Tools\Console\Command\RunDqlCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\DI\Statement;
use Nettrine\ORM\EntityManagerFactory;
use Nettrine\ORM\Mapping\AnnotationDriver;
use Symfony\Component\Console\Application;

final class OrmExtension extends CompilerExtension
{

	/** @var mixed[] */
	private $defaults = [
		'configuration' => [
			'proxyDir' => '%tempDir%/cache/proxies',
			'autoGenerateProxyClasses' => NULL,
			'proxyNamespace' => 'Nettrine\Proxy',
			'metadataDriverImpl' => ArrayCache::class,
			'entityNamespaces' => [],
			'queryCacheImpl' => ArrayCache::class,
			'hydrationCacheImpl' => ArrayCache::class,
			'metadataCacheImpl' => ArrayCache::class,
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
				throw new \Exception('AnnotationReader missing in DIC, please use Nettrine/Annotations 
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

		// Skip if it's not CLI mode
		if (PHP_SAPI !== 'cli')
			return;

		// Helpers
		$builder->addDefinition($this->prefix('entityManagerHelper'))
			->setClass(EntityManagerHelper::class)
			->setAutowired(FALSE);

		// Commands
		$builder->addDefinition($this->prefix('schemaToolCreateCommand'))
			->setClass(CreateCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('schemaToolUpdateCommand'))
			->setClass(UpdateCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('schemaToolDropCommand'))
			->setClass(DropCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('convertMappingCommand'))
			->setClass(ConvertMappingCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('ensureProductionSettingsCommand'))
			->setClass(EnsureProductionSettingsCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('generateEntitiesCommand'))
			->setClass(GenerateEntitiesCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('generateProxiesCommand'))
			->setClass(GenerateProxiesCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('generateRepositoriesCommand'))
			->setClass(GenerateRepositoriesCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('infoCommand'))
			->setClass(InfoCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('mappingDescribeCommand'))
			->setClass(MappingDescribeCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('runDqlCommand'))
			->setClass(RunDqlCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('validateSchemaCommand'))
			->setClass(ValidateSchemaCommand::class)
			->setAutowired(FALSE);
	}

	/**
	 * Decorate services
	 *
	 * @return void
	 */
	public function beforeCompile()
	{
		// Skip if it's not CLI mode
		if (PHP_SAPI !== 'cli')
			return;

		$builder = $this->getContainerBuilder();
		$application = $builder->getDefinitionByType(Application::class);

		// Register helpers
		$entityManagerHelper = '@' . $this->prefix('entityManagerHelper');
		$application->addSetup(new Statement('$service->getHelperSet()->set(?,?)', [$entityManagerHelper, 'em']));
	}

}
