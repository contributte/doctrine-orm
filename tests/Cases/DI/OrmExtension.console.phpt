<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand;
use Doctrine\ORM\Tools\Console\Command\InfoCommand;
use Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand;
use Doctrine\ORM\Tools\Console\Command\RunDqlCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Nette\DI\Compiler;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Tester\Assert;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

// All console commands are registered
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

	// Schema tool commands
	Assert::type(CreateCommand::class, $container->getService('nettrine.orm.schemaToolCreateCommand'));
	Assert::type(UpdateCommand::class, $container->getService('nettrine.orm.schemaToolUpdateCommand'));
	Assert::type(DropCommand::class, $container->getService('nettrine.orm.schemaToolDropCommand'));

	// Proxy command
	Assert::type(GenerateProxiesCommand::class, $container->getService('nettrine.orm.generateProxiesCommand'));

	// Info commands
	Assert::type(InfoCommand::class, $container->getService('nettrine.orm.infoCommand'));
	Assert::type(MappingDescribeCommand::class, $container->getService('nettrine.orm.mappingDescribeCommand'));

	// DQL command
	Assert::type(RunDqlCommand::class, $container->getService('nettrine.orm.runDqlCommand'));

	// Validation command
	Assert::type(ValidateSchemaCommand::class, $container->getService('nettrine.orm.validateSchemaCommand'));

	// Cache command
	Assert::type(MetadataCommand::class, $container->getService('nettrine.orm.clearMetadataCacheCommand'));
});

// Console commands have correct tags
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

	$expectedCommands = [
		'orm:schema-tool:create',
		'orm:schema-tool:update',
		'orm:schema-tool:drop',
		'orm:generate-proxies',
		'orm:info',
		'orm:mapping:describe',
		'orm:run-dql',
		'orm:validate-schema',
		'orm:clear-cache:metadata',
	];

	$taggedServices = $container->findByTag('console.command');

	foreach ($expectedCommands as $commandName) {
		Assert::true(in_array($commandName, $taggedServices, true), "Command '$commandName' should be tagged");
	}
});

// Console commands count
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

	$ormCommands = array_filter(
		$container->findByTag('console.command'),
		fn ($tag) => str_starts_with((string) $tag, 'orm:')
	);

	Assert::count(9, $ormCommands);
});
