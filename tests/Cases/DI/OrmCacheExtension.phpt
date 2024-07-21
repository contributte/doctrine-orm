<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\PhpFileCache;
use Doctrine\Common\Cache\VoidCache;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Nette\DI\Compiler;
use Nettrine\ORM\DI\OrmCacheExtension;
use Tester\Assert;
use Tests\Fixtures\Dummy\DummyCacheConfigurationFactory;
use Tests\Toolkit\Container;

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

	Assert::type(PhpFileCache::class, $em->getConfiguration()->getHydrationCacheImpl());
	Assert::type(PhpFileCache::class, $em->getConfiguration()->getMetadataCacheImpl());
	Assert::type(PhpFileCache::class, $em->getConfiguration()->getQueryCacheImpl());
	Assert::type(PhpFileCache::class, $em->getConfiguration()->getResultCacheImpl());
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
					'defaultDriver' => ArrayCache::class,
					'hydrationCache' => VoidCache::class,
					'metadataCache' => null,
					'queryCache' => ApcuCache::class,
					'secondLevelCache' => [DummyCacheConfigurationFactory::class, 'create'],
				],
			]);
		})
		->build();

	/** @var EntityManagerDecorator $em */
	$em = $container->getByType(EntityManagerDecorator::class);

	Assert::type(VoidCache::class, $em->getConfiguration()->getHydrationCacheImpl());
	Assert::type(ArrayCache::class, $em->getConfiguration()->getMetadataCacheImpl());
	Assert::type(ApcuCache::class, $em->getConfiguration()->getQueryCacheImpl());
	Assert::type(ArrayCache::class, $em->getConfiguration()->getResultCacheImpl());
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
	Assert::type(PhpFileCache::class, $em->getConfiguration()->getHydrationCacheImpl());
	Assert::type(PhpFileCache::class, $em->getConfiguration()->getMetadataCacheImpl());
	Assert::type(PhpFileCache::class, $em->getConfiguration()->getQueryCacheImpl());
	Assert::type(PhpFileCache::class, $em->getConfiguration()->getResultCacheImpl());
});
