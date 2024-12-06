<?php declare(strict_types = 1);

namespace Tests\Mocks;

use Contributte\Psr6\CachePool;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Nette\Caching\Cache;
use Nette\Caching\Storage;

final class DummyCacheConfigurationFactory
{

	public static function create(Storage $cacheStorage): CacheConfiguration
	{
		$regionsConfiguration = new RegionsConfiguration();
		$cache = new CachePool(new Cache($cacheStorage, self::class));
		$cacheFactory = new DefaultCacheFactory($regionsConfiguration, $cache);

		$cacheConfiguration = new CacheConfiguration();
		$cacheConfiguration->setCacheFactory($cacheFactory);

		return $cacheConfiguration;
	}

}
