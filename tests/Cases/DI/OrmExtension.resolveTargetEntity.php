<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\ORM\EntityManager;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Tester\Assert;
use Tests\Mocks\DummyIdentity;
use Tests\Mocks\Entity\DummyEntity;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

// ResolveTargetEntityListener
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
							resolveTargetEntities:
								Tests\Mocks\DummyIdentity: Tests\Mocks\DummyEntity
							mapping:
								App:
									type: attributes
									directories: [%fixturesDir%/Entity]
									namespace: Tests\Mocks\Entity
				NEON
			));
		})
		->build();

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	$cm = $entityManager->getClassMetadata(DummyIdentity::class);
	Assert::equal($cm->name, DummyEntity::class);
});
