<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\ManagerRegistry as NettrineManagerRegistry;
use Tester\Assert;
use Tests\Mocks\Entity\DummyEntity;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../bootstrap.php';

// Multiple managers
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
	Assert::type(ManagerRegistry::class, $registry);

	Assert::count(2, $registry->getManagers());
});

// Reset manager
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
							entityManagerDecoratorClass: Tests\Mocks\DummyEntityManagerDecorator
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

	foreach (['default', 'second'] as $managerName) {
		/** @var EntityManagerInterface $em1 */
		$em1 = $registry->getManager($managerName);

		Assert::true($em1->isOpen());
		$em1->close();
		Assert::false($em1->isOpen());

		// Reset manager
		$registry->resetManager($managerName);

		/** @var EntityManagerInterface $em2 */
		$em2 = $registry->getManager();
		Assert::notSame($em1, $em2);

		Assert::true($em1->isOpen());
		Assert::true($em2->isOpen());
	}
});

// Get default manager name
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

// Get default connection name
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

	Assert::equal('default', $registry->getDefaultConnectionName());
});

// Get connection
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

	Assert::type(Connection::class, $registry->getConnection());
	Assert::type(Connection::class, $registry->getConnection('default'));
});

// Get connections
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
				NEON
			));
		})
		->build();

	/** @var ManagerRegistry $registry */
	$registry = $container->getByType(ManagerRegistry::class);

	Assert::count(2, $registry->getConnections());
	Assert::equal(['default', 'second'], array_keys($registry->getConnectionNames()));
});

// Get manager names
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

	Assert::equal(['default', 'second'], array_keys($registry->getManagerNames()));
});

// Reopen static method on EntityManager
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => Tests::TEMP_PATH,
					'fixturesDir' => Tests::FIXTURES_PATH,
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
									directories: [%fixturesDir%/Entity]
									namespace: Tests\Mocks\Entity
						second:
							connection: second
							entityManagerDecoratorClass: Tests\Mocks\DummyEntityManagerDecorator
							mapping:
								App:
									type: attributes
									directories: [%fixturesDir%/Entity]
									namespace: Tests\Mocks\Entity
				NEON
			));
		})
		->build();

	/** @var ManagerRegistry $registry */
	$registry = $container->getByType(ManagerRegistry::class);

	foreach (['default', 'second'] as $managerName) {
		/** @var EntityManagerInterface $em */
		$em = $registry->getManager($managerName);
		Assert::true($em->isOpen());

		/** @var Connection $connection */
		$connection = $registry->getConnection($managerName);

		$connection->executeQuery('CREATE TABLE dummy_entity (id integer primary key autoincrement, username string unique not null)');

		$persister1 = $em->getUnitOfWork()->getEntityPersister(DummyEntity::class);

		$em->persist(new DummyEntity('test'));
		$em->flush();

		Assert::count(0, $persister1->getInserts(), 'Persister should have no inserts, have ' . count($persister1->getInserts()));

		// try to create a new entity with the same username - not unique
		$em->persist(new DummyEntity('test'));
		try {
			$em->flush();
		} catch (UniqueConstraintViolationException) {
			Assert::false($em->isOpen());
		}

		// after failing queue there is cached last insert
		Assert::count(1, $persister1->getInserts(), 'Persister should have 1 insert, have ' . count($persister1->getInserts()));

		NettrineManagerRegistry::reopen($em);

		Assert::true($em->isOpen());

		$persister2 = $em->getUnitOfWork()->getEntityPersister(DummyEntity::class);

		// check if the persister is different after reopening
		Assert::notSame($persister1, $persister2);

		$dummy2 = new DummyEntity('test2');
		$em->persist($dummy2);
		$em->flush();

		Assert::count(2, $em->getRepository(DummyEntity::class)->findAll());
	}
});

// Get repository for manager
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => Tests::TEMP_PATH,
					'fixturesDir' => Tests::FIXTURES_PATH,
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
									directories: [%fixturesDir%/Entity]
									namespace: Tests\Mocks\Entity
				NEON
			));
		})
		->build();

	/** @var ManagerRegistry $registry */
	$registry = $container->getByType(ManagerRegistry::class);

	$repository = $registry->getRepository(DummyEntity::class);

	Assert::type('Doctrine\ORM\EntityRepository', $repository);
});

// Get manager for class
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => Tests::TEMP_PATH,
					'fixturesDir' => Tests::FIXTURES_PATH,
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
									directories: [%fixturesDir%/Entity]
									namespace: Tests\Mocks\Entity
				NEON
			));
		})
		->build();

	/** @var ManagerRegistry $registry */
	$registry = $container->getByType(ManagerRegistry::class);

	$manager = $registry->getManagerForClass(DummyEntity::class);

	Assert::type(EntityManagerInterface::class, $manager);
});
