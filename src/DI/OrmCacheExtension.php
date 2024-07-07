<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Contributte\Psr6\CachePool;
use Contributte\Psr6\CachePoolFactory;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;
use Throwable;

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

	public function beforeCompile(): void
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

		$configurationDef->addSetup('setQueryCache', [
			$this->buildCacheDriver($config->queryCache, 'queryCache'),
		]);
	}

	private function loadResultCacheConfiguration(): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setResultCache', [
			$this->buildCacheDriver($config->resultCache, 'resultCache'),
		]);
	}

	private function loadHydrationCacheConfiguration(): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setHydrationCache', [
			$this->buildCacheDriver($config->hydrationCache, 'hydrationCache'),
		]);
	}

	private function loadMetadataCacheConfiguration(): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setMetadataCache', [
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
	private function buildCacheDriver(string|array|Statement|null $config, string $prefix): Definition
	{
		$builder = $this->getContainerBuilder();

		// Driver is defined
		if ($config !== null && $config !== []) { // Nette converts explicit null to an empty array
			return $this->buildCacheDriverDefinition($config, $prefix);
		}

		// If there is default cache, don't create it
		if ($builder->hasDefinition($this->prefix('defaultCache'))) {
			return $builder->getDefinition($this->prefix('defaultCache'));
		}

		return $this->buildCacheDriverDefinition($this->config->defaultDriver, 'defaultCache');
	}

	/**
	 * @param string|mixed[]|Statement|null $config
	 */
	private function buildCacheDriverDefinition(string|array|Statement|null $config, string $prefix): Definition
	{
		$builder = $this->getContainerBuilder();

		// Driver is defined
		if ($config !== null && $config !== []) { // Nette converts explicit null to an empty array
			if (is_string($config)) {
				$config = $this->resolveCacheDriverDefinitionString($config, $this->prefix($prefix));
			}

			if ($config instanceof Statement) {
				$entity = $config->getEntity();

				if (is_string($entity) && is_a($entity, Storage::class, true)) {
					$entity = Cache::class;
					$config = new Statement(
						$entity,
						[
							'storage' => $config,
							'namespace' => $this->prefix($prefix),
						]
					);
				}

				if (is_string($entity) && is_a($entity, Cache::class, true)) {
					return $builder->addDefinition($this->prefix($prefix))
						->setFactory(new Statement(CachePool::class, [$config]))
						->setAutowired(false);
				}
			}

			return $builder->addDefinition($this->prefix($prefix))
				->setFactory($config)
				->setAutowired(false);
		}

		// No default driver provided, create CacheItemPoolInterface with autowired Storage

		// ICachePoolFactory doesn't have to be registered in DI container
		if ($builder->hasDefinition($this->prefix('cachePoolFactory')) === false) {
			$builder->addDefinition($this->prefix('cachePoolFactory'))
				->setFactory(CachePoolFactory::class)
				->setAutowired(false);
		}

		return $builder->addDefinition($this->prefix($prefix))
			->setFactory('@' . $this->prefix('cachePoolFactory') . '::create', [$this->prefix($prefix)])
			->setAutowired(false);
	}

	private function resolveCacheDriverDefinitionString(string $config, string $cacheNamespace): string|Statement
	{
		$builder = $this->getContainerBuilder();

		if (str_starts_with($config, '@')) {
			$service = substr($config, 1);

			if ($builder->hasDefinition($service)) {
				$definition = $builder->getDefinition($service);
			} else {
				try {
					$definition = $builder->getDefinitionByType($service);
				} catch (Throwable) {
					$definition = null;
				}
			}

			$type = $definition?->getType();

			if ($type === null) {
				return $config;
			}

			if (is_a($type, Storage::class, true)) {
				return new Statement(
					Cache::class,
					[
						'storage' => $config,
						'namespace' => $cacheNamespace,
					]
				);
			}

			if (is_a($type, Cache::class, true)) {
				return new Statement(CachePool::class, [$config]);
			}

			return $config;
		}

		if (is_a($config, Storage::class, true)) {
			return new Statement($config);
		}

		return $config;
	}

}
