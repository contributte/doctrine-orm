<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Nette\DI\Compiler;
use Nettrine\ORM\EntityManagerDecorator;
use Nettrine\ORM\Exception\Logical\InvalidArgumentException;
use stdClass;
use Tests\Fixtures\DummyConfiguration;
use Tests\Fixtures\DummyEntityManagerDecorator;
use Tests\Toolkit\Nette\ContainerBuilder;
use Tests\Toolkit\TestCase;

final class OrmExtensionTest extends TestCase
{

	public function testOk(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->build();

		$this->assertInstanceOf(EntityManagerDecorator::class, $container->getService('nettrine.orm.entityManagerDecorator'));
	}

	public function testCustomEntityManager(): void
	{
		$container = ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addConfig([
					'nettrine.orm' => [
						'entityManagerDecoratorClass' => DummyEntityManagerDecorator::class,
						'configurationClass' => DummyConfiguration::class,
					],
				]);
			})
			->build();

		$this->assertInstanceOf(DummyEntityManagerDecorator::class, $container->getByType(DummyEntityManagerDecorator::class));
		$this->assertInstanceOf(DummyConfiguration::class, $container->getByType(DummyConfiguration::class));
	}

	public function testConfigurationException(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Configuration class must be subclass of Doctrine\ORM\Configuration, stdClass given.');

		ContainerBuilder::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addConfig([
					'nettrine.orm' => [
						'configurationClass' => stdClass::class,
					],
				]);
			})
			->build();
	}

}
