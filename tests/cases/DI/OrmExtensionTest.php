<?php declare(strict_types = 1);

namespace Tests\Nettrine\ORM\Cases\DI;

use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmAnnotationsExtension;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\EntityManagerDecorator;
use Tests\Nettrine\ORM\Cases\TestCase;
use Tests\Nettrine\ORM\Fixtures\DummyConfiguration;
use Tests\Nettrine\ORM\Fixtures\DummyEntityManagerDecorator;

final class OrmExtensionTest extends TestCase
{

	public function testRegisterAnnotations(): void
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
		self::assertInstanceOf(EntityManagerDecorator::class, $container->getByType(EntityManagerDecorator::class));
	}

	public function testOwnEntityManager(): void
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
			$compiler->addConfig([
				'orm' => [
					'entityManagerDecoratorClass' => DummyEntityManagerDecorator::class,
					'configurationClass' => DummyConfiguration::class,
				],
			]);
		}, self::class . __METHOD__);

		/** @var Container $container */
		$container = new $class();
		self::assertInstanceOf(DummyEntityManagerDecorator::class, $container->getByType(DummyEntityManagerDecorator::class));
		self::assertInstanceOf(DummyConfiguration::class, $container->getByType(DummyConfiguration::class));
	}

}
