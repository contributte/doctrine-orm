<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Tester\Assert;
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
									dirs: [app/Database]
									namespace: App\Database
						second:
							connection: second
							mapping:
								App:
									type: attributes
									dirs: [app/Database]
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
									dirs: [app/Database]
									namespace: App\Database
						second:
							connection: second
							entityManagerDecoratorClass: Tests\Mocks\DummyEntityManagerDecorator
							mapping:
								App:
									type: attributes
									dirs: [app/Database]
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
