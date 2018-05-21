<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\VoidCache;
use Doctrine\Common\Cache\XcacheCache;
use Doctrine\ORM\Configuration;
use Nette\DI\CompilerExtension;
use Nette\DI\ServiceDefinition;
use Nette\InvalidStateException;

class OrmCacheExtension extends CompilerExtension
{

	public const DRIVERS = [
		'apc' => ApcCache::class,
		'apcu' => ApcuCache::class,
		'array' => ArrayCache::class,
		'filesystem' => FilesystemCache::class,
		'memcache' => MemcacheCache::class,
		'memcached' => MemcachedCache::class,
		'redis' => RedisCache::class,
		'void' => VoidCache::class,
		'xcache' => XcacheCache::class,
	];

	/** @var mixed[] */
	private $defaults = [
		'defaultDriver' => 'filesystem',
		'queryCache' => null,
		'hydrationCache' => null,
		'metadataCache' => null,
		'resultCache' => null,
		'secondLevelCache' => null,
	];

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
		$this->loadResultCacheConfiguration();
		$this->loadMetadataCacheConfiguration();
	}

	public function loadQueryCacheConfiguration(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();
		$configuration = $builder->getDefinitionByType(Configuration::class);

		if ($config['queryCache'] === null && $config['defaultDriver']) {
			$configuration->addSetup('setQueryCacheImpl', [$this->getDefaultDriverCache('queryCache')]);
		} elseif ($config['queryCache'] !== null) {
			$builder->addDefinition($this->prefix('queryCache'))
				->setFactory($config['queryCache']);
			$configuration->addSetup('setQueryCacheImpl', [$this->prefix('@queryCache')]);
		}
	}

	public function loadResultCacheConfiguration(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();
		$configuration = $builder->getDefinitionByType(Configuration::class);

		if ($config['resultCache'] === null && $config['defaultDriver']) {
			$configuration->addSetup('setResultCacheImpl', [$this->getDefaultDriverCache('resultCache')]);
		} elseif ($config['resultCache'] !== null) {
			$builder->addDefinition($this->prefix('resultCache'))
				->setFactory($config['resultCache']);
			$configuration->addSetup('setResultCacheImpl', [$this->prefix('@hydrationCache')]);
		}
	}

	public function loadHydrationCacheConfiguration(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();
		$configuration = $builder->getDefinitionByType(Configuration::class);

		if ($config['hydrationCache'] === null && $config['defaultDriver']) {
			$configuration->addSetup('setHydrationCacheImpl', [$this->getDefaultDriverCache('hydrationCache')]);
		} elseif ($config['hydrationCache'] !== null) {
			$builder->addDefinition($this->prefix('hydrationCache'))
				->setFactory($config['hydrationCache']);
			$configuration->addSetup('setHydrationCacheImpl', [$this->prefix('@hydrationCache')]);
		}
	}

	public function loadMetadataCacheConfiguration(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();
		$configuration = $builder->getDefinitionByType(Configuration::class);

		if ($config['metadataCache'] === null && $config['defaultDriver']) {
			$configuration->addSetup('setMetadataCacheImpl', [$this->getDefaultDriverCache('metadataCache')]);
		} elseif ($config['metadataCache'] !== null) {
			$builder->addDefinition($this->prefix('metadataCache'))
				->setFactory($config['metadataCache']);
			$configuration->addSetup('setMetadataCacheImpl', [$this->prefix('@metadataCache')]);
		}
	}

	public function loadSecondLevelCacheConfiguration(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();
		$configuration = $builder->getDefinitionByType(Configuration::class);

		if ($config['secondLevelCache'] !== null) {
			$configuration->addSetup('setSecondLevelCacheEnabled', [true]);
			$configuration->addSetup('setSecondLevelCacheConfiguration', [$config['secondLevelCache']]);
		}
	}

	protected function getDefaultDriverCache(string $service): ServiceDefinition
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();

		if (!isset(self::DRIVERS[$config['defaultDriver']])) {
			throw new InvalidStateException(sprintf('Unsupported default driver "%s"', $config['defaultDriver']));
		}

		$driverCache = $builder->addDefinition($this->prefix($service))
			->setFactory(self::DRIVERS[$config['defaultDriver']]);

		if ($config['defaultDriver'] === 'filesystem') {
			$driverCache->setArguments([$builder->parameters['tempDir'] . '/cache/Doctrine.' . ucfirst($service)]);
		}

		return $driverCache;
	}

}
