<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\ORM\EntityManager;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Tester\Assert;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

// No cache
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
									dirs: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	Assert::null($entityManager->getConfiguration()->getHydrationCache());
	Assert::null($entityManager->getConfiguration()->getMetadataCache());
	Assert::null($entityManager->getConfiguration()->getQueryCache());
	Assert::null($entityManager->getConfiguration()->getResultCache());
	Assert::false($entityManager->getConfiguration()->isSecondLevelCacheEnabled());
	Assert::null($entityManager->getConfiguration()->getSecondLevelCacheConfiguration());
});

// Cache drivers (statement)
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
							hydrationCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: doctrine-orm-hydration, defaultLifetime: 0, directory: %tempDir%/cache/doctrine-orm-hydration)
							metadataCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: doctrine-orm-metadata, defaultLifetime: 0, directory: %tempDir%/cache/doctrine-orm-metadata)
							queryCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: doctrine-orm-query, defaultLifetime: 0, directory: %tempDir%/cache/doctrine-orm-query)
							resultCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: doctrine-orm-result, defaultLifetime: 0, directory: %tempDir%/cache/doctrine-orm-result)
							mapping:
								App:
									type: attributes
									dirs: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	Assert::type(FilesystemAdapter::class, $entityManager->getConfiguration()->getHydrationCache());
	Assert::type(FilesystemAdapter::class, $entityManager->getConfiguration()->getMetadataCache());
	Assert::type(FilesystemAdapter::class, $entityManager->getConfiguration()->getQueryCache());
	Assert::type(FilesystemAdapter::class, $entityManager->getConfiguration()->getResultCache());
});

// Cache drivers (service)
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
							hydrationCache: @cache
							metadataCache: @cache
							queryCache: @Symfony\Component\Cache\Adapter\FilesystemAdapter
							resultCache: @Symfony\Component\Cache\Adapter\FilesystemAdapter
							mapping:
								App:
									type: attributes
									dirs: [app/Database]
									namespace: App\Database

				services:
					cache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: doctrine, defaultLifetime: 0, directory: %tempDir%/cache/doctrine)
				NEON
			));
		})
		->build();

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	Assert::type(FilesystemAdapter::class, $entityManager->getConfiguration()->getHydrationCache());
	Assert::type(FilesystemAdapter::class, $entityManager->getConfiguration()->getMetadataCache());
	Assert::type(FilesystemAdapter::class, $entityManager->getConfiguration()->getQueryCache());
	Assert::type(FilesystemAdapter::class, $entityManager->getConfiguration()->getResultCache());
});

// Default cache driver
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
							defaultCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: doctrine, defaultLifetime: 0, directory: %tempDir%/cache/doctrine)
							mapping:
								App:
									type: attributes
									dirs: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	Assert::type(FilesystemAdapter::class, $entityManager->getConfiguration()->getHydrationCache());
	Assert::type(FilesystemAdapter::class, $entityManager->getConfiguration()->getMetadataCache());
	Assert::type(FilesystemAdapter::class, $entityManager->getConfiguration()->getQueryCache());
	Assert::type(FilesystemAdapter::class, $entityManager->getConfiguration()->getResultCache());
});

// Second level cache
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
							defaultCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: doctrine, defaultLifetime: 0, directory: %tempDir%/cache/doctrine)
							secondLevelCache:
								enabled: true
								cache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: doctrine, defaultLifetime: 0, directory: %tempDir%/cache/doctrine-region1)
								regions:
									region1:
										lifetime: 3600
										lockLifetime: 60
									region2:
										lifetime: 86000
										lockLifetime: 60
							mapping:
								App:
									type: attributes
									dirs: [app/Database]
									namespace: App\Database

				services:
					cache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: doctrine-common, defaultLifetime: 0, directory: %tempDir%/cache/doctrine-common)
				NEON
			));
		})
		->build();

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	Assert::true($entityManager->getConfiguration()->isSecondLevelCacheEnabled());

	$regionsConfiguration = $entityManager->getConfiguration()->getSecondLevelCacheConfiguration()->getRegionsConfiguration();
	Assert::equal(3600, $regionsConfiguration->getLifetime('region1'));
	Assert::equal(60, $regionsConfiguration->getLockLifetime('region1'));
	Assert::equal(86000, $regionsConfiguration->getLifetime('region2'));
	Assert::equal(60, $regionsConfiguration->getLockLifetime('region2'));
});

// Error: invalid cache driver
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
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
							defaultCache: Invalid
							mapping:
								App:
									type: attributes
									dirs: [app/Database]
									namespace: App\Database
				NEON
				));
			})
			->build();
	}, InvalidConfigurationException::class, "Failed assertion #0 for item 'nettrine.orm › managers › default › defaultCache' with value 'Invalid'.");
});
