<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Contributte\DI\Helper\ExtensionDefinitionsHelper;
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
			'secondLevelCache' => Expect::array()->default(null),
		]);
	}

	public function loadConfiguration(): void
	{
		// Validates needed extension
		$this->validate();

		$definitionsHelper = new ExtensionDefinitionsHelper($this->compiler);

		$this->loadQueryCacheConfiguration($definitionsHelper);
		$this->loadHydrationCacheConfiguration($definitionsHelper);
		$this->loadResultCacheConfiguration($definitionsHelper);
		$this->loadMetadataCacheConfiguration($definitionsHelper);
		$this->loadSecondLevelCacheConfiguration($definitionsHelper);
	}

	/**
	 * @return Definition|string
	 */
	private function loadDefaultDriver(ExtensionDefinitionsHelper $definitionsHelper)
	{
		$config = $this->config;

		if ($this->defaultDriverDef !== null) {
			return $this->defaultDriverDef;
		}

		if ($config->defaultDriver === null || $config->defaultDriver === []) { // Nette converts explicit null to an empty array
			return $this->defaultDriverDef = '@' . Cache::class;
		}

		$defaultDriverName = $this->prefix('defaultCache');
		$this->defaultDriverDef = $defaultDriverDef = $definitionsHelper->getDefinitionFromConfig($config->defaultDriver, $defaultDriverName);

		// If service is extension specific, then disable autowiring
		if ($defaultDriverDef instanceof Definition && $defaultDriverDef->getName() === $defaultDriverName) {
			$defaultDriverDef->setAutowired(false);
		}

		return $defaultDriverDef;
	}

	/**
	 * @param string|mixed[]|Statement|null $config
	 * @return Definition|string
	 */
	private function loadSpecificDriver(ExtensionDefinitionsHelper $definitionsHelper, $config, string $prefix)
	{
		if ($config !== null && $config !== []) { // Nette converts explicit null to an empty array
			$driverName = $this->prefix($prefix);
			$driverDef = $definitionsHelper->getDefinitionFromConfig($config, $driverName);

			// If service is extension specific, then disable autowiring
			if ($driverDef instanceof Definition && $driverDef->getName() === $driverName) {
				$driverDef->setAutowired(false);
			}

			return $driverDef;
		}

		return $this->loadDefaultDriver($definitionsHelper);
	}

	private function loadQueryCacheConfiguration(ExtensionDefinitionsHelper $definitionsHelper): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setQueryCacheImpl', [
			$this->loadSpecificDriver($definitionsHelper, $config->queryCache, 'queryCache'),
		]);
	}

	private function loadResultCacheConfiguration(ExtensionDefinitionsHelper $definitionsHelper): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setResultCacheImpl', [
			$this->loadSpecificDriver($definitionsHelper, $config->resultCache, 'resultCache'),
		]);
	}

	private function loadHydrationCacheConfiguration(ExtensionDefinitionsHelper $definitionsHelper): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setHydrationCacheImpl', [
			$this->loadSpecificDriver($definitionsHelper, $config->hydrationCache, 'hydrationCache'),
		]);
	}

	private function loadMetadataCacheConfiguration(ExtensionDefinitionsHelper $definitionsHelper): void
	{
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		$configurationDef->addSetup('setMetadataCacheImpl', [
			$this->loadSpecificDriver($definitionsHelper, $config->metadataCache, 'metadataCache'),
		]);
	}

	private function loadSecondLevelCacheConfiguration(ExtensionDefinitionsHelper $definitionsHelper): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;
		$configurationDef = $this->getConfigurationDef();

		if ($config->secondLevelCache !== null) {
			$cacheConfigurationDef = $definitionsHelper->getDefinitionFromConfig($config->secondLevelCache, $this->prefix('cacheConfiguration'));
		} else {
			$regionsDef = $builder->addDefinition($this->prefix('regions'))
				->setFactory(RegionsConfiguration::class)
				->setAutowired(false);

			$cacheFactoryDef = $builder->addDefinition($this->prefix('cacheFactory'))
				->setFactory(DefaultCacheFactory::class)
				->setArguments([
					$regionsDef,
					$this->loadSpecificDriver($definitionsHelper, null, 'secondLevelCache'),
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

}
