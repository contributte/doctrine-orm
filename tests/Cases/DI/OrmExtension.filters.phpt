<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\ORM\EntityManager;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Tester\Assert;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

// Filters
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
							filters:
								autoEnabledFilter:
									class: Tests\Mocks\DummyFilter
									enabled: true
								autoDisabledFilter:
									class: Tests\Mocks\DummyFilter
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

	$filters = $entityManager->getFilters();

	Assert::equal(true, $filters->has('autoEnabledFilter'));
	Assert::equal(true, $filters->isEnabled('autoEnabledFilter'));

	Assert::equal(true, $filters->has('autoDisabledFilter'));
	Assert::equal(false, $filters->isEnabled('autoDisabledFilter'));
});
