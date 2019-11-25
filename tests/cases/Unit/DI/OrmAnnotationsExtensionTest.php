<?php declare(strict_types = 1);

namespace Tests\Cases\Unit\DI;

use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nette\DI\ServiceCreationException;
use Nettrine\Annotations\DI\AnnotationsExtension;
use Nettrine\Cache\DI\CacheExtension;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmAnnotationsExtension;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\Mapping\AnnotationDriver;
use Tests\Toolkit\TestCase;

final class OrmAnnotationsExtensionTest extends TestCase
{

	public function testOk(): void
	{
		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('annotations', new AnnotationsExtension());
			$compiler->addExtension('cache', new CacheExtension());
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

		$this->assertInstanceOf(AnnotationDriver::class, $container->getService('orm.annotations.annotationDriver'));
	}

	public function testNoReader(): void
	{
		$this->expectException(ServiceCreationException::class);
		$this->expectExceptionMessage('Service \'orm.annotations.annotationDriver\' (type of Doctrine\Common\Persistence\Mapping\Driver\MappingDriver): Service of type Doctrine\Common\Annotations\Reader needed by $reader in Nettrine\ORM\Mapping\AnnotationDriver::__construct() not found. Did you register it in configuration file?');

		$loader = new ContainerLoader(TEMP_PATH, true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addExtension('cache', new CacheExtension());
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
	}

}
