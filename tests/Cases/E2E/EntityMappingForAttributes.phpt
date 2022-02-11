<?php declare(strict_types = 1);

/**
 * @phpVersion >= 8.0
 */

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nettrine\ORM\DI\OrmAttributesExtension;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Fixtures\EntityMappingCompilerExtensionForAttributes;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.orm.attributes', new OrmAttributesExtension());
			$compiler->addExtension('tests.mapping', new EntityMappingCompilerExtensionForAttributes());
			$compiler->addConfig(Helpers::neon('
					nettrine.orm.attributes:
						mapping:
							App\Model\Entity1: %appDir%
				'));
		})
		->build();

	/** @var AttributeDriver $attributeDriver */
	$attributeDriver = $container->getService(current(array_keys($container->findByTag(OrmAttributesExtension::DRIVER_TAG))));

	Assert::equal([
		Tests::APP_PATH,
		Tests::FIXTURES_PATH,
	], array_values($attributeDriver->getPaths()));

	/** @var MappingDriverChain $chainDriver */
	$chainDriver = $container->getService('nettrine.orm.mappingDriver');
	Assert::count(2, $chainDriver->getDrivers());
});
