<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nettrine\ORM\DI\OrmYamlExtension;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Simple driver
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.orm.yaml', new OrmYamlExtension());
			$compiler->addConfig(Helpers::neon('
				nettrine.orm.yaml:
					mapping:
						App\Model\Entity: %appDir%
				'));
		})
		->build();

	/** @var MappingDriverChain $driver */
	$driver = $container->getService('nettrine.orm.mappingDriver');

	/** @var SimplifiedYamlDriver $xmlDriver */
	$xmlDriver = current($driver->getDrivers());

	Assert::type(SimplifiedYamlDriver::class, $xmlDriver);
	Assert::equal([], $xmlDriver->getAllClassNames());
});

// Error (missing mapping)
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		Container::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.yaml', new OrmYamlExtension());
			})
			->build();
	}, InvalidConfigurationException::class, "The mandatory item 'nettrine.orm.yaml › mapping' is missing.");
});
