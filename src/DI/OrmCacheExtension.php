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

	/** @var Definition|string|null */
	private $defaultDriverDef;

	private function getServiceSchema(): Schema
	{
		return Expect::anyOf(
			Expect::string(),
			Expect::array(),
			Expect::type(Statement::class)
		)->nullable();
	}

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'defaultDriver' => $this->getServiceSchema(),
			'queryCache' => $this->getServiceSchema(),
			'hydrationCache' => $this->getServiceSchema(),
			'metadataCache' => $this->getServiceSchema(),
			'resultCache' => $this->getServiceSchema(),
			'secondLevelCache' => $this->getServiceSchema(),
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

	private function loadQueryCacheConfiguration(): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setQueryCacheImpl', [
			$this->loadSpecificDriver($config->queryCache, 'queryCache'),
		]);
	}

	private function loadResultCacheConfiguration(): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setResultCacheImpl', [
			$this->loadSpecificDriver($config->resultCache, 'resultCache'),
		]);
	}

	private function loadHydrationCacheConfiguration(): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setHydrationCacheImpl', [
			$this->loadSpecificDriver($config->hydrationCache, 'hydrationCache'),
		]);
	}

	private function loadMetadataCacheConfiguration(): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setMetadataCacheImpl', [
			$this->loadSpecificDriver($config->metadataCache, 'metadataCache'),
		]);
	}

	private function loadSecondLevelCacheConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		if ($config->secondLevelCache !== null) {
			$cacheConfigurationDef = $this->getHelper()->getDefinitionFromConfig($config->secondLevelCache, $this->prefix('cacheConfiguration'));
		} else {
			$regionsDef = $builder->addDefinition($this->prefix('regions'))
				->setFactory(RegionsConfiguration::class)
				->setAutowired(false);

			$cacheFactoryDef = $builder->addDefinition($this->prefix('cacheFactory'))
				->setFactory(DefaultCacheFactory::class)
				->setArguments([
					$regionsDef,
					$this->loadSpecificDriver(null, 'secondLevelCache'),
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
	 * @return Definition|string
	 */
	private function loadSpecificDriver($config, string $prefix)
	{
		if ($config !== null && $config !== []) { // Nette converts explicit null to an empty array
			$driverName = $this->prefix($prefix);
			$driverDef = $this->getHelper()->getDefinitionFromConfig($config, $driverName);

			// If service is extension specific, then disable autowiring
			if ($driverDef instanceof Definition && $driverDef->getName() === $driverName) {
				$driverDef->setAutowired(false);
			}

			return $driverDef;
		}

		return $this->loadDefaultDriver();
	}

	/**
	 * @return Definition|string
	 */
	private function loadDefaultDriver()
	{
		$config = $this->config;

		if ($this->defaultDriverDef !== null) {
			return $this->defaultDriverDef;
		}

		if ($config->defaultDriver === null || $config->defaultDriver === []) { // Nette converts explicit null to an empty array
			return $this->defaultDriverDef = '@' . Cache::class;
		}

		$defaultDriverName = $this->prefix('defaultCache');
		$this->defaultDriverDef = $defaultDriverDef = $this->getHelper()->getDefinitionFromConfig($config->defaultDriver, $defaultDriverName);

		// If service is extension specific, then disable autowiring
		if ($defaultDriverDef instanceof Definition && $defaultDriverDef->getName() === $defaultDriverName) {
			$defaultDriverDef->setAutowired(false);
		}

		return $defaultDriverDef;
	}

}
