<?php declare(strict_types = 1);

namespace Tests\Cases\Unit\DI;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\PhpFileCache;
use Doctrine\Common\Cache\VoidCache;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nette\DI\ServiceCreationException;
use Nettrine\Annotations\DI\AnnotationsExtension;
use Nettrine\Cache\DI\CacheExtension;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmAnnotationsExtension;
use Nettrine\ORM\DI\OrmCacheExtension;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\EntityManagerDecorator;
use Tests\Toolkit\TestCase;

final class OrmCacheExtensionTest extends TestCase
{

	public function testAutowiredCacheDrivers(): void
	{
		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('annotations', new AnnotationsExtension());
			$compiler->addExtension('cache', new CacheExtension());
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addExtension('orm', new OrmExtension());
			$compiler->addExtension('orm.annotations', new OrmAnnotationsExtension());
			$compiler->addExtension('orm.cache', new OrmCacheExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => TEMP_PATH,
					'appDir' => __DIR__,
				],
			]);
		}, self::class . __METHOD__);

		/** @var Container $container */
		$container = new $class();

		/** @var EntityManagerDecorator $em */
		$em = $container->getByType(EntityManagerDecorator::class);

		$this->assertInstanceOf(PhpFileCache::class, $em->getConfiguration()->getHydrationCacheImpl());
		$this->assertInstanceOf(PhpFileCache::class, $em->getConfiguration()->getMetadataCacheImpl());
		$this->assertInstanceOf(PhpFileCache::class, $em->getConfiguration()->getQueryCacheImpl());
		$this->assertInstanceOf(PhpFileCache::class, $em->getConfiguration()->getResultCacheImpl());
	}

	public function testProvidedCacheDrivers(): void
	{
		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('annotations', new AnnotationsExtension());
			$compiler->addExtension('cache', new CacheExtension());
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addExtension('orm', new OrmExtension());
			$compiler->addExtension('orm.annotations', new OrmAnnotationsExtension());
			$compiler->addExtension('orm.cache', new OrmCacheExtension());
			$compiler->addConfig([
				'orm.cache' => [
					'defaultDriver' => ArrayCache::class,
					'hydrationCache' => VoidCache::class,
					'metadataCache' => null,
					'queryCache' => ApcuCache::class,
					//'resultCache' => null,
				],
				'parameters' => [
					'tempDir' => TEMP_PATH,
					'appDir' => __DIR__,
				],
			]);
		}, self::class . __METHOD__);

		/** @var Container $container */
		$container = new $class();

		/** @var EntityManagerDecorator $em */
		$em = $container->getByType(EntityManagerDecorator::class);

		$this->assertInstanceOf(VoidCache::class, $em->getConfiguration()->getHydrationCacheImpl());
		$this->assertInstanceOf(ArrayCache::class, $em->getConfiguration()->getMetadataCacheImpl());
		$this->assertInstanceOf(ApcuCache::class, $em->getConfiguration()->getQueryCacheImpl());
		$this->assertInstanceOf(ArrayCache::class, $em->getConfiguration()->getResultCacheImpl());
	}

	public function testNoCacheDriver(): void
	{
		$this->expectException(ServiceCreationException::class);
		$this->expectExceptionMessage("Service 'dbal.configuration' (type of Doctrine\DBAL\Configuration): Service of type 'Doctrine\Common\Cache\Cache' not found.");

		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('annotations', new AnnotationsExtension());
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addExtension('orm', new OrmExtension());
			$compiler->addExtension('orm.annotations', new OrmAnnotationsExtension());
			$compiler->addExtension('orm.cache', new OrmCacheExtension());
			$compiler->addConfig([
				'annotations' => [
					'cache' => VoidCache::class,
				],
				'parameters' => [
					'tempDir' => TEMP_PATH,
					'appDir' => __DIR__,
				],
			]);
		}, self::class . __METHOD__);

		new $class();
	}

}
