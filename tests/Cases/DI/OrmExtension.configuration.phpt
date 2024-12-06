<?php declare(strict_types = 1);

use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nettrine\DBAL\DI\DbalExtension;
use Nettrine\ORM\DI\OrmExtension;
use Tester\Assert;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

// Error (configuration subclass)
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		ContainerBuilder::of()
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
								configurationClass: stdClass
								mapping:
									App:
										type: attributes
										dirs: [%fixturesDir%/Entity]
										namespace: Tests\Mocks\Entity
				NEON
				));
			})
			->build();
	}, InvalidConfigurationException::class, "Failed assertion 'Configuration class must be subclass of Doctrine\ORM\Configuration' for item 'nettrine.orm › managers › default › configurationClass' with value 'stdClass'.");
});
