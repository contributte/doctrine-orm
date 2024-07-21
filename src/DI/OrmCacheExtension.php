<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;

/**
 * @property-read stdClass $config
 */
class OrmCacheExtension extends AbstractExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'defaultDriver' => $this->getServiceSchema(),
			'queryCache' => $this->getServiceSchema(),
			'hydrationCache' => $this->getServiceSchema(),
			'metadataCache' => $this->getServiceSchema(),
			'resultCache' => $this->getServiceSchema(),
			'secondLevelCache' => Expect::anyOf($this->getServiceSchema(), false),
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

	private function getServiceSchema(): Schema
	{
		return Expect::anyOf(
			Expect::string(),
			Expect::array(),
			Expect::type(Statement::class)
		)->nullable();
	}

	private function loadQueryCacheConfiguration(): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setQueryCacheImpl', [
			$this->buildCacheDriver($config->queryCache, 'queryCache'),
		]);
	}

	private function loadResultCacheConfiguration(): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setResultCacheImpl', [
			$this->buildCacheDriver($config->resultCache, 'resultCache'),
		]);
	}

	private function loadHydrationCacheConfiguration(): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setHydrationCacheImpl', [
			$this->buildCacheDriver($config->hydrationCache, 'hydrationCache'),
		]);
	}

	private function loadMetadataCacheConfiguration(): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setMetadataCacheImpl', [
			$this->buildCacheDriver($config->metadataCache, 'metadataCache'),
		]);
	}

	private function loadSecondLevelCacheConfiguration(): void
	{
		$config = $this->config;

		if ($config->secondLevelCache === false) {
			return;
		}

		$builder = $this->getContainerBuilder();
		$configurationDef = $this->getConfigurationDef();

		if ($config->secondLevelCache !== null) {
			$cacheConfigurationDef = $builder->addDefinition($this->prefix('cacheConfiguration'))
				->setFactory($config->secondLevelCache);
		} else {
			$regionsDef = $builder->addDefinition($this->prefix('regions'))
				->setFactory(RegionsConfiguration::class)
				->setAutowired(false);

			$cacheFactoryDef = $builder->addDefinition($this->prefix('cacheFactory'))
				->setFactory(DefaultCacheFactory::class)
				->setArguments([
					$regionsDef,
					$this->buildCacheDriver(null, 'secondLevelCache'),
				])
				->setAutowired(false);

			$cacheConfigurationDef = $builder->addDefinition($this->prefix('cacheConfiguration'))
				->setFactory(CacheConfiguration::class)
				->addSetup('setCacheFactory', [$cacheFactoryDef])
				->setAutowired(false);
		}

		$configurationDef->addSetup('setSecondLevelCacheEnabled', [
			true,
		]);
		$configurationDef->addSetup('setSecondLevelCacheConfiguration', [$cacheConfigurationDef]);
	}

	/**
	 * @param string|mixed[]|Statement|null $config
	 */
	private function buildCacheDriver(string|array|Statement|null $config, string $prefix): Definition|string
	{
		$builder = $this->getContainerBuilder();

		// Driver is defined
		if ($config !== null && $config !== []) { // Nette converts explicit null to an empty array
			return $builder->addDefinition($this->prefix($prefix))
				->setFactory($config)
				->setAutowired(false);
		}

		// If there is default cache, don't create it
		if ($builder->hasDefinition($this->prefix('defaultCache'))) {
			return $builder->getDefinition($this->prefix('defaultCache'));
		}

		// Create default driver
		if ($this->config->defaultDriver !== null && $this->config->defaultDriver !== []) { // Nette converts explicit null to an empty array
			return $builder->addDefinition($this->prefix('defaultCache'))
				->setFactory($this->config->defaultDriver)
				->setAutowired(false);
		}

		// No default driver provider, fallback to Cache::class
		return '@' . Cache::class;
	}

}
