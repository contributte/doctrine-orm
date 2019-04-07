<?php declare(strict_types = 1);

namespace Tests\Cases\Unit\DI;

use Doctrine\Common\Cache\FilesystemCache;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmAnnotationsExtension;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\Exception\Logical\InvalidStateException;
use Tests\Toolkit\TestCase;

final class OrmAnnotationsExtensionTest extends TestCase
{

	public function testDefaultCache(): void
	{
		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addExtension('orm', new OrmExtension());
			$compiler->addExtension('orm.annotations', new OrmAnnotationsExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => TEMP_PATH,
					'appDir' => __DIR__,
				],
			]);
		}, self::class . __METHOD__);

		/** @var Container $container */
		$container = new $class();

		$this->assertInstanceOf(FilesystemCache::class, $container->getService('orm.annotations.annotationsCache'));
	}

	public function testNoCache(): void
	{
		$this->expectException(InvalidStateException::class);
		$this->expectExceptionMessage('Cache or defaultCache must be provided');

		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('dbal', new DbalExtension());
			$compiler->addExtension('orm', new OrmExtension());
			$compiler->addExtension('orm.annotations', new OrmAnnotationsExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => TEMP_PATH,
					'appDir' => __DIR__,
				],
				'orm.annotations' => [
					'cache' => null,
					'defaultCache' => null,
				],
			]);
		}, self::class . __METHOD__);

		/** @var Container $container */
		$container = new $class();
	}

}
