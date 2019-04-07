<?php declare(strict_types = 1);

namespace Tests\Cases\Unit\DI;

use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nette\InvalidArgumentException;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmAnnotationsExtension;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\EntityManagerDecorator;
use stdClass;
use Tests\Fixtures\DummyConfiguration;
use Tests\Fixtures\DummyEntityManagerDecorator;
use Tests\Toolkit\TestCase;

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
		$this->assertInstanceOf(EntityManagerDecorator::class, $container->getByType(EntityManagerDecorator::class));
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
		$this->assertInstanceOf(DummyEntityManagerDecorator::class, $container->getByType(DummyEntityManagerDecorator::class));
		$this->assertInstanceOf(DummyConfiguration::class, $container->getByType(DummyConfiguration::class));
	}

	public function testConfigurationException(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Configuration class must be subclass of Doctrine\ORM\Configuration, stdClass given.');

		$loader = new ContainerLoader(TEMP_PATH, true);
		$loader->load(function (Compiler $compiler): void {
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
					'configurationClass' => stdClass::class,
				],
			]);
		}, self::class . __METHOD__);
	}

}
