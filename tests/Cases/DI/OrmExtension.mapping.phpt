<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Tester\Assert;
use Tests\Mocks\Entity\DummyEntity;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

// Driver: attributes
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
									directories: [%fixturesDir%/Entity, %fixturesDir%/../Toolkit]
									namespace: Tests\Fixtures\Dummy
				NEON
			));
		})
		->build();

	/** @var MappingDriverChain $driver */
	$driver = $container->getService('nettrine.orm.managers.default.mappingDriver');
	Assert::count(1, $driver->getDrivers());

	/** @var AttributeDriver $attributeDriver */
	$attributeDriver = current($driver->getDrivers());

	Assert::type(AttributeDriver::class, $attributeDriver);
	Assert::equal([DummyEntity::class], $attributeDriver->getAllClassNames());
	Assert::count(2, $attributeDriver->getPaths());
});

// Driver: xml
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
									type: xml
									directories: [%fixturesDir%/Entity, %fixturesDir%/../Toolkit]
									namespace: Tests\Mocks\Dummy
				NEON
			));
		})
		->build();

	/** @var MappingDriverChain $driver */
	$driver = $container->getService('nettrine.orm.managers.default.mappingDriver');
	Assert::count(1, $driver->getDrivers());

	/** @var SimplifiedXmlDriver $xmlDriver */
	$xmlDriver = current($driver->getDrivers());

	Assert::type(SimplifiedXmlDriver::class, $xmlDriver);
	Assert::equal([], $xmlDriver->getAllClassNames());
	Assert::count(2, $xmlDriver->getLocator()->getPaths());
});

// Driver: all
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
									directories: [%fixturesDir%/Entity, %fixturesDir%/../Toolkit]
									namespace: Tests\Mocks\Entity
								AppModule:
									type: xml
									directories: [%fixturesDir%/Entity, %fixturesDir%/../Toolkit]
									namespace: App\Module
				NEON
			));
		})
		->build();

	/** @var MappingDriverChain $driver */
	$driver = $container->getService('nettrine.orm.managers.default.mappingDriver');
	Assert::count(2, $driver->getDrivers());

	Assert::equal([DummyEntity::class], $driver->getAllClassNames());
});

// Empty mapping (allowed for cases where mapping is defined in user's DI extension)
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
							mapping: []
				NEON
			));
		})
		->build();

	/** @var MappingDriverChain $driver */
	$driver = $container->getService('nettrine.orm.managers.default.mappingDriver');
	Assert::count(0, $driver->getDrivers());
});

// No mapping key at all (uses default empty array)
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
				NEON
			));
		})
		->build();

	/** @var MappingDriverChain $driver */
	$driver = $container->getService('nettrine.orm.managers.default.mappingDriver');
	Assert::count(0, $driver->getDrivers());
});
