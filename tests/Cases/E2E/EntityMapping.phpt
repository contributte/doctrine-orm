<?php declare(strict_types = 1);

use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\DI\Compiler;
use Nettrine\ORM\DI\OrmXmlExtension;
use Contributte\Tester\Toolkit;
use Tester\Assert;
use Tests\Fixtures\EntityMappingCompilerExtension;
use Tests\Toolkit\Container;
use Tests\Toolkit\Helpers;
use Tests\Toolkit\Tests;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$container = Container::of()
		->withDefaults()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('nettrine.orm.xml', new OrmXmlExtension());
			$compiler->addExtension('tests.mapping', new EntityMappingCompilerExtension());
			$compiler->addConfig(Helpers::neon('
					nettrine.orm.xml:
						mapping:
							App\Model\Entity2: %appDir%
				'));
		})
		->build();

	/** @var XmlDriver $xmlDriver */
	$xmlDriver = $container->getService(current(array_keys($container->findByTag(OrmXmlExtension::DRIVER_TAG))));

	Assert::equal([
		Tests::APP_PATH,
		Tests::FIXTURES_PATH,
	], array_values($xmlDriver->getLocator()->getPaths()));

	/** @var MappingDriverChain $chainDriver */
	$chainDriver = $container->getService('nettrine.orm.mappingDriver');
	Assert::count(2, $chainDriver->getDrivers());
});
