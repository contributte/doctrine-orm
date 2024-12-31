<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\ORM\EntityManager;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Tester\Assert;
use Tests\Mocks\DummyConfiguration;
use Tests\Mocks\DummyEntityManagerDecorator;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

// Ok
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => Tests::TEMP_PATH,
				],
			]);
			$compiler->addConfig(Neonkit::load(
				<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
				nettrine.orm:
					managers:
						default:
							connection: default
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	Assert::type(EntityManager::class, $container->getService('nettrine.orm.managers.default.entityManager'));
	Assert::false($container->hasService('nettrine.orm.managers.default.entityManagerDecorator'));
});

// Custom entity manager
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => Tests::TEMP_PATH,
				],
			]);
			$compiler->addConfig(Neonkit::load(
				<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
				nettrine.orm:
					managers:
						default:
							connection: default
							entityManagerDecoratorClass: Tests\Mocks\DummyEntityManagerDecorator
							configurationClass: Tests\Mocks\DummyConfiguration
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	Assert::type(DummyEntityManagerDecorator::class, $container->getService('nettrine.orm.managers.default.entityManagerDecorator'));
	Assert::type(DummyConfiguration::class, $container->getService('nettrine.orm.managers.default.configuration'));
});
