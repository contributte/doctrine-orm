<?php declare(strict_types = 1);

use Contributte\Psr6\CachePool;
use Contributte\Tester\Toolkit;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Caching\Storages\MemoryStorage;
use Nette\DI\Compiler;
use Nette\DI\Definitions\Statement;
use Nette\DI\ServiceCreationException;
use Nettrine\ORM\DI\OrmCacheExtension;
use Psr\Cache\CacheItemPoolInterface;
use Tester\Assert;
use Tests\Fixtures\Dummy\DummyCacheConfigurationFactory;
use Tests\Toolkit\Container;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

// Autowire cache drivers
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.orm.cache', new OrmCacheExtension());
		})
		->build();

	/** @var EntityManagerDecorator $em */
	$em = $container->getByType(EntityManagerDecorator::class);

	Assert::type(CacheItemPoolInterface::class, $em->getConfiguration()->getHydrationCache());
	Assert::type(CacheItemPoolInterface::class, $em->getConfiguration()->getMetadataCache());
	Assert::type(CacheItemPoolInterface::class, $em->getConfiguration()->getQueryCache());
	Assert::type(CacheItemPoolInterface::class, $em->getConfiguration()->getResultCache());
	Assert::true($em->getConfiguration()->isSecondLevelCacheEnabled());
	Assert::notNull($em->getConfiguration()->getSecondLevelCacheConfiguration());
});

// Provide cache drivers
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.orm.cache', new OrmCacheExtension());
			$compiler->addConfig([
				'nettrine.orm.cache' => [
					'defaultDriver' => MemoryStorage::class,
					'hydrationCache' => new Statement(CachePool::class, [new Statement(Cache::class, [1 => 'cache-namespace'])]), // equivalent to config value Contributte\Psr6\CachePool(Nette\Caching\Cache(_, 'cache-namespace'))
					'metadataCache' => new Statement(Cache::class, ['namespace' => 'cache-namespace']), // equivalent to config value Nette\Caching\Cache(namespace: 'cache-namespace')
					'queryCache' => new Statement(MemoryStorage::class),
					'secondLevelCache' => [DummyCacheConfigurationFactory::class, 'create'],
				],
			]);
		})
		->build();

	/** @var EntityManagerDecorator $em */
	$em = $container->getByType(EntityManagerDecorator::class);

	Assert::type(CachePool::class, $em->getConfiguration()->getHydrationCache());
	Assert::type(CachePool::class, $em->getConfiguration()->getMetadataCache());
	Assert::type(CachePool::class, $em->getConfiguration()->getQueryCache());
	Assert::type(CachePool::class, $em->getConfiguration()->getResultCache());
	Assert::true($em->getConfiguration()->isSecondLevelCacheEnabled());
	Assert::notNull($em->getConfiguration()->getSecondLevelCacheConfiguration());
});

// Turn off second level cache
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.orm.cache', new OrmCacheExtension());
			$compiler->addConfig([
					'nettrine.orm.cache' => [
						'secondLevelCache' => false,
					],
			]);
		})
		->build();

	/** @var EntityManagerDecorator $em */
	$em = $container->getByType(EntityManagerDecorator::class);

	Assert::false($em->getConfiguration()->isSecondLevelCacheEnabled());
	Assert::null($em->getConfiguration()->getSecondLevelCacheConfiguration());
	Assert::type(CachePool::class, $em->getConfiguration()->getHydrationCache());
	Assert::type(CachePool::class, $em->getConfiguration()->getMetadataCache());
	Assert::type(CachePool::class, $em->getConfiguration()->getQueryCache());
	Assert::type(CachePool::class, $em->getConfiguration()->getResultCache());
});

// Provide cache drivers as service links
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->getContainerBuilder()->addDefinition('svcCachePool')
				->setFactory(new Statement(CachePool::class, [new Statement(Cache::class, ['namespace' => 'cache-namespace'])]))
				->setAutowired(false);
			$compiler->getContainerBuilder()->addDefinition('svcCache')
				->setFactory(new Statement(Cache::class, ['namespace' => 'cache-namespace']))
				->setAutowired(false);
			$compiler->getContainerBuilder()->addDefinition('svcStorage')
				->setFactory(MemoryStorage::class)
				->setAutowired(false);
			$compiler->addExtension('nettrine.orm.cache', new OrmCacheExtension());
			$compiler->addConfig([
				'nettrine.orm.cache' => [
					'hydrationCache' => '@svcCachePool',
					'metadataCache' => '@svcCache',
					'queryCache' => '@svcStorage',
				]
			]);
		})
		->build();

	/** @var EntityManagerDecorator $em */
	$em = $container->getByType(EntityManagerDecorator::class);

	Assert::type(CachePool::class, $em->getConfiguration()->getHydrationCache());
	Assert::type(CachePool::class, $em->getConfiguration()->getMetadataCache());
	Assert::type(CachePool::class, $em->getConfiguration()->getQueryCache());
	Assert::type(CachePool::class, $em->getConfiguration()->getResultCache());
});

// Provide cache drivers as service class links
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->getContainerBuilder()->addDefinition(null)
				->setFactory(new Statement(CachePool::class, [new Statement(Cache::class, ['namespace' => 'cache-namespace'])]));
			$compiler->getContainerBuilder()->addDefinition(null)
				->setFactory(new Statement(Cache::class, ['namespace' => 'cache-namespace']));
			$compiler->addExtension('nettrine.orm.cache', new OrmCacheExtension());
			$compiler->addConfig([
				'nettrine.orm.cache' => [
					'hydrationCache' => '@' . CachePool::class,
					'metadataCache' => '@' . Cache::class,
					'queryCache' => '@' . Storage::class,
				]
			]);
		})
		->build();

	/** @var EntityManagerDecorator $em */
	$em = $container->getByType(EntityManagerDecorator::class);

	Assert::type(CachePool::class, $em->getConfiguration()->getHydrationCache());
	Assert::type(CachePool::class, $em->getConfiguration()->getMetadataCache());
	Assert::type(CachePool::class, $em->getConfiguration()->getQueryCache());
	Assert::type(CachePool::class, $em->getConfiguration()->getResultCache());
});

// Provide non-existent service (for tests coverage)
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		$container = Container::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.cache', new OrmCacheExtension());
				$compiler->addConfig([
					'nettrine.orm.cache' => [
						'hydrationCache' => '@nonExistentService',
					]
				]);
			})
			->build();
	}, ServiceCreationException::class);
});

// Provide some nonsense string (for tests coverage)
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		$container = Container::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.cache', new OrmCacheExtension());
				$compiler->addConfig([
					'nettrine.orm.cache' => [
						'hydrationCache' => 'nonsenseString',
					]
				]);
			})
			->build();
	}, ServiceCreationException::class);
});
