<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;

final class DummyCacheConfigurationFactory
{

	public static function create(): CacheConfiguration
	{
		$regionsConfiguration = new RegionsConfiguration();
		$cache = new ArrayCache();
		$cacheFactory = new DefaultCacheFactory($regionsConfiguration, $cache);

		$cacheConfiguration = new CacheConfiguration();
		$cacheConfiguration->setCacheFactory($cacheFactory);

		return $cacheConfiguration;
	}

}
