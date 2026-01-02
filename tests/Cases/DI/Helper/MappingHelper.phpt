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

// Validate path for XML
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		MappingHelper::of(new DummyExtension())->addXml('default', 'fake', 'invalid');
	}, LogicalException::class, 'Given mapping path "invalid" does not exist');
});

// No mapping driver found
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('custom', new class extends CompilerExtension {

					public function beforeCompile(): void
					{
						// Try to add mapping without ORM extension
						MappingHelper::of($this)->addAttribute('default', 'App\Database', Tests::FIXTURES_PATH);
					}

				});
			})
			->build();
	}, LogicalException::class, 'No mapping driver found');
});

// No mapping driver found for connection
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.dbal', new DbalExtension());
				$compiler->addExtension('nettrine.orm', new OrmExtension());
				$compiler->addExtension('custom', new class extends CompilerExtension {

					public function beforeCompile(): void
					{
						// Try to add mapping to non-existent connection
						MappingHelper::of($this)->addAttribute('nonexistent', 'App\Database', Tests::FIXTURES_PATH);
					}

				});
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
	}, LogicalException::class, 'No mapping driver found for connection "nonexistent"');
});

// Fluent interface - chaining
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
			$compiler->addExtension('custom', new class extends CompilerExtension {

				public function beforeCompile(): void
				{
					$helper = MappingHelper::of($this);

					// Test that chaining returns the same instance
					$result = $helper
						->addAttribute('default', 'App2\Database', Tests::FIXTURES_PATH . '/../Mocks')
						->addAttribute('default', 'App3\Database', Tests::FIXTURES_PATH . '/../Toolkit');

					Assert::same($helper, $result);
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

	Assert::true(true);
});
