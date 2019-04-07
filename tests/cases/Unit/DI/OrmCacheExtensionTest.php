<?php declare(strict_types = 1);

namespace Tests\Cases\Unit\DI;

use Doctrine\Common\Cache\FilesystemCache;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmAnnotationsExtension;
use Nettrine\ORM\DI\OrmCacheExtension;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\EntityManagerDecorator;
use Tests\Toolkit\TestCase;

final class OrmCacheExtensionTest extends TestCase
{

	public function testCacheDrivers(): void
	{
		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
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

		$this->assertInstanceOf(FilesystemCache::class, $em->getConfiguration()->getHydrationCacheImpl());
		$this->assertInstanceOf(FilesystemCache::class, $em->getConfiguration()->getMetadataCacheImpl());
		$this->assertInstanceOf(FilesystemCache::class, $em->getConfiguration()->getQueryCacheImpl());
		$this->assertInstanceOf(FilesystemCache::class, $em->getConfiguration()->getResultCacheImpl());
	}

}
