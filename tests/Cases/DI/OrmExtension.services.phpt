<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\AnsiQuoteStrategy;
use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\Mapping\ContainerEntityListenerResolver;
use Tester\Assert;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

// Default
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

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	Assert::type(DefaultRepositoryFactory::class, $entityManager->getConfiguration()->getRepositoryFactory());
	Assert::type(DefaultQuoteStrategy::class, $entityManager->getConfiguration()->getQuoteStrategy());
	Assert::type(UnderscoreNamingStrategy::class, $entityManager->getConfiguration()->getNamingStrategy());
	Assert::type(ContainerEntityListenerResolver::class, $entityManager->getConfiguration()->getEntityListenerResolver());
});

// Configuration (reference)
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
							entityListenerResolver: @entityListenerResolver
							namingStrategy: @namingStrategy
							quoteStrategy: @quoteStrategy
							repositoryFactory: @repositoryFactory
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
				services:
					entityListenerResolver: Doctrine\ORM\Mapping\DefaultEntityListenerResolver()
					namingStrategy: Doctrine\ORM\Mapping\UnderscoreNamingStrategy()
					quoteStrategy: Doctrine\ORM\Mapping\AnsiQuoteStrategy()
					repositoryFactory: Doctrine\ORM\Repository\DefaultRepositoryFactory()
				NEON
			));
		})
		->build();

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	Assert::type(DefaultRepositoryFactory::class, $entityManager->getConfiguration()->getRepositoryFactory());
	Assert::type(AnsiQuoteStrategy::class, $entityManager->getConfiguration()->getQuoteStrategy());
	Assert::type(UnderscoreNamingStrategy::class, $entityManager->getConfiguration()->getNamingStrategy());
	Assert::type(DefaultEntityListenerResolver::class, $entityManager->getConfiguration()->getEntityListenerResolver());
});

// Configuration (statement)
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
							entityListenerResolver: Doctrine\ORM\Mapping\DefaultEntityListenerResolver()
							namingStrategy: Doctrine\ORM\Mapping\UnderscoreNamingStrategy()
							quoteStrategy: Doctrine\ORM\Mapping\AnsiQuoteStrategy()
							repositoryFactory: Doctrine\ORM\Repository\DefaultRepositoryFactory()
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	Assert::type(DefaultRepositoryFactory::class, $entityManager->getConfiguration()->getRepositoryFactory());
	Assert::type(AnsiQuoteStrategy::class, $entityManager->getConfiguration()->getQuoteStrategy());
	Assert::type(UnderscoreNamingStrategy::class, $entityManager->getConfiguration()->getNamingStrategy());
	Assert::type(DefaultEntityListenerResolver::class, $entityManager->getConfiguration()->getEntityListenerResolver());
});

// Configuration (string)
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
							entityListenerResolver: Doctrine\ORM\Mapping\DefaultEntityListenerResolver
							namingStrategy: Doctrine\ORM\Mapping\UnderscoreNamingStrategy
							quoteStrategy: Doctrine\ORM\Mapping\AnsiQuoteStrategy
							repositoryFactory: Doctrine\ORM\Repository\DefaultRepositoryFactory
							mapping:
								App:
									type: attributes
									directories: [app/Database]
									namespace: App\Database
				NEON
			));
		})
		->build();

	/** @var EntityManager $entityManager */
	$entityManager = $container->getService('nettrine.orm.managers.default.entityManager');

	Assert::type(DefaultRepositoryFactory::class, $entityManager->getConfiguration()->getRepositoryFactory());
	Assert::type(AnsiQuoteStrategy::class, $entityManager->getConfiguration()->getQuoteStrategy());
	Assert::type(UnderscoreNamingStrategy::class, $entityManager->getConfiguration()->getNamingStrategy());
	Assert::type(DefaultEntityListenerResolver::class, $entityManager->getConfiguration()->getEntityListenerResolver());
});
