<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\ProxyFactory;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Tester\Assert;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

// Default
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

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	Assert::equal(ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS, $entityManager->getConfiguration()->getAutoGenerateProxyClasses());
});

// Override
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
							autoGenerateProxyClasses: 3
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	Assert::equal(ProxyFactory::AUTOGENERATE_EVAL, $entityManager->getConfiguration()->getAutoGenerateProxyClasses());
});

// Statement
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
							autoGenerateProxyClasses: ::constant(Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_NEVER)
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	Assert::equal(ProxyFactory::AUTOGENERATE_NEVER, $entityManager->getConfiguration()->getAutoGenerateProxyClasses());
});

// Native Objects
Toolkit::test(function (): void {
	// Skip on PHP < 8.4 OR doctrine/orm < v3.4.0
	if (PHP_VERSION_ID < 80400 || !method_exists(Configuration::class, 'enableNativeLazyObjects')) {
		Assert::true(true);
		return;
	}

	$builder = ContainerBuilder::of()
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
							lazyNativeObjects: true
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	Assert::true($entityManager->getConfiguration()->isNativeLazyObjectsEnabled());
});
