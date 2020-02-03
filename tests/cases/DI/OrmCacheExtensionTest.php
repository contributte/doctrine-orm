<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\PhpFileCache;
use Doctrine\Common\Cache\VoidCache;
use Nette\DI\Compiler;
use Nettrine\ORM\DI\OrmCacheExtension;
use Nettrine\ORM\EntityManagerDecorator;
use Tests\Toolkit\Nette\ContainerBuilder;
use Tests\Toolkit\TestCase;

final class OrmCacheExtensionTest extends TestCase
{

	public function testAutowiredCacheDrivers(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.cache', new OrmCacheExtension());
			})
			->build();

		/** @var EntityManagerDecorator $em */
		$em = $container->getByType(EntityManagerDecorator::class);

		$this->assertInstanceOf(PhpFileCache::class, $em->getConfiguration()->getHydrationCacheImpl());
		$this->assertInstanceOf(PhpFileCache::class, $em->getConfiguration()->getMetadataCacheImpl());
		$this->assertInstanceOf(PhpFileCache::class, $em->getConfiguration()->getQueryCacheImpl());
		$this->assertInstanceOf(PhpFileCache::class, $em->getConfiguration()->getResultCacheImpl());
	}

	public function testProvidedCacheDrivers(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.cache', new OrmCacheExtension());
				$compiler->addConfig([
					'nettrine.orm.cache' => [
						'defaultDriver' => ArrayCache::class,
						'hydrationCache' => VoidCache::class,
						'metadataCache' => null,
						'queryCache' => ApcuCache::class,
						//'resultCache' => null,
					],
				]);
			})
			->build();

		/** @var EntityManagerDecorator $em */
		$em = $container->getByType(EntityManagerDecorator::class);

		$this->assertInstanceOf(VoidCache::class, $em->getConfiguration()->getHydrationCacheImpl());
		$this->assertInstanceOf(ArrayCache::class, $em->getConfiguration()->getMetadataCacheImpl());
		$this->assertInstanceOf(ApcuCache::class, $em->getConfiguration()->getQueryCacheImpl());
		$this->assertInstanceOf(ArrayCache::class, $em->getConfiguration()->getResultCacheImpl());
	}

}
