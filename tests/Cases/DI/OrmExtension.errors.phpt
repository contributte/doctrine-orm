<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\Exception\LogicalException;
use Tester\Assert;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

// Error: Invalid entityManagerDecoratorClass
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
								entityManagerDecoratorClass: stdClass
								mapping:
									App:
										type: attributes
										directories: [app/Database]
										namespace: App\Database
					NEON
				));
			})
			->build();
	}, InvalidConfigurationException::class, '~EntityManager decorator class must be subclass~');
});

// Error: Second level cache enabled without cache
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
								secondLevelCache:
									enabled: true
								mapping:
									App:
										type: attributes
										directories: [app/Database]
										namespace: App\Database
					NEON
				));
			})
			->build();
	}, LogicalException::class, 'Second level cache is enabled but no cache is set.');
});

// Error: Missing connection
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
								connection: nonexistent
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

// Error: Invalid mapping type
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
								mapping:
									App:
										type: yaml
										directories: [app/Database]
										namespace: App\Database
					NEON
				));
			})
			->build();
	}, InvalidConfigurationException::class, '~mapping.*App.*type~');
});

// Error: Invalid service reference for cache
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
										directories: [app/Database]
										namespace: App\Database
					NEON
				));
			})
			->build();
	}, InvalidConfigurationException::class, '~defaultCache~');
});

// Error: Empty proxyDir
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.dbal', new DbalExtension());
				$compiler->addExtension('nettrine.orm', new OrmExtension());
				$compiler->addConfig([
					'parameters' => [
						// No tempDir parameter
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
								proxyDir: null
								mapping:
									App:
										type: attributes
										directories: [app/Database]
										namespace: App\Database
					NEON
				));
			})
			->build();
	}, InvalidConfigurationException::class, '~proxyDir~');
});

// Error: Missing connection in manager
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
								mapping:
									App:
										type: attributes
										directories: [app/Database]
										namespace: App\Database
					NEON
				));
			})
			->build();
	}, InvalidConfigurationException::class, '~connection~');
});
