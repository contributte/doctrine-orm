<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\ORM\Configuration;
use Nette\DI\CompilerExtension;
use Nette\DI\ServiceDefinition;
use Nette\InvalidStateException;

class OrmCacheExtension extends CompilerExtension
{

	/** @var mixed[] */
	private $defaults = [
		'driver' => FilesystemCache::class,
		'queryCache' => NULL,
		'hydrationCache' => NULL,
		'metadataCache' => NULL,
		'secondLevelCache' => NULL,
	];

	/**
	 * @return void
	 */
	public function loadConfiguration(): void
	{
		if (!$this->compiler->getExtensions(OrmExtension::class)) {
			throw new InvalidStateException(
				sprintf('You should register %s before %s.', OrmExtension::class, get_class($this))
			);
		}

		$this->validateConfig($this->defaults);
		$this->loadQueryCacheConfiguration();
		$this->loadHydrationCacheConfiguration();
		$this->loadMetadataCacheConfiguration();
	}

	/**
	 * @return void
	 */
	public function loadQueryCacheConfiguration(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();
		$configuration = $builder->getDefinitionByType(Configuration::class);

		if ($config['queryCache'] === NULL && $config['driver']) {
			$configuration->addSetup('setQueryCacheImpl', [$this->getDriverCache('queryCache')]);
		} elseif ($config['queryCache'] !== NULL) {
			$builder->addDefinition($this->prefix('queryCache'))
				->setFactory($config['queryCache']);
			$configuration->addSetup('setQueryCacheImpl', [$this->prefix('@queryCache')]);
		}
	}

	/**
	 * @return void
	 */
	public function loadHydrationCacheConfiguration(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();
		$configuration = $builder->getDefinitionByType(Configuration::class);

		if ($config['hydrationCache'] === NULL && $config['driver']) {
			$configuration->addSetup('setHydrationCacheImpl', [$this->getDriverCache('hydrationCache')]);
		} elseif ($config['hydrationCache'] !== NULL) {
			$builder->addDefinition($this->prefix('hydrationCache'))
				->setFactory($config['hydrationCache']);
			$configuration->addSetup('setHydrationCacheImpl', [$this->prefix('@hydrationCache')]);
		}
	}

	/**
	 * @return void
	 */
	public function loadMetadataCacheConfiguration(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();
		$configuration = $builder->getDefinitionByType(Configuration::class);

		if ($config['metadataCache'] === NULL && $config['driver']) {
			$configuration->addSetup('setMetadataCacheImpl', [$this->getDriverCache('metadataCache')]);
		} elseif ($config['metadataCache'] !== NULL) {
			$builder->addDefinition($this->prefix('metadataCache'))
				->setFactory($config['metadataCache']);
			$configuration->addSetup('setMetadataCacheImpl', [$this->prefix('@metadataCache')]);
		}
	}

	/**
	 * @return void
	 */
	public function loadSecondLevelCacheConfiguration(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();
		$configuration = $builder->getDefinitionByType(Configuration::class);

		if ($config['secondLevelCache'] !== NULL) {
			$configuration->addSetup('setSecondLevelCacheEnabled', [TRUE]);
			$configuration->addSetup('setSecondLevelCacheConfiguration', [$config['secondLevelCache']]);
		}
	}

	/**
	 * @param string $service
	 * @return ServiceDefinition
	 */
	protected function getDriverCache(string $service): ServiceDefinition
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();

		$driverCache = $builder->addDefinition($this->prefix($service))
			->setFactory($config['driver']);

		if (is_subclass_of($config['driver'], FilesystemCache::class)) {
			$driverCache->setArguments([$builder->parameters['tempDir'] . '/cache/Doctrine.' . ucfirst($service)]);
		}
		return $driverCache;
	}

}
