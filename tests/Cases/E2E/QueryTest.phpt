<?php declare(strict_types = 1);

namespace Tests\Cases\E2E;

use Contributte\Tester\Environment;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Tester\Assert;
use Tests\Mocks\Entity\DummyEntity;

require_once __DIR__ . '/../../bootstrap.php';

// DBAL
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
			NEON
			));
		})
		->build();

	/** @var Connection $connection */
	$connection = $container->getByType(Connection::class);
	$connection->executeQuery(
		'CREATE TABLE person (id int, lastname varchar(255), firstname varchar(255), address varchar(255), city varchar(255));'
	);

	$qb = $connection->createQueryBuilder();

	$qb->insert('person')
		->values([
			'id' => 1,
			'firstname' => '"John"',
			'lastname' => '"Doe"',
		])
		->executeStatement();

	$qb->insert('person')
		->values([
			'id' => 2,
			'firstname' => '"Sam"',
			'lastname' => '"Smith"',
		])
		->executeStatement();

	$qb = $connection->createQueryBuilder();
	$result = $qb->select('id', 'firstname')
		->from('person')
		->executeQuery()
		->fetchAllAssociative();

	Assert::equal(
		expected: [
			[
				'id' => 1,
				'firstname' => 'John',
			],
			[
				'id' => 2,
				'firstname' => 'Sam',
			],
		],
		actual: $result
	);
});

// DBAL + ORM
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				nettrine.dbal:
					connections:
						default:
							driver: pdo_sqlite
							password: test
							user: test
							path: ":memory:"
							resultCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: dbal, defaultLifetime: 0, directory: %tempDir%/cache/nettrine)
				nettrine.orm:
					managers:
						default:
							connection: default
							defaultCache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: orm, defaultLifetime: 0, directory: %tempDir%/cache/nettrine)
							secondLevelCache:
								enabled: true
								cache: Symfony\Component\Cache\Adapter\FilesystemAdapter(namespace: orm, defaultLifetime: 0, directory: %tempDir%/cache/nettrine/slc/region1)
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
									directories: [%appDir%/Mocks]
									namespace: Tests\Mocks

			NEON
			));
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => Environment::getTestDir(),
					'appDir' => Environment::getTmpDir(),
				],
			]);
		})
		->build();

	/** @var Connection $connection */
	$connection = $container->getByType(Connection::class);
	$connection->executeQuery('CREATE TABLE dummy_entity (id integer primary key autoincrement, username string)');

	/** @var EntityManager $em */
	$em = $container->getByType(EntityManagerInterface::class);

	$em->persist(new DummyEntity('John'));
	$em->persist(new DummyEntity('Doe'));
	$em->flush();

	$result = $em->createQueryBuilder()
		->from(DummyEntity::class, 'd')
		->addSelect('d.id', 'd.username')
		->getQuery()
		->enableResultCache(3600, 'dummy')
		->getArrayResult();

	Assert::equal(
		expected: [
			[
				'id' => 1,
				'username' => 'John',
			],
			[
				'id' => 2,
				'username' => 'Doe',
			],
		],
		actual: $result
	);
});
