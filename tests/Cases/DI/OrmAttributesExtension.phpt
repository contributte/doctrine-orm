<?php declare(strict_types = 1);

/**
 * @phpVersion >= 8.0
 */

namespace Tests\Casesě\DI;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nette\DI\InvalidConfigurationException;
use Nettrine\ORM\DI\OrmAttributesExtension;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;

require_once __DIR__ . '/../../bootstrap.php';

// Ok
Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.orm.attributes', new OrmAttributesExtension());
			$compiler->addConfig(Helpers::neon('
				nettrine.orm.attributes:
					mapping:
						App\Model\Entity: %appDir%
				'));
		})
		->build();

	/** @var MappingDriverChain $driver */
	$driver = $container->getService('nettrine.orm.mappingDriver');

	Assert::type(AttributeDriver::class, current($driver->getDrivers()));

	/** @var AttributeDriver $attributeDriver */
	$attributeDriver = current($driver->getDrivers());
	Assert::equal([], $attributeDriver->getAllClassNames());
});

// Error (missing mapping)
Toolkit::test(function (): void {
	Assert::exception(function (): void {
		Container::of()
			->withDefaults()
			->withCompiler(function (Compiler $compiler): void {
				$compiler->addExtension('nettrine.orm.attributes', new OrmAttributesExtension());
			})
			->build();
	}, InvalidConfigurationException::class, "The mandatory item 'nettrine.orm.attributes › mapping' is missing.");
});
