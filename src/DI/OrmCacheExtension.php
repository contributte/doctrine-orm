<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Nette\InvalidStateException;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nettrine\ORM\DI\Helpers\CacheBuilder;
use stdClass;

/**
 * @property-read stdClass $config
 */
class OrmCacheExtension extends AbstractExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'defaultDriver' => Expect::string('filesystem')->nullable(),
			'queryCache' => Expect::string(),
			'hydrationCache' => Expect::string(),
			'metadataCache' => Expect::string(),
			'resultCache' => Expect::string(),
			'secondLevelCache' => Expect::array()->default(null),
		]);
	}

	public function loadConfiguration(): void
	{
		// Validates needed extension
		$this->validate();

		$this->loadQueryCacheConfiguration();
		$this->loadHydrationCacheConfiguration();
		$this->loadResultCacheConfiguration();
		$this->loadMetadataCacheConfiguration();
		$this->loadSecondLevelCacheConfiguration();
	}

	public function loadQueryCacheConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		if ($config->queryCache === null && $config->defaultDriver !== null) {
			$configurationDef->addSetup('setQueryCacheImpl', [
					CacheBuilder::of($this)
						->withDefault($config->defaultDriver)
						->getDefinition('queryCache')]);
		} elseif ($config->queryCache !== null) {
			$builder->addDefinition($this->prefix('queryCache'))
				->setFactory($config->queryCache);

			$configurationDef->addSetup('setQueryCacheImpl', [$this->prefix('@queryCache')]);
		} else {
			throw new InvalidStateException('QueryCache or defaultDriver must be provided');
		}
	}

	public function loadResultCacheConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		if ($config->resultCache === null && $config->defaultDriver !== null) {
			$configurationDef->addSetup('setResultCacheImpl', [
					CacheBuilder::of($this)
						->withDefault($config->defaultDriver)
						->getDefinition('resultCache')]);
		} elseif ($config->resultCache !== null) {
			$builder->addDefinition($this->prefix('resultCache'))
				->setFactory($config->resultCache);

			$configurationDef->addSetup('setResultCacheImpl', [$this->prefix('@hydrationCache')]);
		} else {
			throw new InvalidStateException('ResultCache or defaultDriver must be provided');
		}
	}

	public function loadHydrationCacheConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		if ($config->hydrationCache === null && $config->defaultDriver !== null) {
			$configurationDef->addSetup('setHydrationCacheImpl', [
					CacheBuilder::of($this)
						->withDefault($config->defaultDriver)
						->getDefinition('hydrationCache')]);
		} elseif ($config->hydrationCache !== null) {
			$builder->addDefinition($this->prefix('hydrationCache'))
				->setFactory($config->hydrationCache);

			$configurationDef->addSetup('setHydrationCacheImpl', [$this->prefix('@hydrationCache')]);
		} else {
			throw new InvalidStateException('HydrationCache or defaultDriver must be provided');
		}
	}

	public function loadMetadataCacheConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		if ($config->metadataCache === null && $config->defaultDriver !== null) {
			$configurationDef->addSetup('setMetadataCacheImpl', [
					CacheBuilder::of($this)
						->withDefault($config->defaultDriver)
						->getDefinition('metadataCache')]);
		} elseif ($config->metadataCache !== null) {
			$builder->addDefinition($this->prefix('metadataCache'))
				->setFactory($config->metadataCache);

			$configurationDef->addSetup('setMetadataCacheImpl', [$this->prefix('@metadataCache')]);
		} else {
			throw new InvalidStateException('MetadataCache or defaultDriver must be provided');
		}
	}

	public function loadSecondLevelCacheConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		if ($config->secondLevelCache === null && $config->defaultDriver !== null) {
			$regionsDef = $builder->addDefinition($this->prefix('regions'))
				->setFactory(RegionsConfiguration::class)
				->setAutowired(false);

			$cacheFactoryDef = $builder->addDefinition($this->prefix('cacheFactory'))
				->setFactory(DefaultCacheFactory::class)
				->setArguments([
					$regionsDef,
					CacheBuilder::of($this)
						->withDefault($config->defaultDriver)
						->getDefinition('secondLevelCache'),
				])
				->setAutowired(false);

			$cacheConfigurationDef = $builder->addDefinition($this->prefix('cacheConfiguration'))
				->setFactory(CacheConfiguration::class)
				->addSetup('setCacheFactory', [$cacheFactoryDef])
				->setAutowired(false);

			$configurationDef->addSetup('setSecondLevelCacheEnabled', [true]);
			$configurationDef->addSetup('setSecondLevelCacheConfiguration', [$cacheConfigurationDef]);
		} elseif ($config->secondLevelCache !== null) {
			$configurationDef->addSetup('setSecondLevelCacheEnabled', [true]);
			$configurationDef->addSetup('setSecondLevelCacheConfiguration', [$config->secondLevelCache]);
		}
	}

}
