<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\Helpers\MappingHelper;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\Exception\LogicalException;
use Tester\Assert;
use Tests\Mocks\DummyExtension;
use Tests\Mocks\Entity\DummyEntity;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../../bootstrap.php';

// Validate path for attribute
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		MappingHelper::of(new DummyExtension())->addAttribute('default', 'fake', 'invalid');
	}, LogicalException::class, 'Given mapping path "invalid" does not exist');
});

// Attributes
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
			$compiler->addExtension('custom', new class extends CompilerExtension {

				public function beforeCompile(): void
				{
					MappingHelper::of($this)
						->addAttribute('default', 'App2\Database', Tests::FIXTURES_PATH . '/../Mocks')
						->addXml('default', 'App3\Database', Tests::FIXTURES_PATH . '/../Toolkit');
				}

			});
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
									namespace: Tests\Mocks
				NEON
			));
		})
		->build();

	/** @var MappingDriverChain $driver */
	$driver = $container->getService('nettrine.orm.managers.default.mappingDriver');
	Assert::count(3, $driver->getDrivers());
	Assert::equal([DummyEntity::class], $driver->getAllClassNames());
});
