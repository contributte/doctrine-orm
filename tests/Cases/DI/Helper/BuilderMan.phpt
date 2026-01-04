<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\Helpers\BuilderMan;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\DI\Pass\AbstractPass;
use Nettrine\ORM\Exception\LogicalException;
use Tester\Assert;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../../bootstrap.php';

// Get connection by name - exists
Toolkit::test(function (): void {
	ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
			$compiler->addExtension('test', new class extends CompilerExtension {

				public function beforeCompile(): void
				{
					$pass = new class ($this) extends AbstractPass {

					};

					$builderMan = BuilderMan::of($pass);
					$connection = $builderMan->getConnectionByName('default');

					Assert::type(ServiceDefinition::class, $connection);
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
});

// Get connection by name - not found
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.dbal', new DbalExtension());
				$compiler->addExtension('nettrine.orm', new OrmExtension());
				$compiler->addExtension('test', new class extends CompilerExtension {

					public function beforeCompile(): void
					{
						$pass = new class ($this) extends AbstractPass {

						};

						$builderMan = BuilderMan::of($pass);
						$builderMan->getConnectionByName('nonexistent');
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
	}, LogicalException::class, 'Connection "nonexistent" not found');
});

// Get connections map
Toolkit::test(function (): void {
	ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
			$compiler->addExtension('test', new class extends CompilerExtension {

				public function beforeCompile(): void
				{
					$pass = new class ($this) extends AbstractPass {

					};

					$builderMan = BuilderMan::of($pass);
					$map = $builderMan->getConnectionsMap();

					Assert::count(2, $map);
					Assert::true(isset($map['default']));
					Assert::true(isset($map['second']));
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
});

// Get managers map
Toolkit::test(function (): void {
	ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
			$compiler->addExtension('test', new class extends CompilerExtension {

				public function beforeCompile(): void
				{
					$pass = new class ($this) extends AbstractPass {

					};

					$builderMan = BuilderMan::of($pass);
					$map = $builderMan->getManagersMap();

					Assert::count(2, $map);
					Assert::true(isset($map['default']));
					Assert::true(isset($map['second']));
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
});

// Get managers map with decorator
Toolkit::test(function (): void {
	ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
			$compiler->addExtension('test', new class extends CompilerExtension {

				public function beforeCompile(): void
				{
					$pass = new class ($this) extends AbstractPass {

					};

					$builderMan = BuilderMan::of($pass);
					$map = $builderMan->getManagersMap();

					// With decorator, the decorator replaces the manager in the map
					Assert::count(1, $map);
					Assert::true(isset($map['default']));
					Assert::contains('entityManagerDecorator', $map['default']);
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
});

// Get all connections
Toolkit::test(function (): void {
	ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.dbal', new DbalExtension());
			$compiler->addExtension('nettrine.orm', new OrmExtension());
			$compiler->addExtension('test', new class extends CompilerExtension {

				public function beforeCompile(): void
				{
					$pass = new class ($this) extends AbstractPass {

					};

					$builderMan = BuilderMan::of($pass);
					$connections = $builderMan->getConnections();

					Assert::count(2, $connections);
					Assert::type(ServiceDefinition::class, $connections['default']);
					Assert::type(ServiceDefinition::class, $connections['second']);
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
});
