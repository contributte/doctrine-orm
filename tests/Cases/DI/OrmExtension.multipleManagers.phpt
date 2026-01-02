<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Tester\Assert;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

// Only default manager is autowired
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
						second:
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
						second:
							connection: second
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	// Autowiring should return the default manager
	$autowired = $container->getByType(EntityManagerInterface::class);
	$defaultManager = $container->getService('nettrine.orm.managers.default.entityManager');
	$secondManager = $container->getService('nettrine.orm.managers.second.entityManager');

	Assert::same($autowired, $defaultManager);
	Assert::notSame($autowired, $secondManager);
});

// Access non-default managers by name via registry
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
						second:
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
						second:
							connection: second
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	/** @var ManagerRegistry $registry */
	$registry = $container->getByType(ManagerRegistry::class);

	$defaultManager = $registry->getManager('default');
	$secondManager = $registry->getManager('second');

	Assert::type(EntityManager::class, $defaultManager);
	Assert::type(EntityManager::class, $secondManager);
	Assert::notSame($defaultManager, $secondManager);
});

// Different connections per manager
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
						second:
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
						second:
							connection: second
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	/** @var EntityManager $defaultManager */
	$defaultManager = $container->getService('nettrine.orm.managers.default.entityManager');
	/** @var EntityManager $secondManager */
	$secondManager = $container->getService('nettrine.orm.managers.second.entityManager');

	Assert::notSame($defaultManager->getConnection(), $secondManager->getConnection());
});

// Different caches per manager
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
						second:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
				nettrine.orm:
					managers:
						default:
							connection: default
							defaultCache: Symfony\Component\Cache\Adapter\ArrayAdapter()
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
						second:
							connection: second
							defaultCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: second, defaultLifetime: 0, directory: %tempDir%/cache/second)
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	/** @var EntityManager $defaultManager */
	$defaultManager = $container->getService('nettrine.orm.managers.default.entityManager');
	/** @var EntityManager $secondManager */
	$secondManager = $container->getService('nettrine.orm.managers.second.entityManager');

	Assert::type(ArrayAdapter::class, $defaultManager->getConfiguration()->getMetadataCache());
	Assert::type(FilesystemAdapter::class, $secondManager->getConfiguration()->getMetadataCache());
});

// Manager count in registry
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
						second:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
						third:
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
						second:
							connection: second
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
						third:
							connection: third
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	/** @var ManagerRegistry $registry */
	$registry = $container->getByType(ManagerRegistry::class);

	Assert::count(3, $registry->getManagers());
	Assert::equal(['default', 'second', 'third'], array_keys($registry->getManagerNames()));
});

// Default manager name
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

	/** @var ManagerRegistry $registry */
	$registry = $container->getByType(ManagerRegistry::class);

	Assert::equal('default', $registry->getDefaultManagerName());
});
